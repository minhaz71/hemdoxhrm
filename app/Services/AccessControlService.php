<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AccessControlService
{
    private const CRITICAL_PERMISSIONS = [
        'roles.manage',
        'settings.edit',
        'users.manage',
        'invitations.manage',
    ];

    public function assertCanAssignRoles(User $actor, User $target, array $roleIds): void
    {
        $roles = Role::whereIn('id', $roleIds)->get();

        if ($roles->contains('name', Role::ADMIN) && ! $actor->isAdmin()) {
            throw ValidationException::withMessages([
                'role_ids' => 'Only administrators can assign the admin role.',
            ]);
        }

        if ($target->id === $actor->id && ! $roles->contains('name', Role::ADMIN) && $actor->isAdmin()) {
            throw ValidationException::withMessages([
                'role_ids' => 'Administrators cannot remove their own admin role.',
            ]);
        }
    }

    public function assertCanChangeRolePermissions(User $actor, Role $role, array $permissionIds): void
    {
        if ($role->name === Role::ADMIN && ! $actor->isAdmin()) {
            throw ValidationException::withMessages([
                'role' => 'Only administrators can modify admin role permissions.',
            ]);
        }

        $criticalCount = Permission::whereIn('id', $permissionIds)
            ->whereIn('name', self::CRITICAL_PERMISSIONS)
            ->count();

        if ($criticalCount > 0 && ! $actor->isAdmin()) {
            throw ValidationException::withMessages([
                'permission_ids' => 'Only administrators can grant critical security permissions.',
            ]);
        }
    }

    public function assertCanGrantPermission(User $actor, Permission $permission): void
    {
        if (in_array($permission->name, self::CRITICAL_PERMISSIONS, true) && ! $actor->isAdmin()) {
            throw ValidationException::withMessages([
                'permission_id' => 'Only administrators can grant critical security permissions.',
            ]);
        }
    }
}
