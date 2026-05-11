<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\SalaryHistory;
use App\Models\User;
use App\Services\PermissionService;

class SalaryHistoryPolicy
{
    public function __construct(private readonly PermissionService $permissionService) {}

    /** View the salary history directory from the main menu. */
    public function viewDirectory(User $user): bool
    {
        return $this->permissionService->check($user, 'employees.salary.view');
    }

    /** View all salary history records for an employee. */
    public function viewAny(User $user, Employee $employee): bool
    {
        // Employee can only view their own
        if ($user->employee?->id === $employee->id) return true;

        return $this->permissionService->check($user, 'employees.salary.view');
    }

    /** Create a new salary change record. */
    public function create(User $user, Employee $employee): bool
    {
        return $this->permissionService->check($user, 'employees.salary.manage');
    }

    /** Approve or reject a pending record — admin only. */
    public function approve(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Delete a record — admin only, and only if not used in payroll. */
    public function delete(User $user, SalaryHistory $record): bool
    {
        return $user->hasRole(['admin', 'hr'])
            && in_array($record->salary_type, [SalaryHistory::TYPE_INCREMENT, SalaryHistory::TYPE_DECREMENT], true)
            && ! $record->usedInPayroll();
    }
}
