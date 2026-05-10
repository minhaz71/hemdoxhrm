<?php

namespace App\Http\Middleware;

use App\Services\SecurityAuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PreventRoleEscalation
 *
 * Intercepts any request that would assign a role to a user and enforces:
 *   1. A non-admin cannot grant admin role to anyone.
 *   2. A user cannot modify their own roles at all.
 *   3. All attempted escalations are written to the security audit log.
 *
 * Apply to RBAC assignment routes via: middleware('prevent.escalation')
 */
class PreventRoleEscalation
{
    public function __construct(private readonly SecurityAuditService $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();

        if (!$actor) {
            return $next($request);
        }

        // ── 1. Self-modification guard ─────────────────────────────────
        $targetUserId = $this->resolveTargetUserId($request);
        if ($targetUserId && (int) $targetUserId === $actor->id) {
            $this->audit->record(
                SecurityAuditService::EVENT_ROLE_ESCALATION_ATTEMPT,
                $request,
                $actor,
                null,
                [
                    'reason'      => 'self_modification',
                    'target_user' => $actor->id,
                    'roles_requested' => $this->resolveRoles($request),
                ],
                'warning'
            );

            return response()->json([
                'message' => 'You cannot modify your own roles.',
            ], Response::HTTP_FORBIDDEN);
        }

        // ── 2. Non-admin cannot grant admin role ───────────────────────
        if (!$actor->hasRole('admin')) {
            $requestedRoles = $this->resolveRoles($request);
            $adminRequested = collect($requestedRoles)
                ->map(fn ($r) => strtolower((string) $r))
                ->contains('admin');

            if ($adminRequested) {
                $this->audit->record(
                    SecurityAuditService::EVENT_ROLE_ESCALATION_ATTEMPT,
                    $request,
                    $actor,
                    null,
                    [
                        'reason'          => 'unauthorized_admin_grant',
                        'target_user'     => $targetUserId,
                        'roles_requested' => $requestedRoles,
                    ],
                    'critical'
                );

                return response()->json([
                    'message' => 'You do not have permission to assign the admin role.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    /**
     * Try to resolve the target user ID from common route/input patterns.
     */
    private function resolveTargetUserId(Request $request): int|string|null
    {
        // Route parameter: /users/{user}/roles, /admin/users/{user}/assign-role
        foreach (['user', 'userId', 'user_id', 'id'] as $key) {
            $val = $request->route($key);
            if ($val !== null) {
                return is_object($val) ? $val->id : $val;
            }
        }

        // Request body: { user_id: 5 } or { userId: 5 }
        return $request->input('user_id') ?? $request->input('userId');
    }

    /**
     * Try to resolve the requested roles from common input patterns.
     *
     * @return array<string|int>
     */
    private function resolveRoles(Request $request): array
    {
        $roles = $request->input('roles')
            ?? $request->input('role')
            ?? [];

        return is_array($roles) ? $roles : [$roles];
    }
}
