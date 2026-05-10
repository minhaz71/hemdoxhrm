<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(private readonly HolidayCalendarService $calendar) {}

    // ── Attendance Report ─────────────────────────────────────────

    public function attendanceReport(array $filters): array
    {
        $month      = (int) ($filters['month'] ?? now()->month);
        $year       = (int) ($filters['year']  ?? now()->year);
        $employeeId = $filters['employee_id']  ?? null;
        $status     = $filters['status']       ?? null;
        $department = $filters['department']   ?? null;

        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd   = $periodStart->copy()->endOfMonth();

        // Base employee set
        $employees = Employee::active()
            ->when($employeeId, fn ($q) => $q->where('id', $employeeId))
            ->when($department, fn ($q) => $q->where('department', $department))
            ->with(['weeklyOffs' => fn ($q) => $q->active(), 'branch', 'department'])
            ->get();

        $employeeIds = $employees->pluck('id');

        // Single bulk query — all attendance records for the period
        $allAtt = Attendance::whereIn('employee_id', $employeeIds)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('employee_id');

        // Raw daily records (with status filter applied for the daily table)
        $query = Attendance::with('employee')
            ->whereIn('employee_id', $employeeIds)
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        if ($status) {
            $query->where('status', $status);
        }

        $records = $query->orderBy('date', 'desc')->get();

        // Per-employee summary — all seven day categories
        $summary = $employees->map(function (Employee $emp) use ($allAtt, $periodStart, $periodEnd) {
            // Working days = days employee was expected to attend (excludes holidays + weekly offs)
            $workingDays = $this->calendar->countWorkingDaysForEmployee($emp, $periodStart, $periodEnd);

            $att = $allAtt->get($emp->id, collect());

            // Counts from attendance records (written by cron + manual marking)
            $present    = $att->whereIn('status', ['present', 'late', 'underworked'])->count();
            $late       = $att->where('status', 'late')->count();
            $underworked = $att->where('status', 'underworked')->count();
            $holiday    = $att->where('status', 'holiday')->count();
            $weeklyOff  = $att->where('status', 'weekly_off')->count();
            $leave      = $att->where('status', 'leave')->count();
            // Absent = explicit absent records OR computed if cron hasn't run yet
            $absentRec  = $att->where('status', 'absent')->count();
            $absentCalc = max(0, $workingDays - $present - $leave);
            $absent     = $absentRec > 0 ? $absentRec : $absentCalc;

            // Attendance rate = present / expected-to-work days (excludes off days)
            $rate = $workingDays > 0 ? round($present / $workingDays * 100, 1) : 0;

            return [
                'employee'         => $emp,
                'working_days'     => $workingDays,
                'present_days'     => $present,
                'late_days'        => $late,
                'underworked_days' => $underworked,
                'absent_days'      => $absent,
                'holiday_days'     => $holiday,
                'weekly_off_days'  => $weeklyOff,
                'leave_days'       => $leave,
                'attendance_rate'  => $rate,
            ];
        });

        $totals = [
            'total_employees'   => $employees->count(),
            'avg_attendance'    => round($summary->avg('attendance_rate'), 1),
            'total_present'     => $summary->sum('present_days'),
            'total_late'        => $summary->sum('late_days'),
            'total_underworked' => $summary->sum('underworked_days'),
            'total_absent'      => $summary->sum('absent_days'),
            'total_holiday'     => $summary->sum('holiday_days'),
            'total_weekly_off'  => $summary->sum('weekly_off_days'),
            'total_leave'       => $summary->sum('leave_days'),
        ];

        $workingDays = (int) $summary->max('working_days');

        return compact('records', 'summary', 'totals', 'workingDays', 'month', 'year', 'filters');
    }

    // ── Monthly summary for a single employee (used by payslip / employee portal) ──

    public function employeeMonthSummary(Employee $employee, int $month, int $year): array
    {
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd   = $periodStart->copy()->endOfMonth();

        $workingDays = $this->calendar->countWorkingDaysForEmployee($employee, $periodStart, $periodEnd);

        $att = Attendance::forEmployee($employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $present     = $att->whereIn('status', ['present', 'late', 'underworked'])->count();
        $late        = $att->where('status', 'late')->count();
        $underworked = $att->where('status', 'underworked')->count();
        $holiday     = $att->where('status', 'holiday')->count();
        $weeklyOff   = $att->where('status', 'weekly_off')->count();
        $leave       = $att->where('status', 'leave')->count();
        $absent      = max(0, $att->where('status', 'absent')->count(),
                              $workingDays - $present - $leave);

        return [
            'working_days'     => $workingDays,
            'present_days'     => $present,
            'late_days'        => $late,
            'underworked_days' => $underworked,
            'absent_days'      => $absent,
            'holiday_days'     => $holiday,
            'weekly_off_days'  => $weeklyOff,
            'leave_days'       => $leave,
            'attendance_rate'  => $workingDays > 0 ? round($present / $workingDays * 100, 1) : 0,
        ];
    }

    // ── Salary Report ─────────────────────────────────────────────

    public function salaryReport(array $filters): array
    {
        $month      = (int) ($filters['month'] ?? now()->month);
        $year       = (int) ($filters['year']  ?? now()->year);
        $employeeId = $filters['employee_id']  ?? null;
        $status     = $filters['status']       ?? null;
        $department = $filters['department']   ?? null;

        $query = Payroll::with('employee')
            ->forPeriod($month, $year);

        if ($employeeId) $query->where('employee_id', $employeeId);
        if ($status)     $query->where('status', $status);
        if ($department) {
            $query->whereHas('employee', fn ($q) => $q->where('department', $department))
                  ->with(['employee' => fn ($q) => $q->where('department', $department)]);
        }

        $records = $query->orderByDesc('net_salary')->get();

        $totals = [
            'total_gross'        => $records->sum('gross_salary'),
            'total_deductions'   => $records->sum('total_deductions'),
            'total_net'          => $records->sum('net_salary'),
            'total_bonus'        => $records->sum('bonus'),
            'total_incentive'    => $records->sum('incentive'),
            'total_overtime'     => $records->sum('overtime_amount'),
            'paid_count'         => $records->where('status', 'paid')->count(),
            'draft_count'        => $records->whereIn('status', ['draft', 'processed'])->count(),
            'avg_net'            => $records->avg('net_salary'),
            // Attendance totals across all payrolls in this period
            'total_present'      => $records->sum('present_days'),
            'total_absent'       => $records->sum('absent_days'),
            'total_holiday'      => $records->sum('holiday_days'),
            'total_weekly_off'   => $records->sum('weekly_off_days'),
            'total_leave'        => $records->sum('leave_days'),
            'total_unpaid_leave' => $records->sum('unpaid_leave_days'),
        ];

        return compact('records', 'totals', 'month', 'year', 'filters');
    }

    // ── Leave Report ──────────────────────────────────────────────

    public function leaveReport(array $filters): array
    {
        $year        = (int) ($filters['year']          ?? now()->year);
        $month       = $filters['month']                ?? null;
        $employeeId  = $filters['employee_id']          ?? null;
        $leaveTypeId = $filters['leave_type_id']        ?? null;
        $status      = $filters['status']               ?? null;
        $department  = $filters['department']           ?? null;

        $query = Leave::with(['employee', 'leaveType', 'approvedBy'])
            ->whereYear('start_date', $year);

        if ($month)       $query->whereMonth('start_date', $month);
        if ($employeeId)  $query->where('employee_id', $employeeId);
        if ($leaveTypeId) $query->where('leave_type_id', $leaveTypeId);
        if ($status)      $query->where('status', $status);
        if ($department) {
            $query->whereHas('employee', fn ($q) => $q->where('department', $department))
                  ->with(['employee' => fn ($q) => $q->where('department', $department)]);
        }

        $records = $query->latest()->get();

        $byType = $records->groupBy('leaveType.name')->map(fn ($group, $type) => [
            'type'       => $type,
            'count'      => $group->count(),
            'total_days' => $group->sum('total_days'),
            'approved'   => $group->where('status', 'approved')->count(),
            'pending'    => $group->where('status', 'pending')->count(),
            'rejected'   => $group->where('status', 'rejected')->count(),
        ])->values();

        $totals = [
            'total_applications' => $records->count(),
            'total_days'         => $records->sum('total_days'),
            'approved'           => $records->where('status', 'approved')->count(),
            'pending'            => $records->where('status', 'pending')->count(),
            'rejected'           => $records->where('status', 'rejected')->count(),
            'unpaid_overrides'   => $records->where('is_unpaid_override', true)->count(),
        ];

        return compact('records', 'byType', 'totals', 'year', 'month', 'filters');
    }

    // ── Shared helpers ────────────────────────────────────────────

    public function departments(): Collection
    {
        return \App\Models\Department::active()->orderBy('name')->pluck('name');
    }
}
