<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardService
{
    public function data(Request $request): array
    {
        [$start, $end, $filters] = $this->dateRange($request);

        $attendance = Attendance::whereBetween('date', [$start->toDateString(), $end->toDateString()]);

        $payrollStart = (int) $start->copy()->startOfMonth()->format('Ym');
        $payrollEnd = (int) $end->copy()->startOfMonth()->format('Ym');

        $stats = [
            'total_employees' => Employee::active()->count(),
            'present' => (clone $attendance)->whereIn('status', ['present', 'late', 'underworked'])->count(),
            'on_leave' => Leave::approved()
                ->where('start_date', '<=', $end->toDateString())
                ->where('end_date', '>=', $start->toDateString())
                ->count(),
            'absent' => (clone $attendance)->where('status', 'absent')->count(),
            'payroll_total' => Payroll::whereRaw('(year * 100 + month) between ? and ?', [$payrollStart, $payrollEnd])
                ->sum('net_salary'),
            'payslips_issued' => Payslip::whereBetween('generated_at', [$start, $end->copy()->endOfDay()])->count(),
            'pending_leaves' => Leave::pending()
                ->where('start_date', '<=', $end->toDateString())
                ->where('end_date', '>=', $start->toDateString())
                ->count(),
            'new_employees' => Employee::whereBetween('join_date', [$start->toDateString(), $end->toDateString()])->count(),
        ];

        return [
            'filters' => $filters,
            'periodLabel' => $this->periodLabel($start, $end, $filters),
            'stats' => $stats,
            'recentAttendances' => Attendance::with('employee')
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->latest('date')
                ->latest('check_in')
                ->limit(8)
                ->get(),
            'pendingLeaves' => Leave::with(['employee', 'leaveType'])
                ->pending()
                ->where('start_date', '<=', $end->toDateString())
                ->where('end_date', '>=', $start->toDateString())
                ->latest()
                ->limit(8)
                ->get(),
            'recentEmployees' => Employee::with('designationModel')
                ->whereBetween('join_date', [$start->toDateString(), $end->toDateString()])
                ->latest('join_date')
                ->limit(8)
                ->get(),
        ];
    }

    private function dateRange(Request $request): array
    {
        $type = $request->get('period_type', 'month');
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        if ($type === 'year') {
            $start = Carbon::createFromDate($year, 1, 1)->startOfDay();
            $end = $start->copy()->endOfYear();
        } elseif ($type === 'custom') {
            $start = Carbon::parse($request->get('start_date', now()->startOfMonth()->toDateString()))->startOfDay();
            $end = Carbon::parse($request->get('end_date', now()->endOfMonth()->toDateString()))->endOfDay();

            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }
        } else {
            $month = max(1, min(12, $month));
            $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $type = 'month';
        }

        return [$start, $end, [
            'period_type' => $type,
            'month' => $month,
            'year' => $year,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ]];
    }

    private function periodLabel(Carbon $start, Carbon $end, array $filters): string
    {
        return match ($filters['period_type']) {
            'year' => (string) $filters['year'],
            'custom' => $start->format('M j, Y').' - '.$end->format('M j, Y'),
            default => $start->format('F Y'),
        };
    }
}
