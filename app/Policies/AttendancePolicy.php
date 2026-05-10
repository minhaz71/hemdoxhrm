<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use App\Services\PermissionService;

class AttendancePolicy
{
    public function __construct(private readonly PermissionService $permissionService) {}

    public function viewAny(User $user): bool
    {
        return $this->permissionService->check($user, 'attendance.view');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->employee?->id === $attendance->employee_id) return true;

        return $this->permissionService->check($user, 'attendance.view');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->check($user, 'attendance.mark');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $this->permissionService->check($user, 'attendance.edit');
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $this->permissionService->check($user, 'attendance.delete');
    }
}
