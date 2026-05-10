<?php

namespace App\Http\Controllers;

use App\Services\EmployeeDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly EmployeeDashboardService $dashboards) {}

    public function index()
    {
        $user = auth()->user();

        if ($user->isEmployee() && ! $user->isAdmin() && ! $user->isHR() && ! $user->isManager()) {
            return redirect()->route('dashboard.crm');
        }

        return view('dashboard');
    }

    public function crm()
    {
        $employee = auth()->user()->employee;

        abort_unless($employee, 404, 'No employee record linked to your account.');

        return view('dashboard.employee-crm', $this->dashboards->crm($employee));
    }

    public function timeDoctor(Request $request)
    {
        $employee = auth()->user()->employee;

        abort_unless($employee, 404, 'No employee record linked to your account.');

        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        return view('dashboard.employee-time-doctor', $this->dashboards->timeDoctor($employee, $month, $year));
    }
}
