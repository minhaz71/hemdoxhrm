<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;
use App\Services\PermissionService;

class PayrollPolicy
{
    public function __construct(private readonly PermissionService $permissionService) {}

    public function viewAny(User $user): bool
    {
        return $this->permissionService->check($user, 'payroll.view');
    }

    public function view(User $user, Payroll $payroll): bool
    {
        if ($user->employee?->id === $payroll->employee_id) return true;

        return $this->permissionService->check($user, 'payroll.view');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'payroll.generate');
    }

    public function update(User $user, Payroll $payroll): bool
    {
        return $this->permissionService->check($user, 'payroll.edit') && !$payroll->isLocked();
    }

    public function pay(User $user, Payroll $payroll): bool
    {
        return $this->permissionService->check($user, 'payroll.pay') && !$payroll->isLocked();
    }
}
