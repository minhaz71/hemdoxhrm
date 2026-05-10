<?php

namespace App\Policies;

use App\Models\Payslip;
use App\Models\User;
use App\Services\PermissionService;

class PayslipPolicy
{
    public function __construct(private readonly PermissionService $permissionService) {}

    public function viewAny(User $user): bool
    {
        return $this->permissionService->check($user, 'payslip.view');
    }

    public function view(User $user, Payslip $payslip): bool
    {
        if ($user->employee?->id === $payslip->employee_id) return true;

        return $this->permissionService->check($user, 'payslip.view');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'payslip.generate');
    }
}
