<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Services\AttendanceCalendarService;
use App\Services\SettingService;
use App\Support\DayStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentEmployees extends Command
{
    protected $signature = 'hrms:mark-absent
        {--date= : Date to process (Y-m-d, defaults to yesterday)}
        {--dry-run : Preview what would be created without writing to the database}';

    protected $description = 'Mark absent / holiday / weekly-off / leave attendance for employees with no record';

    public function __construct(
        private readonly AttendanceCalendarService $calendar,
        private readonly SettingService            $settings,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $date    = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
        $dateStr = $date->toDateString();
        $dryRun  = (bool) $this->option('dry-run');

        // 'record' → create holiday/weekend attendance rows
        // 'skip'   → silently ignore non-working holidays/weekends (legacy behaviour)
        // NOTE: weekly_off is ALWAYS recorded regardless of this setting.
        $recordHolidays = $this->settings->get('holiday_attendance_record', 'skip') === 'record';

        $this->line('');
        $this->info("  📅  Processing attendance for <comment>{$dateStr}</comment>");
        if ($dryRun) {
            $this->warn('  ⚠️   Dry-run mode — no records will be written.');
        }
        $this->line('');

        // Pre-load approved leaves for this date (single query instead of N+1)
        $onLeaveEmployeeIds = Leave::where('status', 'approved')
            ->where('start_date', '<=', $dateStr)
            ->where('end_date',   '>=', $dateStr)
            ->pluck('employee_id')
            ->unique();

        // Eager-load relationships used inside getDayStatus() to avoid N+1
        $employees = Employee::active()
            ->with([
                'weeklyOffs' => fn ($q) => $q->active(),
                'branch',
                'department',
            ])
            ->get();

        $counters = [
            'absent'     => 0,
            'leave'      => 0,
            'holiday'    => 0,
            'weekly_off' => 0,
            'weekend'    => 0,
            'skipped'    => 0,   // already had a record
        ];

        foreach ($employees as $employee) {
            // Skip employees who already have any attendance record for the date
            if (Attendance::forEmployee($employee->id)->forDate($dateStr)->exists()) {
                $counters['skipped']++;
                continue;
            }

            // ── Resolve what kind of day this is for this specific employee ──
            $dayStatus = $this->calendar->getDayStatus($employee, $date);

            // ── Non-working day: holiday, weekly_off, or weekend ──────────
            if (! $dayStatus->isWorking()) {
                $statusKey = match (true) {
                    $dayStatus->isHoliday()   => 'holiday',
                    $dayStatus->isWeeklyOff() => 'weekly_off',
                    default                    => 'weekend',
                };
                $counters[$statusKey]++;

                if ($dayStatus->isWeeklyOff()) {
                    // Weekly off is ALWAYS recorded so attendance boards and payroll
                    // can distinguish between "absent" and "off day".
                    $this->createRecord(
                        $employee->id, $dateStr,
                        $dayStatus->toAttendanceStatus(),
                        $dayStatus->toAttendanceNote(),
                        $dryRun,
                    );
                } elseif ($recordHolidays) {
                    // Holidays + weekends only recorded when admin setting = 'record'
                    $this->createRecord(
                        $employee->id, $dateStr,
                        $dayStatus->toAttendanceStatus(),
                        $dayStatus->toAttendanceNote(),
                        $dryRun,
                    );
                }
                // else: silently skip holidays/weekends

                continue;
            }

            // ── Working day: check for approved leave first ────────────────
            if ($onLeaveEmployeeIds->contains($employee->id)) {
                // Employee is on approved leave — record as 'leave', not absent
                $counters['leave']++;
                $this->createRecord($employee->id, $dateStr, 'leave', 'On approved leave.', $dryRun);
                continue;
            }

            // ── Normal absent — expected to work but no check-in ──────────
            $counters['absent']++;
            $this->createRecord($employee->id, $dateStr, 'absent', null, $dryRun);
        }

        // ── Summary output ────────────────────────────────────────────
        $this->line('');
        $this->table(
            ['Category', 'Count'],
            [
                ['🔴 Absent (marked)',                                   $counters['absent']],
                ['🏖️  On Approved Leave (marked)',                        $counters['leave']],
                ['🎉 Holiday' . ($recordHolidays ? ' (recorded)' : ' (skipped)'), $counters['holiday']],
                ['📅 Weekly off (always recorded)',                       $counters['weekly_off']],
                ['😴 Weekend' . ($recordHolidays ? ' (recorded)' : ' (skipped)'), $counters['weekend']],
                ['✅ Already had record (skipped)',                       $counters['skipped']],
                ['👥 Total active employees',                             $employees->count()],
            ]
        );

        if ($dryRun) {
            $this->warn('  Dry-run complete — no changes made.');
        }
        $this->line('');

        return self::SUCCESS;
    }

    // ── Private helpers ────────────────────────────────────────────────

    private function createRecord(
        int     $employeeId,
        string  $dateStr,
        string  $status,
        ?string $note,
        bool    $dryRun,
    ): void {
        if ($dryRun) {
            $this->line("  [dry-run] employee_id={$employeeId}  status={$status}" . ($note ? "  note=\"{$note}\"" : ''));
            return;
        }

        Attendance::create([
            'employee_id' => $employeeId,
            'date'        => $dateStr,
            'status'      => $status,
            'note'        => $note,
        ]);
    }
}
