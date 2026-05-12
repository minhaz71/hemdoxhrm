<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;
use App\Services\PermissionService;

class LeavePolicy
{
    public function __construct(private readonly PermissionService $permissionService) {}

    public function viewAny(User $user): bool
    {
        return true; // list is scoped per-user in the service layer
    }

    public function view(User $user, Leave $leave): bool
    {
        if ($user->employee?->id === $leave->employee_id) return true;

        return $this->permissionService->check($user, 'leave.view');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'leave.apply');
    }

    public function action(User $user, Leave $leave): bool
    {
        return $this->permissionService->check($user, 'leave.approve');
    }

    public function adminUpdate(User $user, Leave $leave): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Leave $leave): bool
    {
        if ($this->permissionService->check($user, 'leave.delete')) return true;

        // Employees may cancel their own pending leave
        return $user->employee?->id === $leave->employee_id
            && $leave->status === 'pending';
    }
}
