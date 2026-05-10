<?php

namespace App\Services;

use App\Models\LoginActivity;
use App\Models\Role;
use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminUserManagementService
{
    public function __construct(
        private readonly SecurityAuditService $audit,
        private readonly AccessControlService $accessControl,
    ) {}

    public function suspend(User $target, User $actor, Request $request): void
    {
        $this->guardTarget($target, $actor);

        if ($target->isAdmin()) {
            throw ValidationException::withMessages(['user' => 'Admin users cannot be suspended from this screen.']);
        }

        $target->update(['status' => 'suspended']);
        $this->invalidateSessions($target, $actor, $request, 'user.suspended');
    }

    public function activate(User $target, User $actor, Request $request): void
    {
        $this->guardTarget($target, $actor);

        $target->update(['status' => 'active']);
        $this->audit->record('user.activated', $request, $actor, $target, [], 'warning');
    }

    public function forceLogout(User $target, User $actor, Request $request): int
    {
        $this->guardTarget($target, $actor);

        return $this->invalidateSessions($target, $actor, $request, 'user.force_logout');
    }

    public function impersonate(User $target, User $actor, Request $request): void
    {
        $this->guardTarget($target, $actor);

        if (! $actor->isAdmin()) {
            throw ValidationException::withMessages(['user' => 'Only administrators can impersonate users.']);
        }

        if ($target->isAdmin()) {
            throw ValidationException::withMessages(['user' => 'Administrators cannot impersonate another administrator.']);
        }

        if ($target->status !== 'active' || in_array($target->approval_status, ['pending', 'correction_required', 'rejected'], true)) {
            throw ValidationException::withMessages(['user' => 'Only active approved users can be impersonated.']);
        }

        $request->session()->put('impersonator_id', $actor->id);
        $request->session()->put('impersonated_user_id', $target->id);
        $request->session()->put('impersonation_started_at', now()->toIso8601String());

        Auth::login($target);
        $request->session()->regenerate();

        $this->audit->record('user.impersonation_started', $request, $actor, $target, [], 'critical');
    }

    public function stopImpersonating(Request $request): void
    {
        $actorId = $request->session()->get('impersonator_id');
        $targetId = Auth::id();

        if (! $actorId) {
            return;
        }

        $actor = User::findOrFail($actorId);
        $target = $targetId ? User::find($targetId) : null;

        Auth::login($actor);
        $request->session()->forget(['impersonator_id', 'impersonated_user_id', 'impersonation_started_at']);
        $request->session()->regenerate();

        $this->audit->record('user.impersonation_stopped', $request, $actor, $target);
    }

    public function syncRoles(User $target, User $actor, Request $request, array $roleIds): void
    {
        $this->accessControl->assertCanAssignRoles($actor, $target, $roleIds);

        $target->roles()->sync($roleIds);
        $target->load('roles');

        $this->audit->record('user.roles_changed', $request, $actor, $target, [
            'role_ids' => $roleIds,
            'roles' => $target->roles->pluck('name')->values()->all(),
        ], 'warning');
    }

    public function loginHistory(User $target, int $limit = 50)
    {
        return LoginActivity::where('user_id', $target->id)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function auditHistory(User $target, int $limit = 50)
    {
        return SecurityAuditLog::with('actor')
            ->where('subject_type', User::class)
            ->where('subject_id', $target->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function activeSessionCount(User $target): int
    {
        return DB::table('sessions')->where('user_id', $target->id)->count();
    }

    private function invalidateSessions(User $target, User $actor, Request $request, string $event): int
    {
        $count = DB::table('sessions')->where('user_id', $target->id)->delete();
        $target->forceFill(['remember_token' => null])->save();

        $this->audit->record($event, $request, $actor, $target, [
            'sessions_invalidated' => $count,
        ], 'critical');

        return $count;
    }

    private function guardTarget(User $target, User $actor): void
    {
        if ($target->id === $actor->id) {
            throw ValidationException::withMessages(['user' => 'You cannot perform this action on your own account.']);
        }
    }
}
