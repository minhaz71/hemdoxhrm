<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /** Per-request cache: userId → ['perm.name' => true|false, ...] */
    private array $resolved = [];

    /** Tracks which user IDs have been fully bulk-loaded. */
    private array $loaded = [];

    // ── Core check ────────────────────────────────────────────────

    public function check(User $user, string $permission): bool
    {
        if ($user->hasRole('admin')) return true;

        $this->loadAll($user);

        return $this->resolved[$user->id][$permission] ?? false;
    }

    /**
     * Load ALL effective permissions for a user in two queries (direct overrides
     * + role permissions) and store in $this->resolved[$userId].  Subsequent
     * check() calls are O(1) array lookups.
     */
    private function loadAll(User $user): void
    {
        if (isset($this->loaded[$user->id])) {
            return;
        }

        // Query 1: direct user overrides (granted or denied)
        $overrides = $user->directPermissions()->get();
        $grants    = $overrides->where('pivot.granted', true)->pluck('name')->flip()->map(fn () => true);
        $denies    = $overrides->where('pivot.granted', false)->pluck('name')->flip()->map(fn () => false);

        // Query 2: all role-based permissions in one eager load
        $fromRoles = $user->roles()->with('permissions')->get()
            ->flatMap(fn ($r) => $r->permissions->pluck('name'))
            ->unique()
            ->flip()
            ->map(fn () => true);

        // Merge: role grants first, then user-level overrides take priority
        $merged = $fromRoles->merge($grants)->merge($denies);

        $this->resolved[$user->id] = $merged->toArray();
        $this->loaded[$user->id]   = true;
    }

    // ── Cache management ──────────────────────────────────────────

    public function flush(int $userId): void
    {
        unset($this->resolved[$userId], $this->loaded[$userId]);
    }

    public function flushAll(): void
    {
        $this->resolved = [];
        Cache::forget('rbac_permission_names');
    }

    // ── Role permission management ────────────────────────────────

    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        $role->permissions()->sync($permissionIds);
        $this->flushAll();
    }

    public function getRolePermissionIds(Role $role): array
    {
        return $role->permissions()->pluck('permissions.id')->toArray();
    }

    // ── User permission overrides ─────────────────────────────────

    public function grantToUser(User $user, Permission $permission): void
    {
        \DB::table('user_permissions')->updateOrInsert(
            ['user_id' => $user->id, 'permission_id' => $permission->id],
            ['granted' => true, 'updated_at' => now(), 'created_at' => now()]
        );
        $this->flush($user->id);
    }

    public function denyFromUser(User $user, Permission $permission): void
    {
        \DB::table('user_permissions')->updateOrInsert(
            ['user_id' => $user->id, 'permission_id' => $permission->id],
            ['granted' => false, 'updated_at' => now(), 'created_at' => now()]
        );
        $this->flush($user->id);
    }

    public function removeUserOverride(User $user, Permission $permission): void
    {
        \DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->delete();
        $this->flush($user->id);
    }

    public function getUserOverrides(User $user): Collection
    {
        return $user->directPermissions()->get();
    }

    // ── Query helpers ─────────────────────────────────────────────

    public function allGroupedByModule(): Collection
    {
        return Permission::orderBy('module')->orderBy('label')->get()->groupBy('module');
    }

    public function allNames(): array
    {
        return Cache::remember('rbac_permission_names', 3600, function () {
            return Permission::pluck('name')->toArray();
        });
    }

    public function getUserEffectivePermissions(User $user): array
    {
        if ($user->hasRole('admin')) {
            return Permission::pluck('name')->toArray();
        }

        $fromRoles = $user->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn ($r) => $r->permissions->pluck('name'))
            ->unique()
            ->values()
            ->toArray();

        $overrides = $user->directPermissions()->get();
        $grants    = $overrides->where('pivot.granted', true)->pluck('name')->toArray();
        $denies    = $overrides->where('pivot.granted', false)->pluck('name')->toArray();

        return array_values(array_diff(
            array_unique(array_merge($fromRoles, $grants)),
            $denies
        ));
    }
}
