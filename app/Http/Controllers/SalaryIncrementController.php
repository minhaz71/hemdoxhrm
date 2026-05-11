<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalaryHistory\StoreSalaryIncrementRequest;
use App\Http\Requests\SalaryHistory\UpdateSalaryIncrementRequest;
use App\Models\Employee;
use App\Models\SalaryHistory;
use App\Services\SalaryIncrementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SalaryIncrementController extends Controller
{
    public function __construct(
        private readonly SalaryIncrementService $service
    ) {}

    /**
     * Paginated list with filters.
     */
    public function index(Request $request): View
    {
        abort_unless(
            Gate::check('employees.salary.manage') || Gate::check('employees.salary.view'),
            403
        );

        $filters = $request->only(['employee_id', 'status', 'salary_type', 'per_page']);

        $records       = $this->service->paginate($filters);
        $employees     = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);
        $pendingCount  = SalaryHistory::whereIn('salary_type', [
            SalaryHistory::TYPE_INCREMENT,
            SalaryHistory::TYPE_DECREMENT,
            SalaryHistory::TYPE_ADJUSTMENT,
        ])->where('status', SalaryHistory::STATUS_PENDING)->count();

        return view('salary-increment.index', compact('records', 'employees', 'filters', 'pendingCount'));
    }

    /**
     * Show create form. Accept optional ?employee_id to pre-select.
     */
    public function create(Request $request): View
    {
        abort_unless(Gate::check('employees.salary.manage'), 403);

        $employees = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);

        // Build a map of employee_id => current_salary for the JS calculator
        $salarMap = [];
        foreach ($employees as $emp) {
            $salarMap[$emp->id] = $this->service->currentSalaryFromHistory($emp);
        }

        $selectedEmployeeId = $request->query('employee_id');

        return view('salary-increment.create', compact('employees', 'salarMap', 'selectedEmployeeId'));
    }

    /**
     * Save increment record.
     */
    public function store(StoreSalaryIncrementRequest $request): RedirectResponse
    {
        abort_unless(Gate::check('employees.salary.manage'), 403);

        $employee = Employee::findOrFail($request->validated()['employee_id']);
        $record   = $this->service->store($employee, $request->validated(), $request->user());

        $msg = $request->user()->isAdmin()
            ? 'Salary increment saved and approved.'
            : 'Salary increment submitted for approval.';

        return redirect()->route('salary-increments.show', $record)->with('success', $msg);
    }

    /**
     * Show a single record.
     */
    public function show(SalaryHistory $salaryIncrement): View
    {
        abort_unless(
            Gate::check('employees.salary.manage') || Gate::check('employees.salary.view'),
            403
        );

        $salaryIncrement->load(['employee', 'changedBy', 'approvedBy']);

        // Last 5 approved salary history for this employee
        $recentHistory = SalaryHistory::where('employee_id', $salaryIncrement->employee_id)
            ->where('status', SalaryHistory::STATUS_APPROVED)
            ->orderByDesc('effective_from')
            ->limit(5)
            ->get();

        return view('salary-increment.show', compact('salaryIncrement', 'recentHistory'));
    }

    /**
     * Edit form for pending records only.
     */
    public function edit(SalaryHistory $salaryIncrement): View
    {
        abort_unless(Gate::check('employees.salary.manage'), 403);

        if (!$salaryIncrement->isPending()) {
            abort(403, 'Only pending records can be edited.');
        }

        $salaryIncrement->load(['employee', 'changedBy']);
        $currentSalary = (float) ($salaryIncrement->previous_salary ?? 0);

        return view('salary-increment.edit', compact('salaryIncrement', 'currentSalary'));
    }

    /**
     * Update a pending record.
     */
    public function update(UpdateSalaryIncrementRequest $request, SalaryHistory $salaryIncrement): RedirectResponse
    {
        abort_unless(Gate::check('employees.salary.manage'), 403);

        if (!$salaryIncrement->isPending()) {
            return back()->withErrors(['status' => 'Only pending records can be edited.']);
        }

        $this->service->update($salaryIncrement, $request->validated(), $request->user());

        return redirect()->route('salary-increments.show', $salaryIncrement)->with('success', 'Record updated.');
    }

    /**
     * Admin approval queue.
     */
    public function approval(Request $request): View
    {
        abort_unless(Gate::check('employees.salary.manage') && $request->user()->isAdmin(), 403);

        $pending = $this->service->pendingApprovals();

        return view('salary-increment.approval', compact('pending'));
    }

    /**
     * Approve a pending record (admin only).
     */
    public function approve(Request $request, SalaryHistory $salaryIncrement): RedirectResponse
    {
        abort_unless(Gate::check('employees.salary.manage') && $request->user()->isAdmin(), 403);

        $this->service->approve($salaryIncrement, $request->user());

        return back()->with('success', 'Salary increment approved.');
    }

    /**
     * Reject a pending record (admin only).
     */
    public function reject(Request $request, SalaryHistory $salaryIncrement): RedirectResponse
    {
        abort_unless(Gate::check('employees.salary.manage') && $request->user()->isAdmin(), 403);

        $note = $request->input('note');
        $this->service->reject($salaryIncrement, $request->user(), $note);

        return back()->with('success', 'Salary increment rejected.');
    }
}
