<?php

namespace App\Services;

use App\Services\SalaryResolverService;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\SalarySnapshot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly HolidayCalendarService $calendar,
        private readonly SalaryResolverService $salaryResolver,
        private readonly SettingService $settings,
    ) {}

    // ── Generate single employee payroll ─────────────────────────

    /**
     * @param  bool  $force  When true, delete any existing draft/processed payroll
     *                       and regenerate from scratch. Throws if payroll is locked/paid.
     */
    public function generate(Employee $employee, int $month, int $year, array $adjustments = [], bool $force = false): Payroll
    {
        $existing = Payroll::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existing) {
            if (! $force) {
                throw ValidationException::withMessages([
                    'payroll' => "Payroll already generated for {$employee->full_name} — {$this->monthLabel($month, $year)}.",
                ]);
            }

            // Force-regenerate: refuse if already paid (locked)
            if ($existing->isLocked()) {
                throw ValidationException::withMessages([
                    'payroll' => "Cannot regenerate a paid/locked payroll for {$employee->full_name} — {$this->monthLabel($month, $year)}.",
                ]);
            }

            // Delete existing payroll + snapshot so we can recreate cleanly
            $existing->salarySnapshot?->delete();
            $existing->delete();
        }

        $snapshotData = $this->buildSnapshot($employee, $month, $year, $adjustments);

        $payroll = Payroll::create($snapshotData);

        // Create the immutable salary snapshot tied to this payroll
        $this->createSalarySnapshot($payroll, $employee, $snapshotData);

        return $payroll;
    }

    /**
     * Regenerate payroll for one employee, overwriting any existing draft.
     * Called explicitly by the controller's regenerate action.
     */
    public function regenerate(Employee $employee, int $month, int $year): Payroll
    {
        return $this->generate($employee, $month, $year, [], force: true);
    }

    // ── Generate for ALL active employees in a period ─────────────

    public function generateBulk(int $month, int $year): array
    {
        $employees = Employee::active()->get();
        $results   = ['created' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($employees as $employee) {
            try {
                $this->generate($employee, $month, $year);
                $results['created']++;
            } catch (ValidationException $e) {
                $results['skipped']++;
            } catch (\Throwable $e) {
                $results['errors'][] = "{$employee->full_name}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    // ── Adjust (only before payment) ──────────────────────────────

    public function adjust(Payroll $payroll, array $data): Payroll
    {
        $this->guardLocked($payroll);

        $bonus     = (float) ($data['bonus']           ?? $payroll->bonus);
        $incentive = (float) ($data['incentive']       ?? $payroll->incentive);
        $overtimeEnabled = $this->boolSetting('overtime_pay_enabled', false);
        $overtime  = $overtimeEnabled ? (float) ($data['overtime_amount'] ?? $payroll->overtime_amount) : 0.0;
        $note      = $data['note']                     ?? $payroll->note;
        $lateDeduction = (float) ($data['late_deduction'] ?? $payroll->late_deduction);
        $leaveDeduction = (float) ($data['leave_deduction'] ?? $payroll->leave_deduction);
        $workingDays = array_key_exists('management_working_days', $data)
            ? ($data['management_working_days'] !== null && $data['management_working_days'] !== ''
                ? (int) $data['management_working_days']
                : $payroll->calendar_working_days)
            : $payroll->working_days;

        $gross           = $payroll->base_salary + $bonus + $incentive + $overtime;
        $totalDeductions = $lateDeduction + $payroll->absent_deduction + $leaveDeduction;
        $net             = max(0, $gross - $totalDeductions);

        $payroll->update([
            'bonus'           => $bonus,
            'incentive'       => $incentive,
            'overtime_amount' => $overtime,
            'overtime_enabled'=> $overtimeEnabled,
            'gross_salary'    => $gross,
            'late_deduction'  => $lateDeduction,
            'leave_deduction' => $leaveDeduction,
            'total_deductions'=> $totalDeductions,
            'net_salary'      => $net,
            'working_days'    => $workingDays,
            'management_working_days' => array_key_exists('management_working_days', $data) ? $workingDays : $payroll->management_working_days,
            'note'            => $note,
            'status'          => 'processed',
        ]);

        return $payroll->fresh();
    }

    // ── Mark as paid — LOCKS the payroll and its snapshot ────────

    public function markPaid(Payroll $payroll, User $paidBy): Payroll
    {
        $this->guardLocked($payroll);

        $payroll->update([
            'status'  => 'paid',
            'paid_at' => now(),
            'paid_by' => $paidBy->id,
        ]);

        // Lock the snapshot — after this point it is fully immutable
        $payroll->salarySnapshot?->lock($paidBy);

        return $payroll->fresh();
    }

    public function sendPayrollMessage(Payroll $payroll, User $sender): Payroll
    {
        $payroll->loadMissing(['employee.user', 'employee.teamLeader.user']);

        $this->notifications->payrollGenerated($payroll);

        $payroll->update([
            'email_sent_at' => now(),
            'email_sent_by' => $sender->id,
        ]);

        return $payroll->fresh(['employee', 'emailSentBy']);
    }

    // ── Bulk pay entire period ─────────────────────────────────────

    public function bulkPay(int $month, int $year, User $paidBy): int
    {
        $payrolls = Payroll::with('salarySnapshot')
            ->forPeriod($month, $year)
            ->whereIn('status', ['draft', 'processed'])
            ->get();

        foreach ($payrolls as $payroll) {
            $payroll->update([
                'status'  => 'paid',
                'paid_at' => now(),
                'paid_by' => $paidBy->id,
            ]);
            $payroll->salarySnapshot?->lock($paidBy);
        }

        return $payrolls->count();
    }

    public function bulkPaySelected(array $ids, User $paidBy): int
    {
        $payrolls = Payroll::with('salarySnapshot')
            ->whereIn('id', $ids)
            ->whereIn('status', ['draft', 'processed'])
            ->get();

        foreach ($payrolls as $payroll) {
            $payroll->update([
                'status'  => 'paid',
                'paid_at' => now(),
                'paid_by' => $paidBy->id,
            ]);
            $payroll->salarySnapshot?->lock($paidBy);
        }

        return $payrolls->count();
    }

    public function bulkSendPayrollMessages(int $month, int $year, User $sender): int
    {
        $payrolls = Payroll::with(['employee.user', 'employee.teamLeader.user'])
            ->forPeriod($month, $year)
            ->whereNull('email_sent_at')
            ->get();

        foreach ($payrolls as $payroll) {
            $this->sendPayrollMessage($payroll, $sender);
        }

        return $payrolls->count();
    }

    public function bulkSendSelectedPayrollMessages(array $ids, User $sender): int
    {
        $payrolls = Payroll::with(['employee.user', 'employee.teamLeader.user'])
            ->whereIn('id', $ids)
            ->whereNull('email_sent_at')
            ->get();

        foreach ($payrolls as $payroll) {
            $this->sendPayrollMessage($payroll, $sender);
        }

        return $payrolls->count();
    }

    // ── Queries ───────────────────────────────────────────────────

    public function paginateByPeriod(int $month, int $year, int $perPage = 20): LengthAwarePaginator
    {
        return Payroll::with('employee')
            ->forPeriod($month, $year)
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForEmployee(Employee $employee, int $perPage = 15): LengthAwarePaginator
    {
        return Payroll::where('employee_id', $employee->id)
            ->with('payslip')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate($perPage);
    }

    public function employeeSummary(Employee $employee): array
    {
        $latest = Payroll::where('employee_id', $employee->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        $paidTotal = Payroll::where('employee_id', $employee->id)
            ->where('status', 'paid')
            ->sum('net_salary');

        return [
            'current_base_salary' => (float) $employee->base_salary,
            'latest_payroll'      => $latest,
            'paid_total'          => (float) $paidTotal,
        ];
    }

    public function periodSummary(int $month, int $year): array
    {
        $agg = Payroll::forPeriod($month, $year)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status IN ('draft','processed') THEN 1 ELSE 0 END) as draft_count,
                SUM(gross_salary)      as total_gross,
                SUM(total_deductions)  as total_deductions,
                SUM(net_salary)        as total_net
            ")
            ->first();

        return [
            'total_employees'  => (int)   ($agg->total            ?? 0),
            'total_gross'      => (float)  ($agg->total_gross      ?? 0),
            'total_deductions' => (float)  ($agg->total_deductions ?? 0),
            'total_net'        => (float)  ($agg->total_net        ?? 0),
            'paid_count'       => (int)   ($agg->paid_count        ?? 0),
            'draft_count'      => (int)   ($agg->draft_count       ?? 0),
        ];
    }

    // ── Core Calculation ──────────────────────────────────────────

    private function buildSnapshot(Employee $employee, int $month, int $year, array $adj): array
    {
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd   = $periodStart->copy()->endOfMonth();

        // Calendar working days respect holidays + weekly offs. Management may
        // override this denominator for one employee or a full bulk run.
        $calendarWorkingDays = $this->calendar->countWorkingDaysForEmployee($employee, $periodStart, $periodEnd);
        $managementWorkingDays = isset($adj['management_working_days']) && $adj['management_working_days'] !== ''
            ? (int) $adj['management_working_days']
            : null;
        $workingDays = $managementWorkingDays ?? $calendarWorkingDays;

        // ── Salary resolution from history (mode-aware) ───────────
        // Delegates to SalaryResolverService which applies the admin-configured
        // mode: month_start | month_end | prorated.
        // NEVER reads employees.base_salary — always from salary_histories.
        $resolution = $this->salaryResolver->getSalaryForMonth($employee, $month, $year, $workingDays);
        $baseSalary = $resolution['effective_salary'];

        // Daily salary rate (denominator = total working days for the month)
        $dailyRate = $workingDays > 0 ? $baseSalary / $workingDays : 0;

        // ── Attendance stats ───────────────────────────────────────
        $attendances = Attendance::forEmployee($employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $presentDays    = $attendances->whereIn('status', ['present', 'late', 'underworked'])->count();
        $lateDays       = $attendances->where('status', 'late')->count();
        $holidayDays    = $attendances->where('status', 'holiday')->count();
        $weeklyOffDays  = $attendances->where('status', 'weekly_off')->count();
        $leaveDaysAtt   = $attendances->where('status', 'leave')->count();

        // ── Approved leave days (clipped to this period) ──────────
        // Fetch all approved leaves overlapping this month.
        // IMPORTANT: clip each leave's days to the period boundaries to
        // avoid double-counting multi-month leaves.
        $approvedLeaves = Leave::forEmployee($employee->id)
            ->join('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id')
            ->where('leaves.status', 'approved')
            ->where(function ($q) use ($periodStart, $periodEnd) {
                $q->where('leaves.start_date', '<=', $periodEnd)
                  ->where('leaves.end_date',   '>=', $periodStart);
            })
            ->select(
                'leaves.start_date',
                'leaves.end_date',
                'leaves.is_unpaid_override',
                'leave_types.is_paid',
            )
            ->get();

        $paidLeaveDays   = 0;
        $unpaidLeaveDays = 0;

        foreach ($approvedLeaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date)->max($periodStart);
            $leaveEnd   = Carbon::parse($leave->end_date)->min($periodEnd);

            if ($leaveStart->gt($leaveEnd)) {
                continue; // no overlap in this period
            }

            $days = $leaveStart->diffInDays($leaveEnd) + 1;

            $isPaid = $leave->is_paid && ! $leave->is_unpaid_override;

            if ($isPaid) {
                $paidLeaveDays += $days;
            } else {
                $unpaidLeaveDays += $days;
            }
        }

        // Total leave days affecting attendance (paid + unpaid)
        $totalLeaveDays = $paidLeaveDays + $unpaidLeaveDays;
        // Use max of attendance records vs leave application count (cron may not have run)
        $leaveDaysSnapshot = max($leaveDaysAtt, $totalLeaveDays);

        // ── Absent days: working days an employee missed AND wasn't on leave ──
        // present + leave = accounted for; the rest are absent
        $absentDays = max(0, $workingDays - $presentDays - $totalLeaveDays);

        // ── Earnings ──────────────────────────────────────────────
        $bonus      = (float) ($adj['bonus']           ?? 0);
        $incentive  = (float) ($adj['incentive']       ?? 0);
        $overtimeEnabled = $this->boolSetting('overtime_pay_enabled', false);
        $overtime   = $overtimeEnabled ? (float) ($adj['overtime_amount'] ?? 0) : 0.0;
        $gross      = $baseSalary + $bonus + $incentive + $overtime;

        // ── Deductions ────────────────────────────────────────────
        $latePenaltyEnabled = $this->boolSetting('late_penalty_enabled', true);
        $latePenaltyAmount = (float) $this->settings->get('late_penalty_amount', 10);
        $leavePenaltyEnabled = $this->boolSetting('leave_penalty_enabled', true);
        $leavePenaltyRate = (float) $this->settings->get('leave_penalty_rate', 1);

        $lateDeduction   = $latePenaltyEnabled ? round($lateDays * $latePenaltyAmount, 2) : 0.0;
        $absentDeduction = round($absentDays * $dailyRate, 2);
        // Only unpaid leave is deducted; paid leave has no deduction
        $leaveDeduction  = $leavePenaltyEnabled ? round($unpaidLeaveDays * $dailyRate * $leavePenaltyRate, 2) : 0.0;

        // HR/Admin can override penalty values during generation.
        if (array_key_exists('late_deduction', $adj) && $adj['late_deduction'] !== null && $adj['late_deduction'] !== '') {
            $lateDeduction = (float) $adj['late_deduction'];
        }
        if (array_key_exists('leave_deduction', $adj) && $adj['leave_deduction'] !== null && $adj['leave_deduction'] !== '') {
            $leaveDeduction = (float) $adj['leave_deduction'];
        }

        $totalDeductions = $lateDeduction + $absentDeduction + $leaveDeduction;

        // ── Net ───────────────────────────────────────────────────
        $net = max(0, $gross - $totalDeductions);

        return [
            'employee_id'       => $employee->id,
            'month'             => $month,
            'year'              => $year,

            // Earnings snapshot
            'base_salary'       => $baseSalary,
            'bonus'             => $bonus,
            'incentive'         => $incentive,
            'overtime_amount'   => $overtime,
            'overtime_enabled'   => $overtimeEnabled,
            'gross_salary'      => $gross,

            // Deductions snapshot
            'late_deduction'    => $lateDeduction,
            'absent_deduction'  => $absentDeduction,
            'leave_deduction'   => $leaveDeduction,
            'total_deductions'  => $totalDeductions,

            // Net
            'net_salary'        => $net,

            // Attendance audit trail
            'working_days'      => $workingDays,
            'management_working_days' => $managementWorkingDays,
            'calendar_working_days' => $calendarWorkingDays,
            'holiday_days'      => $holidayDays,
            'weekly_off_days'   => $weeklyOffDays,
            'leave_days'        => $leaveDaysSnapshot,
            'present_days'      => $presentDays,
            'absent_days'       => $absentDays,
            'late_days'         => $lateDays,
            'late_penalty_enabled' => $latePenaltyEnabled,
            'late_penalty_amount' => $latePenaltyAmount,
            'paid_leave_days'   => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'leave_penalty_enabled' => $leavePenaltyEnabled,
            'leave_penalty_rate' => $leavePenaltyRate,

            // Salary resolution audit
            'salary_resolution_mode' => $resolution['mode'],
            'salary_had_mid_change'  => $resolution['has_mid_change'],
            'salary_segments'        => count($resolution['segments']) > 1
                ? $resolution['segments']
                : null,

            'note'   => $adj['note'] ?? null,
            'status' => 'draft',
        ];
    }

    // ── Snapshot creation (called after payroll is created) ──────

    private function createSalarySnapshot(Payroll $payroll, Employee $employee, array $data): SalarySnapshot
    {
        return SalarySnapshot::create([
            'employee_id'      => $employee->id,
            'payroll_id'       => $payroll->id,
            'month'            => $data['month'],
            'year'             => $data['year'],

            // Salary figures — copied from computed data, not re-read from employee
            'base_salary'      => $data['base_salary'],
            'bonus'            => $data['bonus'],
            'incentive'        => $data['incentive'],
            'overtime'         => $data['overtime_amount'],
            'gross_salary'     => $data['gross_salary'],
            'late_deduction'   => $data['late_deduction'],
            'absent_deduction' => $data['absent_deduction'],
            'leave_deduction'  => $data['leave_deduction'],
            'total_deductions' => $data['total_deductions'],
            'net_salary'       => $data['net_salary'],

            // Employee metadata frozen at this moment
            'employee_code'    => $employee->employee_code,
            'employee_name'    => $employee->full_name,
            'department'       => $employee->department,
            'designation'      => $employee->designation,

            'is_locked' => false,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function guardLocked(Payroll $payroll): void
    {
        if ($payroll->isLocked()) {
            throw ValidationException::withMessages([
                'payroll' => 'This payroll record is locked after payment and cannot be modified.',
            ]);
        }
    }

    private function monthLabel(int $month, int $year): string
    {
        return Carbon::createFromDate($year, $month, 1)->format('F Y');
    }

    private function boolSetting(string $key, bool $default): bool
    {
        $value = $this->settings->get($key, $default);

        return in_array($value, [true, 'true', '1', 1], true);
    }
}
