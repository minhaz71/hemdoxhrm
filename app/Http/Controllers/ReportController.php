<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    // GET /reports — hub
    public function index()
    {
        return view('reports.index');
    }

    // GET /reports/attendance
    public function attendance(Request $request)
    {
        $filters    = $request->only(['month', 'year', 'employee_id', 'status', 'department']);
        $data       = $this->reportService->attendanceReport($filters);
        $employees  = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $departments = $this->reportService->departments();

        return view('reports.attendance', array_merge($data, compact('employees', 'departments')));
    }

    // GET /reports/salary
    public function salary(Request $request)
    {
        $filters    = $request->only(['month', 'year', 'employee_id', 'status', 'department']);
        $data       = $this->reportService->salaryReport($filters);
        $employees  = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $departments = $this->reportService->departments();

        return view('reports.salary', array_merge($data, compact('employees', 'departments')));
    }

    // GET /reports/leave
    public function leave(Request $request)
    {
        $filters     = $request->only(['month', 'year', 'employee_id', 'leave_type_id', 'status', 'department']);
        $data        = $this->reportService->leaveReport($filters);
        $employees   = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $leaveTypes  = LeaveType::active()->get();
        $departments = $this->reportService->departments();

        return view('reports.leave', array_merge($data, compact('employees', 'leaveTypes', 'departments')));
    }
}
