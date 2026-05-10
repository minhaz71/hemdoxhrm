<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\PermissionService;
use App\Services\SecurityAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService,
        private readonly AccessControlService $accessControl,
        private readonly SecurityAuditService $audit,
    ) {}

    // ── Role list ─────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $roles       = Role::withCount(['users', 'permissions'])->orderBy('name')->get();
        $permissions = $this->permissionService->allGroupedByModule();

        return view('rbac.roles.index', compact('roles', 'permissions'));
    }

    // ── User access management list ───────────────────────────────

    public function userIndex(Request $request): \Illuminate\View\View
    {
        $search = $request->input('q');

        $users = User::with('roles')
            ->withCount([
                'directPermissions as overrides_count',
                'directPermissions as grants_count' => fn ($q) => $q->where('user_permissions.granted', true),
                'directPermissions as denies_count'  => fn ($q) => $q->where('user_permissions.granted', false),
            ])
            ->when($search, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('rbac.users.index', compact('users', 'roles', 'search'));
    }

    // ── Create role ───────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_\-]+$/', Rule::unique('roles', 'name')],
            'display_name' => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::create($data + ['is_system' => false]);
        $this->audit->record('rbac.role_created', $request, $request->user(), $role, $data);

        return response()->json([
            'success' => true,
            'message' => "Role \"{$role->display_name}\" created.",
            'role'    => $this->formatRole($role),
        ], 201);
    }

    // ── Update role details ───────────────────────────────────────

    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string', 'max:255'],
        ]);

        $role->update($data);
        $this->audit->record('rbac.role_updated', $request, $request->user(), $role, $data);

        return response()->json([
            'success' => true,
            'message' => "Role updated.",
            'role'    => $this->formatRole($role->fresh()),
        ]);
    }

    // ── Delete role ───────────────────────────────────────────────

    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json(['success' => false, 'message' => 'System roles cannot be deleted.'], 422);
        }

        if ($role->users()->exists()) {
            return response()->json(['success' => false, 'message' => 'Remove all users from this role first.'], 422);
        }

        $role->delete();
        $this->audit->record('rbac.role_deleted', request(), request()->user(), $role);

        return response()->json(['success' => true, 'message' => "Role deleted."]);
    }

    // ── Permission matrix for a role ──────────────────────────────

    public function show(Role $role): \Illuminate\View\View
    {
        $permissions        = $this->permissionService->allGroupedByModule();
        $assignedIds        = $this->permissionService->getRolePermissionIds($role);
        $roles              = Role::withCount(['users', 'permissions'])->orderBy('name')->get();

        return view('rbac.roles.show', compact('role', 'permissions', 'assignedIds', 'roles'));
    }

    // ── Sync permissions for a role (AJAX) ────────────────────────

    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'permission_ids'   => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = $data['permission_ids'] ?? [];

        $this->accessControl->assertCanChangeRolePermissions($request->user(), $role, $permissionIds);
        $this->permissionService->syncRolePermissions($role, $permissionIds);
        $this->audit->record('rbac.role_permissions_synced', $request, $request->user(), $role, [
            'permission_ids' => $permissionIds,
        ], 'warning');

        return response()->json([
            'success' => true,
            'message' => "Permissions updated for \"{$role->display_name}\".",
            'count'   => count($data['permission_ids'] ?? []),
        ]);
    }

    // ── User permission overrides ─────────────────────────────────

    public function userPermissions(User $user): \Illuminate\View\View
    {
        $permissions = $this->permissionService->allGroupedByModule();
        $overrides   = $this->permissionService->getUserOverrides($user)->keyBy('id');
        $effective   = $this->permissionService->getUserEffectivePermissions($user);
        $roles       = Role::orderBy('name')->get();
        $userRoles   = $user->roles->pluck('id')->toArray();

        return view('rbac.users.permissions', compact('user', 'permissions', 'overrides', 'effective', 'roles', 'userRoles'));
    }

    public function grantUserPermission(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
        ]);

        $permission = Permission::findOrFail($data['permission_id']);
        $this->accessControl->assertCanGrantPermission($request->user(), $permission);
        $this->permissionService->grantToUser($user, $permission);
        $this->audit->record('rbac.user_permission_granted', $request, $request->user(), $user, [
            'permission' => $permission->name,
        ], 'warning');

        return response()->json(['success' => true, 'message' => "Permission granted."]);
    }

    public function denyUserPermission(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
        ]);

        $permission = Permission::findOrFail($data['permission_id']);
        $this->permissionService->denyFromUser($user, $permission);
        $this->audit->record('rbac.user_permission_denied', $request, $request->user(), $user, [
            'permission' => $permission->name,
        ], 'warning');

        return response()->json(['success' => true, 'message' => "Permission denied."]);
    }

    public function removeUserOverride(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
        ]);

        $permission = Permission::findOrFail($data['permission_id']);
        $this->permissionService->removeUserOverride($user, $permission);
        $this->audit->record('rbac.user_permission_override_removed', $request, $request->user(), $user, [
            'permission' => $permission->name,
        ]);

        return response()->json(['success' => true, 'message' => "Override removed."]);
    }

    public function syncUserRoles(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'role_ids'   => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $roleIds = $data['role_ids'] ?? [];
        $this->accessControl->assertCanAssignRoles($request->user(), $user, $roleIds);

        $user->roles()->sync($roleIds);
        $user->load('roles');
        $this->audit->record('rbac.user_roles_synced', $request, $request->user(), $user, [
            'role_ids' => $roleIds,
            'roles' => $user->roles->pluck('name')->values()->all(),
        ], 'warning');

        return response()->json(['success' => true, 'message' => "Roles updated."]);
    }

    // ── Effective permission checker (JSON) ───────────────────────

    public function effectivePermissions(User $user): JsonResponse
    {
        $effective  = $this->permissionService->getUserEffectivePermissions($user);
        $overrides  = $this->permissionService->getUserOverrides($user);
        $rolePerms  = $user->hasRole('admin')
            ? Permission::pluck('name')->toArray()
            : $user->roles()->with('permissions')->get()
                ->flatMap(fn ($r) => $r->permissions->pluck('name'))->unique()->values()->toArray();

        return response()->json([
            'user'              => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'roles'             => $user->roles->pluck('display_name'),
            'effective'         => array_values($effective),
            'from_roles'        => array_values($rolePerms),
            'overrides_granted' => $overrides->where('pivot.granted', true)->pluck('name')->values(),
            'overrides_denied'  => $overrides->where('pivot.granted', false)->pluck('name')->values(),
            'total_effective'   => count($effective),
        ]);
    }

    // ── Permission CRUD ───────────────────────────────────────────

    public function storePermission(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_\.\-]+$/', Rule::unique('permissions', 'name')],
            'label'       => ['required', 'string', 'max:150'],
            'module'      => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $permission = Permission::create($data + ['is_system' => false]);
        $this->permissionService->flushAll();

        return response()->json([
            'success'    => true,
            'message'    => "Permission \"{$permission->name}\" created.",
            'permission' => $permission,
        ], 201);
    }

    public function destroyPermission(Permission $permission): JsonResponse
    {
        if ($permission->is_system) {
            return response()->json(['success' => false, 'message' => 'System permissions cannot be deleted.'], 422);
        }

        $permission->delete();
        $this->permissionService->flushAll();

        return response()->json(['success' => true, 'message' => "Permission deleted."]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function formatRole(Role $role): array
    {
        // Prefer already-loaded withCount() values; fall back to a live query
        // only when the role was not fetched with withCount (e.g. after store/update).
        $usersCount       = isset($role->users_count)
            ? (int) $role->users_count
            : $role->users()->count();
        $permissionsCount = isset($role->permissions_count)
            ? (int) $role->permissions_count
            : $role->permissions()->count();

        return [
            'id'               => $role->id,
            'name'             => $role->name,
            'display_name'     => $role->display_name,
            'description'      => $role->description,
            'is_system'        => $role->is_system,
            'users_count'      => $usersCount,
            'permissions_count'=> $permissionsCount,
        ];
    }
}
