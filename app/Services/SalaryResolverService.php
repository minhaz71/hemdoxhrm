<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalaryHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Resolves the correct base salary for a given employee and payroll period.
 *
 * Three modes (controlled by the `salary_change_effect_mode` admin setting):
 *
 *  month_start  — use the salary that was active on the 1st of the month.
 *                 Default and simplest. A mid-month raise takes effect next month.
 *
 *  month_end    — use the salary that was active on the last day of the month.
 *                 A raise effective on any day of the month applies to the full month.
 *
 *  prorated     — split the month into segments at each salary change boundary.
 *                 Each segment contributes (working_days_in_segment * salary / total_working_days).
 *                 Most accurate for employees who get a raise mid-month.
 *
 * All modes only consider APPROVED salary_history records.
 * `employees.base_salary` is never read here — salary always comes from salary_histories.
 */
class SalaryResolverService
{
    const MODE_MONTH_START = 'month_start';
    const MODE_MONTH_END   = 'month_end';
    const MODE_PRORATED    = 'prorated';

    const MODE_LABELS = [
        self::MODE_MONTH_START => 'Month Start (salary on 1st of month)',
        self::MODE_MONTH_END   => 'Month End (salary on last day of month)',
        self::MODE_PRORATED    => 'Prorated (weighted by days at each rate)',
    ];

    public function __construct(
        private readonly SettingService $settings,
        private readonly HolidayCalendarService $calendar,
    ) {}

    // ── Public API ────────────────────────────────────────────────

    /**
     * Current configured mode from admin settings.
     */
    public function getMode(): string
    {
        return $this->settings->get('salary_change_effect_mode', self::MODE_MONTH_START);
    }

    /**
     * Get the approved salary that was effective on a specific date.
     * Falls back to 0 if no history record exists.
     */
    public function getSalaryForDate(Employee $employee, Carbon $date): float
    {
        $dateStr = $date->toDateString();

        $record = SalaryHistory::where('employee_id', $employee->id)
            ->approved()
            ->where('effective_from', '<=', $dateStr)
            ->where(fn ($q) => $q->whereNull('effective_to')
                ->orWhere('effective_to', '>=', $dateStr))
            ->orderByDesc('effective_from')
            ->first();

        return $record ? (float) $record->base_salary : 0.0;
    }

    /**
     * Resolve the salary for a full payroll month.
     *
     * Returns a SalaryResolution array:
     * [
     *   'effective_salary'   => float,   // the base_salary to use for this payroll
     *   'mode'               => string,  // mode that was applied
     *   'has_mid_change'     => bool,    // whether a salary change occurred mid-month
     *   'total_working_days' => int,     // total working days in the month
     *   'segments'           => array,   // breakdown (always 1 segment for non-prorated)
     * ]
     */
    public function getSalaryForMonth(Employee $employee, int $month, int $year, ?int $managementWorkingDays = null): array
    {
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $periodEnd   = $periodStart->copy()->endOfMonth();
        $mode        = $this->getMode();

        // All approved salary records that overlap this month
        $records = $this->getRecordsForPeriod($employee, $periodStart, $periodEnd);

        if ($records->isEmpty()) {
            // No history at all — this should not happen for seeded employees
            \Log::warning("SalaryResolver: no salary_history for employee {$employee->id} in {$month}/{$year}");
            return $this->singleSegmentResult(0.0, $mode, false, $periodStart, $periodEnd, 0);
        }

        // Detect mid-month change: more than one record overlaps the period,
        // OR the single record starts after the first of the month
        $hasMidChange = $records->count() > 1
            || Carbon::parse($records->first()->effective_from)->gt($periodStart);

        $totalWorkingDays = $managementWorkingDays
            ?? $this->calendar->countWorkingDaysForEmployee($employee, $periodStart, $periodEnd);

        // ── Simple modes (no split needed) ────────────────────────
        if (! $hasMidChange || $mode !== self::MODE_PRORATED) {
            $salary = match ($mode) {
                self::MODE_MONTH_END => $this->getSalaryForDate($employee, $periodEnd),
                default              => $this->getSalaryForDate($employee, $periodStart), // month_start
            };

            return $this->singleSegmentResult($salary, $mode, $hasMidChange, $periodStart, $periodEnd, $totalWorkingDays);
        }

        // ── Prorated mode ─────────────────────────────────────────
        return $this->calculateProrated($employee, $records, $periodStart, $periodEnd, $totalWorkingDays);
    }

    // ── Prorated calculation ──────────────────────────────────────

    /**
     * Split the month at each salary change boundary.
     *
     * Each segment's contribution = working_days_in_segment × (segment_salary / total_working_days)
     *
     * This ensures the daily rate uses the full month's working days as denominator
     * (so deductions calculated later remain consistent).
     */
    private function calculateProrated(
        Employee   $employee,
        Collection $records,
        Carbon     $periodStart,
        Carbon     $periodEnd,
        int        $totalWorkingDays
    ): array {
        if ($totalWorkingDays === 0) {
            $salary = (float) $records->last()->base_salary;
            return [
                'effective_salary'   => $salary,
                'mode'               => self::MODE_PRORATED,
                'has_mid_change'     => true,
                'total_working_days' => 0,
                'segments'           => [],
            ];
        }

        $segments    = [];
        $totalAmount = 0.0;
        $recordList  = $records->values();

        foreach ($recordList as $i => $record) {
            // Segment starts at the later of: effective_from OR period start
            $segStart = Carbon::parse($record->effective_from)->max($periodStart)->startOfDay();

            // Segment ends at the earlier of: day before next record's effective_from OR period end
            $nextRecord = $recordList->get($i + 1);
            $segEnd = $nextRecord
                ? Carbon::parse($nextRecord->effective_from)->subDay()->min($periodEnd)->endOfDay()
                : $periodEnd->copy()->endOfDay();

            // Skip degenerate segments
            if ($segStart->gt($periodEnd) || $segEnd->lt($periodStart)) {
                continue;
            }

            $segSalary      = (float) $record->base_salary;
            $segWorkingDays = $this->calendar->countWorkingDaysForEmployee(
                $employee,
                $segStart->copy()->startOfDay(),
                $segEnd->copy()->startOfDay()
            );

            // Daily rate uses TOTAL working days (not segment working days) as denominator
            // This keeps the daily rate consistent with the PayrollService deduction logic.
            $dailyRate = $segSalary / $totalWorkingDays;
            $segAmount = round($segWorkingDays * $dailyRate, 4);
            $totalAmount += $segAmount;

            $segments[] = [
                'from'         => $segStart->toDateString(),
                'to'           => $segEnd->toDateString(),
                'salary'       => $segSalary,
                'working_days' => $segWorkingDays,
                'daily_rate'   => round($dailyRate, 4),
                'amount'       => round($segAmount, 2),
            ];
        }

        return [
            'effective_salary'   => round($totalAmount, 2),
            'mode'               => self::MODE_PRORATED,
            'has_mid_change'     => true,
            'total_working_days' => $totalWorkingDays,
            'segments'           => $segments,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Fetch all approved salary_history rows that overlap a date range.
     */
    private function getRecordsForPeriod(Employee $employee, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        return SalaryHistory::where('employee_id', $employee->id)
            ->approved()
            ->where('effective_from', '<=', $periodEnd->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_to')
                ->orWhere('effective_to', '>=', $periodStart->toDateString()))
            ->orderBy('effective_from')
            ->get();
    }

    private function singleSegmentResult(
        float  $salary,
        string $mode,
        bool   $hasMidChange,
        Carbon $periodStart,
        Carbon $periodEnd,
        int    $totalWorkingDays
    ): array {
        return [
            'effective_salary'   => $salary,
            'mode'               => $mode,
            'has_mid_change'     => $hasMidChange,
            'total_working_days' => $totalWorkingDays,
            'segments'           => [
                [
                    'from'         => $periodStart->toDateString(),
                    'to'           => $periodEnd->toDateString(),
                    'salary'       => $salary,
                    'working_days' => $totalWorkingDays,
                    'daily_rate'   => $totalWorkingDays > 0 ? round($salary / $totalWorkingDays, 4) : 0,
                    'amount'       => $salary,
                ],
            ],
        ];
    }
}
