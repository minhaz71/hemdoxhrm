<?php

namespace App\Http\Controllers;

use App\Http\Requests\Leave\ApplyLeaveRequest;
use App\Http\Requests\Leave\AdminUpdateLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveService $leaveService) {}

    // GET /leaves — list all (admin/hr/manager) or own (employee)
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'employee_id']);
        $user = $request->user();

        if ($user->hasRole(['admin', 'hr', 'manager'])) {
            $leaves = $this->leaveService->paginateAll($filters);
            $employees = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
            $isSelfService = false;
        } else {
            $employee = $user->employee;

            abort_unless($employee, 404, 'No employee record linked to your account.');

            $filters['employee_id'] = $employee->id;
            $leaves = $this->leaveService->paginateForEmployee($employee, $filters);
            $employees = collect([$employee]);
            $isSelfService = true;
        }

        return view('leaves.index', compact('leaves', 'employees', 'filters', 'isSelfService'));
    }

    // GET /leaves/create
    public function create()
    {
        $user = auth()->user();
        $leaveTypes = LeaveType::active()->get();
        $isSelfService = ! $user->hasRole(['admin', 'hr']);

        if ($isSelfService) {
            $employee = $user->employee;

            abort_unless($employee, 404, 'No employee record linked to your account.');

            $employees = collect([$employee]);
        } else {
            $employee = null;
            $employees = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        }

        return view('leaves.create', compact('leaveTypes', 'employees', 'employee', 'isSelfService'));
    }

    // POST /leaves
    public function store(ApplyLeaveRequest $request)
    {
        $employee = Employee::findOrFail($request->employee_id);
        $leave    = $this->leaveService->apply($employee, $request->validated());

        $msg = $leave->is_unpaid_override
            ? 'Leave applied. Note: your paid leave limit is exceeded — this will be treated as unpaid.'
            : 'Leave application submitted successfully.';

        return redirect()->route('leaves.index')->with('success', $msg);
    }

    // GET /leaves/{leave}
    public function show(Leave $leave)
    {
        $this->authorize('view', $leave);

        $leave->load(['employee', 'leaveType', 'approvedBy']);
        $balance = $this->leaveService->balanceFor($leave->employee, now()->year);

        return view('leaves.show', compact('leave', 'balance'));
    }

    // PATCH /leaves/{leave}/action — approve or reject
    public function action(UpdateLeaveRequest $request, Leave $leave)
    {
        $this->authorize('action', $leave);

        $approver = auth()->user();

        if ($request->action === 'approved') {
            $this->leaveService->approve($leave, $approver);
            $msg = 'Leave approved.';
        } else {
            $this->leaveService->reject($leave, $approver, $request->rejection_note ?? '');
            $msg = 'Leave rejected.';
        }

        return redirect()->route('leaves.index')->with('success', $msg);
    }

    // PATCH /leaves/{leave}/admin-update — admin-only correction for status/dates
    public function adminUpdate(AdminUpdateLeaveRequest $request, Leave $leave)
    {
        $this->authorize('adminUpdate', $leave);

        $this->leaveService->adminUpdate($leave, $request->validated(), $request->user());

        return redirect()
            ->route('leaves.show', $leave)
            ->with('success', 'Leave application updated by admin.');
    }

    // DELETE /leaves/{leave} — employee cancels pending leave
    public function destroy(Leave $leave)
    {
        $this->authorize('delete', $leave);

        $this->leaveService->cancel($leave);

        return redirect()->route('leaves.index')->with('success', 'Leave application cancelled.');
    }

    // GET /leaves/balance — balance checker for an employee
    public function balance(Request $request)
    {
        $employee = Employee::findOrFail($request->integer('employee_id'));

        $this->authorize('viewLeaveBalance', $employee);

        $year    = $request->integer('year', now()->year);
        $balance = $this->leaveService->balanceFor($employee, $year);

        return response()->json(['balance' => $balance]);
    }
}
