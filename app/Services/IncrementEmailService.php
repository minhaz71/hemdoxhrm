<?php

namespace App\Services;

use App\Mail\IncrementMail;
use App\Models\Employee;
use App\Models\SalaryHistory;
use App\Models\SalaryIncrementEmailLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class IncrementEmailService
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    // ── Template management ───────────────────────────────────────

    /**
     * Load the saved default template from system_settings.
     */
    public function getDefaultTemplate(): array
    {
        return [
            'subject'           => $this->settings->get('increment_email_subject',      'Salary Increment Notification'),
            'intro'             => $this->settings->get('increment_email_intro',         ''),
            'closing'           => $this->settings->get('increment_email_closing',       ''),
            'signature_name'    => $this->settings->get('increment_email_signature_name',    'HR Department'),
            'signature_title'   => $this->settings->get('increment_email_signature_title',   'Human Resources'),
            'signature_contact' => $this->settings->get('increment_email_signature_contact', ''),
        ];
    }

    /**
     * Persist updated template defaults to system_settings.
     */
    public function saveDefaultTemplate(array $data): void
    {
        $map = [
            'subject'           => 'increment_email_subject',
            'intro'             => 'increment_email_intro',
            'closing'           => 'increment_email_closing',
            'signature_name'    => 'increment_email_signature_name',
            'signature_title'   => 'increment_email_signature_title',
            'signature_contact' => 'increment_email_signature_contact',
        ];

        $keyed = [];
        foreach ($map as $field => $settingKey) {
            if (array_key_exists($field, $data)) {
                $keyed[$settingKey] = $data[$field];
            }
        }

        $this->settings->setMany($keyed, 'increment_email_template');
    }

    /**
     * Substitute {placeholders} in a text string with actual employee data.
     */
    public function substitutePlaceholders(string $text, SalaryHistory $sh): string
    {
        $emp        = $sh->employee;
        $prevSalary = (float)$sh->previous_salary;
        $newSalary  = (float)$sh->base_salary;
        $delta      = $newSalary - $prevSalary;
        $deltaPct   = $prevSalary > 0 ? abs(($delta / $prevSalary) * 100) : 0;

        $map = [
            '{employee_name}'        => $emp?->full_name ?? '',
            '{name}'                 => $emp?->full_name ?? '',
            '{previous_salary}'      => currency($prevSalary),
            '{new_salary}'           => currency($newSalary),
            '{effective_date}'       => $sh->effective_from->format('F j, Y'),
            '{increment_amount}'     => ($delta >= 0 ? '+' : '') . currency($delta),
            '{increment_percentage}' => ($deltaPct > 0 ? number_format($deltaPct, 1) . '%' : '—'),
            '{company_name}'         => config('app.name'),
        ];

        return str_replace(array_keys($map), array_values($map), $text);
    }

    /**
     * Build a resolved (placeholders substituted) template for a given record.
     */
    public function buildResolvedTemplate(SalaryHistory $sh, array $template): array
    {
        return [
            'subject'           => $this->substitutePlaceholders($template['subject'],           $sh),
            'intro'             => $this->substitutePlaceholders($template['intro'],             $sh),
            'closing'           => $this->substitutePlaceholders($template['closing'],           $sh),
            'signature_name'    => $template['signature_name'],
            'signature_title'   => $template['signature_title'],
            'signature_contact' => $template['signature_contact'],
        ];
    }

    /**
     * Render the email HTML (for preview or logging) using the resolved template.
     */
    public function renderEmail(SalaryHistory $sh, array $template): string
    {
        $resolved = $this->buildResolvedTemplate($sh, $template);
        $mailable = $this->buildMailable($sh, $resolved);
        return $mailable->render();
    }

    // ── Query: employees with increments ─────────────────────────

    /**
     * Paginate approved non-initial salary history records.
     *
     * @param  array{
     *   from?:        string|null,
     *   to?:          string|null,
     *   employee_id?: int|null,
     *   type?:        string|null,
     * } $filters
     */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = SalaryHistory::with(['employee.user', 'changedBy'])
            ->where('status', SalaryHistory::STATUS_APPROVED)
            ->where('salary_type', '!=', SalaryHistory::TYPE_INITIAL)
            ->orderByDesc('effective_from')
            ->orderByDesc('id');

        if (! empty($filters['from'])) {
            $query->where('effective_from', '>=', Carbon::parse($filters['from'])->toDateString());
        }

        if (! empty($filters['to'])) {
            $query->where('effective_from', '<=', Carbon::parse($filters['to'])->toDateString());
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('salary_type', $filters['type']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Fetch multiple salary history records by IDs for preview/send.
     */
    public function getByIds(array $ids): Collection
    {
        return SalaryHistory::with(['employee.user'])
            ->whereIn('id', $ids)
            ->where('status', SalaryHistory::STATUS_APPROVED)
            ->where('salary_type', '!=', SalaryHistory::TYPE_INITIAL)
            ->get();
    }

    // ── Send ──────────────────────────────────────────────────────

    /**
     * Send increment emails to the given salary_history IDs using the provided
     * template. Every attempt is logged in salary_increment_email_logs.
     *
     * @return array{ sent: int, failed: int, errors: string[] }
     */
    public function send(array $salaryHistoryIds, User $sentBy, array $template): array
    {
        $records = $this->getByIds($salaryHistoryIds);
        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        foreach ($records as $sh) {
            $employee = $sh->employee;
            $email    = $employee?->user?->email;
            $emails   = $this->employeeEmails($employee);

            if ($emails === []) {
                $results['failed']++;
                $results['errors'][] = ($employee?->full_name ?? "ID:{$sh->employee_id}") . ': no email address found';
                continue;
            }

            $resolved = $this->buildResolvedTemplate($sh, $template);

            // Create a pending log entry first
            $log = SalaryIncrementEmailLog::create([
                'employee_id'       => $sh->employee_id,
                'salary_history_id' => $sh->id,
                'email'             => $email,
                'subject'           => $resolved['subject'],
                'body'              => '',
                'status'            => SalaryIncrementEmailLog::STATUS_PENDING,
                'sent_by'           => $sentBy->id,
                'sent_at'           => null,
            ]);

            try {
                $mailable = $this->buildMailable($sh, $resolved);
                $body     = $mailable->render();

                Mail::to($emails)->send($mailable);

                $log->update([
                    'body'    => $body,
                    'status'  => SalaryIncrementEmailLog::STATUS_SENT,
                    'sent_at' => now(),
                ]);

                $results['sent']++;
            } catch (\Throwable $e) {
                $renderedBody = '';
                try { $renderedBody = $this->buildMailable($sh, $resolved)->render(); } catch (\Throwable) {}

                $log->update([
                    'body'          => $renderedBody,
                    'status'        => SalaryIncrementEmailLog::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                ]);

                $results['failed']++;
                $results['errors'][] = ($employee->full_name ?? '?') . ': ' . $e->getMessage();
            }
        }

        return $results;
    }

    // ── Logs ──────────────────────────────────────────────────────

    public function paginateLogs(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = SalaryIncrementEmailLog::with(['employee', 'sentBy', 'salaryHistory'])
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function lastSentLog(int $salaryHistoryId): ?SalaryIncrementEmailLog
    {
        return SalaryIncrementEmailLog::where('salary_history_id', $salaryHistoryId)
            ->where('status', SalaryIncrementEmailLog::STATUS_SENT)
            ->latest()
            ->first();
    }

    // ── Private ───────────────────────────────────────────────────

    private function buildMailable(SalaryHistory $sh, array $resolved): IncrementMail
    {
        return new IncrementMail(
            salaryHistory:   $sh,
            emailSubject:    $resolved['subject'],
            introText:       $resolved['intro'],
            closingText:     $resolved['closing'],
            signatureName:   $resolved['signature_name'],
            signatureTitle:  $resolved['signature_title'],
            signatureContact:$resolved['signature_contact'],
            companyName:     config('app.name'),
        );
    }

    private function employeeEmails(?Employee $employee): array
    {
        return collect([
            $employee?->user?->email,
            $employee?->user?->alternate_email,
        ])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
