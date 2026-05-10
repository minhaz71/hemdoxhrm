<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Services\PermissionService;

class EmployeePolicy
{
    public function __construct(private readonly PermissionService $permissionService) {}

    public function viewAny(User $user): bool
    {
        return $this->permissionService->check($user, 'employee.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($user->employee?->id === $employee->id) return true;

        return $this->permissionService->check($user, 'employee.view');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'employee.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->permissionService->check($user, 'employee.edit');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $this->permissionService->check($user, 'employee.delete');
    }

    public function terminate(User $user, Employee $employee): bool
    {
        return $this->permissionService->check($user, 'employee.terminate');
    }

    public function viewHistory(User $user, Employee $employee): bool
    {
        if ($user->employee?->id === $employee->id) return true;

        return $this->permissionService->check($user, 'attendance.view');
    }

    public function viewLeaveBalance(User $user, Employee $employee): bool
    {
        if ($user->employee?->id === $employee->id) return true;

        return $this->permissionService->check($user, 'leave.view');
    }

    public function manageEducation(User $user, Employee $employee): bool
    {
        if ($user->employee?->id === $employee->id) return true;

        return $this->permissionService->check($user, 'employee.edit');
    }
}
