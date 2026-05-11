<?php

namespace App\Http\Controllers;

use App\Services\EmployeeDashboardService;
use App\Services\AdminDashboardService;
use App\Models\Employee;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly EmployeeDashboardService $dashboards,
        private readonly AdminDashboardService $adminDashboard,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isEmployee() && ! $user->isAdmin() && ! $user->isHR() && ! $user->isManager()) {
            return redirect()->route('dashboard.crm');
        }

        return view('dashboard', $this->adminDashboard->data($request));
    }

    public function crm(Request $request)
    {
        $employee = $this->resolveDashboardEmployee($request);

        abort_unless($employee, 404, 'No employee record linked to your account.');

        return view('dashboard.employee-crm', $this->dashboards->crm($employee) + [
            'employees' => $this->dashboardEmployeeOptions(),
            'canSwitchEmployee' => $this->canSwitchEmployee(),
        ]);
    }

    public function timeDoctor(Request $request)
    {
        $employee = $this->resolveDashboardEmployee($request);

        abort_unless($employee, 404, 'No employee record linked to your account.');

        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        return view('dashboard.employee-time-doctor', $this->dashboards->timeDoctor($employee, $month, $year) + [
            'employees' => $this->dashboardEmployeeOptions(),
            'canSwitchEmployee' => $this->canSwitchEmployee(),
        ]);
    }

    private function resolveDashboardEmployee(Request $request): ?Employee
    {
        if ($this->canSwitchEmployee() && $request->filled('employee_id')) {
            return Employee::findOrFail((int) $request->employee_id);
        }

        if ($this->canSwitchEmployee() && ! auth()->user()->employee) {
            return Employee::active()->orderBy('first_name')->first();
        }

        return auth()->user()->employee;
    }

    private function canSwitchEmployee(): bool
    {
        $user = auth()->user();

        return $user->isAdmin() || $user->isHR() || $user->isManager();
    }

    private function dashboardEmployeeOptions()
    {
        if (! $this->canSwitchEmployee()) {
            return collect();
        }

        return Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);
    }
}
