<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalaryHistory\StoreSalaryHistoryRequest;
use App\Models\Employee;
use App\Models\SalaryHistory;
use App\Services\SalaryHistoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SalaryHistoryController extends Controller
{
    public function __construct(private readonly SalaryHistoryService $service) {}

    /**
     * Main-menu directory for salary history records across employees.
     */
    public function directory(Request $request)
    {
        $this->authorize('viewDirectory', SalaryHistory::class);

        $filters = $request->only(['employee_id', 'status', 'type', 'from', 'to']);
        $records = $this->service->directory($filters);
        $employees = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);

        $statuses = [
            SalaryHistory::STATUS_PENDING => 'Pending',
            SalaryHistory::STATUS_APPROVED => 'Approved',
            SalaryHistory::STATUS_REJECTED => 'Rejected',
        ];

        $types = SalaryHistory::TYPE_LABELS;

        return view('salary-history.directory', compact('records', 'employees', 'filters', 'statuses', 'types'));
    }

    /**
     * Show full salary history for one employee.
     */
    public function index(Employee $employee)
    {
        $this->authorize('viewAny', [SalaryHistory::class, $employee]);

        $histories = $this->service->historyForEmployee($employee);
        $current   = $this->service->currentRecord($employee);
        $pending   = $histories->where('status', SalaryHistory::STATUS_PENDING);

        // Pending queue for admins — only their employees
        $pendingQueue = auth()->user()->isAdmin()
            ? $this->service->pendingQueue()
            : collect();

        return view('salary-history.index', compact(
            'employee', 'histories', 'current', 'pending', 'pendingQueue'
        ));
    }

    /**
     * Store a new salary change record.
     */
    public function store(StoreSalaryHistoryRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorize('create', [SalaryHistory::class, $employee]);

        try {
            $record = $this->service->create($employee, $request->validated(), auth()->user());

            $msg = $record->status === SalaryHistory::STATUS_APPROVED
                ? 'Salary change saved and approved.'
                : 'Salary change submitted for admin approval.';

            return redirect()
                ->route('employees.salary-history.index', $employee)
                ->with('success', $msg);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withInput()->with('error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * Approve a pending salary record (admin only).
     */
    public function approve(Request $request, Employee $employee, SalaryHistory $salaryHistory): RedirectResponse
    {
        $this->authorize('approve', SalaryHistory::class);

        try {
            $this->service->approve($salaryHistory, auth()->user());
            return redirect()
                ->route('employees.salary-history.index', $employee)
                ->with('success', 'Salary change approved. Employee salary updated.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->with('error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * Reject a pending salary record (admin only).
     */
    public function reject(Request $request, Employee $employee, SalaryHistory $salaryHistory): RedirectResponse
    {
        $this->authorize('approve', SalaryHistory::class);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->service->reject($salaryHistory, auth()->user(), $data['note'] ?? null);
            return redirect()
                ->route('employees.salary-history.index', $employee)
                ->with('success', 'Salary change rejected.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->with('error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * Delete a salary record (admin only, not used in payroll).
     */
    public function destroy(Employee $employee, SalaryHistory $salaryHistory): RedirectResponse
    {
        $this->authorize('delete', $salaryHistory);

        try {
            $this->service->delete($salaryHistory);
            return redirect()
                ->route('employees.salary-history.index', $employee)
                ->with('success', 'Salary record deleted.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->with('error', collect($e->errors())->flatten()->first());
        }
    }
}
