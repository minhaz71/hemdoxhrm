<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class RoleService
{
    public function assignRole(User $user, string $role): void
    {
        $user->assignRole($role);
    }

    public function removeRole(User $user, string $role): void
    {
        $user->removeRole($role);
    }

    public function syncRoles(User $user, array $roles): void
    {
        $user->syncRoles($roles);
    }

    public function assignDefaultRole(User $user): void
    {
        if ($user->roles()->count() === 0) {
            $user->assignRole(Role::EMPLOYEE);
        }
    }

    public function getAllRoles(): Collection
    {
        return Role::orderBy('id')->get();
    }

    public function getUsersByRole(string $role): Collection
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', $role))
            ->with('roles')
            ->get();
    }
}
