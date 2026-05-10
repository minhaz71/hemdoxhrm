<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\DesignationService;
use App\Services\StaffUserService;
use App\Support\EnterprisePassword;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffUserController extends Controller
{
    // Staff roles that can be managed here (excludes plain 'employee')
    private const STAFF_ROLES = ['admin', 'hr', 'manager'];

    public function __construct(
        private readonly DesignationService $designationService,
        private readonly StaffUserService $staffUserService,
    ) {}

    // ── Index ─────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search = $request->input('q');
        $role   = $request->input('role');

        // Users who have at least one staff role
        $staffRoleIds = Role::whereIn('name', self::STAFF_ROLES)->pluck('id');

        $users = User::with(['roles', 'employee.designationModel', 'employee.department'])
            ->whereHas('roles', fn ($q) => $q->whereIn('role_id', $staffRoleIds))
            ->when($search, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('name', 'like', "%{$search}%")
                   ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($role, fn ($q) => $q->whereHas('roles', fn ($q2) => $q2->where('name', $role)))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::whereIn('name', self::STAFF_ROLES)->orderBy('name')->get();

        return view('rbac.staff-users.index', compact('users', 'roles', 'search', 'role'));
    }

    // ── Create form ───────────────────────────────────────────────

    public function create()
    {
        $roles           = Role::whereIn('name', self::STAFF_ROLES)->orderBy('name')->get();
        $designations    = $this->designationService->allActive();
        $departments     = Department::active()->orderBy('name')->get();
        $shifts          = Shift::active()->orderBy('name')->get();
        $branchesEnabled = branches_enabled();
        $branches        = $branchesEnabled ? Branch::active()->orderBy('is_headquarters', 'desc')->orderBy('name')->get() : collect();

        return view('rbac.staff-users.create', compact(
            'roles', 'designations', 'departments', 'shifts', 'branches', 'branchesEnabled'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Account
            'first_name'           => ['required', 'string', 'max:80'],
            'last_name'            => ['required', 'string', 'max:80'],
            'email'                => ['required', 'email', 'max:180', 'unique:users,email'],
            'login_id'             => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:users,login_id'],
            'password'             => ['required', 'confirmed', EnterprisePassword::rules()],
            'force_password_reset' => ['boolean'],
            'role_ids'             => ['required', 'array', 'min:1'],
            'role_ids.*'           => ['integer', 'exists:roles,id'],

            // Employee profile
            'phone'           => ['nullable', 'string', 'max:30'],
            'date_of_birth'   => ['nullable', 'date'],
            'gender'          => ['nullable', 'in:male,female,other'],
            'address'         => ['nullable', 'string', 'max:500'],
            'nid'             => ['nullable', 'string', 'max:50'],
            'designation_id'  => ['nullable', 'exists:designations,id'],
            'department_id'   => ['nullable', 'exists:departments,id'],
            'branch_id'       => ['nullable', 'exists:branches,id'],
            'shift_id'        => ['nullable', 'exists:shifts,id'],
            'join_date'       => ['nullable', 'date'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,internship'],
            'base_salary'     => ['nullable', 'numeric', 'min:0'],
            'photo'           => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $this->staffUserService->create(
            $validated,
            $request->file('photo'),
            $request->boolean('force_password_reset')
        );

        return redirect()->route('rbac.staff-users.index')
            ->with('success', 'Staff user created successfully.');
    }

    // ── Show ──────────────────────────────────────────────────────

    public function show(User $staffUser)
    {
        $staffUser->load(['roles', 'employee.designationModel', 'employee.department', 'employee.branch', 'employee.shift']);

        return view('rbac.staff-users.show', compact('staffUser'));
    }

    // ── Edit form ─────────────────────────────────────────────────

    public function edit(User $staffUser)
    {
        $staffUser->load(['roles', 'employee']);
        $roles           = Role::whereIn('name', self::STAFF_ROLES)->orderBy('name')->get();
        $designations    = $this->designationService->allActive();
        $departments     = Department::active()->orderBy('name')->get();
        $shifts          = Shift::active()->orderBy('name')->get();
        $branchesEnabled = branches_enabled();
        $branches        = $branchesEnabled ? Branch::active()->orderBy('is_headquarters', 'desc')->orderBy('name')->get() : collect();

        return view('rbac.staff-users.edit', compact(
            'staffUser', 'roles', 'designations', 'departments', 'shifts', 'branches', 'branchesEnabled'
        ));
    }

    // ── Update ────────────────────────────────────────────────────

    public function update(Request $request, User $staffUser)
    {
        $validated = $request->validate([
            'first_name'           => ['required', 'string', 'max:80'],
            'last_name'            => ['required', 'string', 'max:80'],
            'email'                => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($staffUser->id)],
            'login_id'             => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'login_id')->ignore($staffUser->id)],
            'new_password'         => ['nullable', 'confirmed', EnterprisePassword::rules()],
            'force_password_reset' => ['boolean'],
            'role_ids'             => ['required', 'array', 'min:1'],
            'role_ids.*'           => ['integer', 'exists:roles,id'],

            'phone'           => ['nullable', 'string', 'max:30'],
            'date_of_birth'   => ['nullable', 'date'],
            'gender'          => ['nullable', 'in:male,female,other'],
            'address'         => ['nullable', 'string', 'max:500'],
            'nid'             => ['nullable', 'string', 'max:50'],
            'designation_id'  => ['nullable', 'exists:designations,id'],
            'department_id'   => ['nullable', 'exists:departments,id'],
            'branch_id'       => ['nullable', 'exists:branches,id'],
            'shift_id'        => ['nullable', 'exists:shifts,id'],
            'join_date'       => ['nullable', 'date'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,internship'],
            'base_salary'     => ['nullable', 'numeric', 'min:0'],
            'photo'           => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $this->staffUserService->update(
            $staffUser,
            $validated,
            $request->file('photo'),
            $request->boolean('force_password_reset')
        );

        return redirect()->route('rbac.staff-users.show', $staffUser)
            ->with('success', 'Staff user updated successfully.');
    }

}
