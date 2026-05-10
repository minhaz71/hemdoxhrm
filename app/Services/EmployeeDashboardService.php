<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\TimeDoctorDailyRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeeDashboardService
{
    public function crm(Employee $employee): array
    {
        $today = today()->toDateString();

        $attendanceToday = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        $latestPayroll = Payroll::where('employee_id', $employee->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        $latestPayslip = Payslip::where('employee_id', $employee->id)
            ->with('payroll')
            ->latest('generated_at')
            ->first();

        $leaveStats = Leave::where('employee_id', $employee->id)
            ->whereYear('start_date', now()->year)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'pending') as pending,
                SUM(status = 'approved') as approved,
                SUM(status = 'rejected') as rejected
            ")
            ->first();

        $recentAttendances = Attendance::where('employee_id', $employee->id)
            ->latest('date')
            ->limit(5)
            ->get();

        $recentLeaves = Leave::with('leaveType')
            ->where('employee_id', $employee->id)
            ->latest()
            ->limit(5)
            ->get();

        return [
            'employee' => $employee->load(['branch', 'department', 'shift', 'designationModel']),
            'attendanceToday' => $attendanceToday,
            'latestPayroll' => $latestPayroll,
            'latestPayslip' => $latestPayslip,
            'leaveStats' => [
                'total' => (int) ($leaveStats->total ?? 0),
                'pending' => (int) ($leaveStats->pending ?? 0),
                'approved' => (int) ($leaveStats->approved ?? 0),
                'rejected' => (int) ($leaveStats->rejected ?? 0),
            ],
            'recentAttendances' => $recentAttendances,
            'recentLeaves' => $recentLeaves,
        ];
    }

    public function timeDoctor(Employee $employee, int $month, int $year): array
    {
        $period = Carbon::createFromDate($year, $month, 1);

        $records = TimeDoctorDailyRecord::where('employee_id', $employee->id)
            ->whereYear('work_date', $year)
            ->whereMonth('work_date', $month)
            ->orderByDesc('work_date')
            ->get();

        $tracked = (int) $records->sum('time_tracked_minutes');
        $idle = (int) $records->sum('idle_minutes');
        $productive = (int) $records->sum(fn ($record) => $record->productive_minutes ?? $record->active_minutes ?? 0);
        $days = $records->count();

        return [
            'employee' => $employee,
            'month' => $month,
            'year' => $year,
            'periodLabel' => $period->format('F Y'),
            'records' => $records,
            'summary' => [
                'days' => $days,
                'tracked_minutes' => $tracked,
                'idle_minutes' => $idle,
                'productive_minutes' => $productive,
                'avg_tracked_minutes' => $days > 0 ? (int) round($tracked / $days) : 0,
                'productivity' => $tracked > 0 ? round(($productive / $tracked) * 100, 1) : 0,
                'idle_rate' => $tracked > 0 ? round(($idle / $tracked) * 100, 1) : 0,
                'underworked_days' => $records->where('attendance_status', 'underworked')->count(),
                'late_days' => $records->where('attendance_status', 'late')->count(),
                'leave_alerts' => $records->where('worked_on_leave', true)->count(),
            ],
            'weekly' => $this->weeklyBuckets($records),
        ];
    }

    private function weeklyBuckets(Collection $records): Collection
    {
        return $records
            ->groupBy(fn ($record) => $record->work_date->startOfWeek()->format('M j'))
            ->map(fn (Collection $items, string $week) => [
                'week' => $week,
                'tracked_minutes' => (int) $items->sum('time_tracked_minutes'),
                'productive_minutes' => (int) $items->sum(fn ($record) => $record->productive_minutes ?? $record->active_minutes ?? 0),
                'idle_minutes' => (int) $items->sum('idle_minutes'),
            ])
            ->values();
    }
}
