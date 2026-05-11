<?php

namespace App\Services;

use App\Mail\HolidayReminderMail;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\HolidayEmailLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Mail;

/**
 * HolidayNotificationService
 *
 * Responsible solely for sending holiday reminder emails.
 * Extracted from HolidayService to follow the single-responsibility principle.
 *
 * Notification window rule:
 *   Notify when  →  today  >=  start_date − notify_before_days
 *   Stop when    →  today  >   end_date   (holiday is completely over)
 *
 * Deduplication:
 *   Per-employee, per-holiday. Uses firstOrCreate + a DB UNIQUE index on
 *   (holiday_id, employee_id) so concurrent cron processes cannot create
 *   duplicate log rows. UniqueConstraintViolationException is caught and
 *   handled gracefully when a race condition does occur.
 *
 * Scope support:
 *   global            → all active employees with a linked user/email
 *   branch            → employees in the holiday's branch
 *   department        → employees in the holiday's department
 *   employee_specific → only employees explicitly attached via pivot
 */
class HolidayNotificationService
{
    public function __construct(private readonly SettingService $settings) {}

    // ── Public API ─────────────────────────────────────────────────────

    /**
     * Send reminder emails for all holidays that are due today.
     * Respects the `holiday_email_enabled` admin setting.
     *
     * @param  bool  $retryFailed  When true, previously failed emails are
     *                             attempted again even if within the same run.
     * @return array{sent:int, failed:int, skipped:int, holidays:int}
     */
    public function sendDueEmails(bool $retryFailed = false): array
    {
        // Master email switch — bail early if disabled in admin settings
        $enabled = $this->settings->get('holiday_email_enabled', true);
        if ($enabled === false || $enabled === 'false') {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'holidays' => 0, 'disabled' => true];
        }

        $stats = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'holidays' => 0];

        $holidays = $this->resolveDueHolidays();

        foreach ($holidays as $holiday) {
            $stats['holidays']++;

            // Eager-load relationships needed for email rendering
            $holiday->loadMissing(['branch', 'department']);

            $employees = $this->resolveRecipients($holiday);

            foreach ($employees as $employee) {
                $result = $this->dispatchToEmployee($holiday, $employee, $retryFailed);

                match ($result) {
                    'sent'    => $stats['sent']++,
                    'failed'  => $stats['failed']++,
                    'skipped' => $stats['skipped']++,
                };
            }

            // Stamp the holiday's first-successful-batch timestamp
            if ($stats['sent'] > 0 && $holiday->email_sent_at === null) {
                $holiday->update(['email_sent_at' => now()]);
            }
        }

        return $stats;
    }

    /**
     * Retry all previously failed emails regardless of the notification window.
     *
     * @return array{sent:int, failed:int}
     */
    public function retryFailed(): array
    {
        $stats = ['sent' => 0, 'failed' => 0];

        $failedLogs = HolidayEmailLog::with(['holiday.branch', 'holiday.department', 'employee.user'])
            ->where('status', 'failed')
            ->get();

        foreach ($failedLogs as $log) {
            if (! $log->holiday || ! $log->employee || ! $log->employee->user?->email) {
                continue;
            }

            $result = $this->sendAndUpdateLog($log->holiday, $log->employee, $log);
            $result === 'sent' ? $stats['sent']++ : $stats['failed']++;
        }

        return $stats;
    }

    // ── Private helpers ────────────────────────────────────────────────

    /**
     * Fetch holidays whose notification date has been reached but whose
     * holiday period has not yet fully ended.
     *
     * Uses a raw DB expression so we avoid loading thousands of rows
     * into PHP just to filter them.
     */
    private function resolveDueHolidays(): Collection
    {
        $today = today()->toDateString();

        // When notify_before_days is NULL on the holiday, fall back to the
        // admin-configured global default from system settings.
        $defaultDays = (int) $this->settings->get('holiday_notify_before_days', 3);

        return Holiday::active()
            ->with(['branch', 'department'])
            ->whereDate('end_date', '>=', $today)           // holiday hasn't fully passed
            ->whereRaw(                                      // notification date reached
                'DATE_SUB(start_date, INTERVAL COALESCE(notify_before_days, ?) DAY) <= ?',
                [$defaultDays, $today]
            )
            ->get();
    }

    /**
     * Resolve the set of active employees who should receive this holiday's
     * reminder based on its scope type.
     */
    private function resolveRecipients(Holiday $holiday): Collection
    {
        return Employee::with('user')
            ->active()
            ->whereHas('user')                              // must have a linked user with email
            ->when(
                $holiday->type === 'branch',
                fn ($q) => $q->where('branch_id', $holiday->branch_id)
            )
            ->when(
                $holiday->type === 'department',
                fn ($q) => $q->where('department_id', $holiday->department_id)
            )
            ->when(
                $holiday->type === 'employee_specific',
                fn ($q) => $q->whereHas(
                    'holidays',
                    fn ($h) => $h->whereKey($holiday->id)
                )
            )
            // 'global' type: no additional filter → all active employees
            ->get();
    }

    /**
     * Attempt to deliver a reminder email to one employee for one holiday.
     *
     * Uses findOrInitLog() which is backed by a DB UNIQUE index on
     * (holiday_id, employee_id). If two cron processes race, the second
     * process catches UniqueConstraintViolationException and fetches the
     * winner's log record instead of creating a duplicate.
     *
     * @return 'sent'|'failed'|'skipped'
     */
    private function dispatchToEmployee(
        Holiday  $holiday,
        Employee $employee,
        bool     $retryFailed,
    ): string {
        $email = $employee->user?->email;

        if (! $email) {
            return 'skipped';
        }

        [$log, $isNew] = $this->findOrInitLog($holiday, $employee, $email);

        // Already delivered successfully — skip regardless of retry flag
        if (! $isNew && $log->status === 'sent') {
            return 'skipped';
        }

        // Failed previously and we're not retrying — skip
        if (! $isNew && $log->status === 'failed' && ! $retryFailed) {
            return 'skipped';
        }

        // Reset a failed log back to pending before retrying
        if (! $isNew && $log->status === 'failed') {
            $log->update(['status' => 'pending', 'error_message' => null]);
        }

        return $this->sendAndUpdateLog($holiday, $employee, $log);
    }

    /**
     * Return the existing email log for this holiday+employee pair, or create
     * a fresh 'pending' one.  Protected against race conditions by the DB UNIQUE
     * constraint on (holiday_id, employee_id): if a concurrent process inserts
     * first we catch UniqueConstraintViolationException and fetch their record.
     *
     * @return array{0: HolidayEmailLog, 1: bool}  [log, wasJustCreated]
     */
    private function findOrInitLog(Holiday $holiday, Employee $employee, string $email): array
    {
        // Fast path: existing record
        $existing = HolidayEmailLog::where('holiday_id', $holiday->id)
            ->where('employee_id', $employee->id)
            ->latest()
            ->first();

        if ($existing) {
            return [$existing, false];
        }

        // Slow path: create a new pending log
        try {
            $log = HolidayEmailLog::create([
                'holiday_id'  => $holiday->id,
                'employee_id' => $employee->id,
                'email'       => $email,
                'subject'     => $this->buildSubject($holiday),
                'status'      => 'pending',
            ]);

            return [$log, true];
        } catch (UniqueConstraintViolationException) {
            // A concurrent cron process created the row between our SELECT and INSERT.
            // Fetch the winner's record and treat it as not-new.
            $log = HolidayEmailLog::where('holiday_id', $holiday->id)
                ->where('employee_id', $employee->id)
                ->latest()
                ->firstOrFail();

            return [$log, false];
        }
    }

    /**
     * Perform the actual mail delivery and update the log row.
     *
     * @return 'sent'|'failed'
     */
    private function sendAndUpdateLog(Holiday $holiday, Employee $employee, HolidayEmailLog $log): string
    {
        try {
            Mail::to($this->employeeEmails($employee))
                ->send(new HolidayReminderMail($holiday, $employee));

            $log->update([
                'status'        => 'sent',
                'sent_at'       => now(),
                'error_message' => null,
            ]);

            return 'sent';
        } catch (\Throwable $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => substr($e->getMessage(), 0, 500),
            ]);

            return 'failed';
        }
    }

    private function buildSubject(Holiday $holiday): string
    {
        $dateRange = $holiday->start_date->equalTo($holiday->end_date)
            ? $holiday->start_date->format('d M Y')
            : $holiday->start_date->format('d M') . ' – ' . $holiday->end_date->format('d M Y');

        return "[Holiday Notice] {$holiday->title} · {$dateRange}";
    }

    private function employeeEmails(Employee $employee): array
    {
        return collect([
            $employee->user?->email,
            $employee->user?->alternate_email,
        ])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
