<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Services\DesignationService;
use App\Services\DegreeTypeService;
use App\Services\EmployeeEducationService;
use App\Services\EmployeeService;
use App\Services\WeeklyOffService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService          $employeeService,
        private readonly DesignationService       $designationService,
        private readonly DegreeTypeService        $degreeTypeService,
        private readonly EmployeeEducationService $educationService,
        private readonly WeeklyOffService         $weeklyOffService,
    ) {}

    public function index(Request $request)
    {
        $filters   = $request->only(['branch_id', 'status', 'search']);
        $employees = $this->employeeService->paginate(15, $filters);
        $branches  = branches_enabled() ? Branch::active()->orderBy('name')->get() : collect();

        return view('employees.index', compact('employees', 'branches', 'filters'));
    }

    public function create()
    {
        $designations    = $this->designationService->allActive();
        $branchesEnabled = branches_enabled();
        $branches        = $branchesEnabled ? Branch::active()->orderBy('is_headquarters', 'desc')->orderBy('name')->get() : collect();
        $departments     = Department::active()->orderBy('name')->get();
        $shifts          = Shift::active()->orderBy('name')->get();
        $teamLeaders     = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);
        $defaultWeeklyOffDays = $this->weeklyOffService->defaultDays();

        return view('employees.create', compact('designations', 'branches', 'departments', 'shifts', 'teamLeaders', 'branchesEnabled', 'defaultWeeklyOffDays'));
    }

    public function store(StoreEmployeeRequest $request)
    {
        $employee = $this->employeeService->create($request->validated());

        return redirect()->route('employees.edit', $employee)
            ->with('success', 'Employee created successfully. You can now add education records below.');
    }

    public function show(Employee $employee)
    {
        $this->authorize('view', $employee);

        $employee->load(['user.roles', 'branch', 'department', 'shift', 'designationModel', 'teamLeader']);

        return view('employees.show', compact('employee'));
    }

    public function me()
    {
        $employee = auth()->user()->employee;

        abort_unless($employee, 404, 'No employee record linked to your account.');

        return redirect()->route('employees.show', $employee);
    }

    public function edit(Employee $employee)
    {
        $designations    = $this->designationService->allActive();
        $educations      = $this->educationService->forEmployee($employee);
        $degreeTypes     = $this->degreeTypeService->allActive();
        $branchesEnabled = branches_enabled();
        $branches        = $branchesEnabled ? Branch::active()->orderBy('is_headquarters', 'desc')->orderBy('name')->get() : collect();
        $departments     = Department::active()->orderBy('name')->get();
        $shifts          = Shift::active()->orderBy('name')->get();
        $teamLeaders     = Employee::active()
            ->whereKeyNot($employee->id)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_code']);

        return view('employees.edit', compact('employee', 'designations', 'educations', 'degreeTypes', 'branches', 'departments', 'shifts', 'teamLeaders', 'branchesEnabled'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $data = $request->validated();

        // Handle file uploads — keep existing file if no new one provided
        foreach (['photo', 'nid_document', 'certificate'] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store("employees/{$field}s", 'public');
            } else {
                unset($data[$field]); // don't overwrite with null
            }
        }

        $this->employeeService->update($employee, $data);

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $this->employeeService->delete($employee);

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted.');
    }

    public function terminate(Employee $employee)
    {
        $this->employeeService->terminate($employee);

        return redirect()->route('employees.index')
            ->with('success', "{$employee->full_name} has been terminated.");
    }
}
