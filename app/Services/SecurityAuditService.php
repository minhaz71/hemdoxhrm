<?php

namespace App\Services;

use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SecurityAuditService
{
    // ── Event constants ────────────────────────────────────────────────

    // Auth events
    public const EVENT_LOGIN_SUCCESS          = 'auth.login.success';
    public const EVENT_LOGIN_FAILED           = 'auth.login.failed';
    public const EVENT_LOGIN_THROTTLED        = 'auth.login.throttled';
    public const EVENT_LOGOUT                 = 'auth.logout';
    public const EVENT_PASSWORD_RESET         = 'auth.password.reset';
    public const EVENT_PASSWORD_CHANGED       = 'auth.password.changed';

    // Session events
    public const EVENT_SESSION_INVALIDATED    = 'session.invalidated';
    public const EVENT_IMPERSONATION_START    = 'session.impersonation.start';
    public const EVENT_IMPERSONATION_END      = 'session.impersonation.end';

    // Account status events
    public const EVENT_ACCOUNT_SUSPENDED      = 'account.suspended';
    public const EVENT_ACCOUNT_ACTIVATED      = 'account.activated';
    public const EVENT_ACCOUNT_TERMINATED     = 'account.terminated';
    public const EVENT_FORCE_LOGOUT           = 'account.force_logout';

    // Role / permission events
    public const EVENT_ROLE_ASSIGNED          = 'rbac.role.assigned';
    public const EVENT_ROLE_REVOKED           = 'rbac.role.revoked';
    public const EVENT_ROLE_ESCALATION_ATTEMPT = 'rbac.escalation.attempt';
    public const EVENT_PERMISSION_DENIED      = 'rbac.permission.denied';

    // Invite events
    public const EVENT_INVITE_CREATED         = 'invite.created';
    public const EVENT_INVITE_VIEWED          = 'invite.viewed';
    public const EVENT_INVITE_SUBMITTED       = 'invite.submitted';
    public const EVENT_INVITE_ACCEPTED        = 'invite.accepted';
    public const EVENT_INVITE_REVOKED         = 'invite.revoked';
    public const EVENT_INVITE_ABUSE           = 'invite.abuse.detected';
    public const EVENT_INVITE_EXPIRED         = 'invite.expired';

    // Settings events
    public const EVENT_SETTINGS_UPDATED       = 'settings.updated';
    public const EVENT_SMTP_TESTED            = 'settings.smtp.tested';

    // Data events
    public const EVENT_PAYROLL_APPROVED       = 'payroll.approved';
    public const EVENT_PAYROLL_REJECTED       = 'payroll.rejected';
    public const EVENT_LEAVE_APPROVED         = 'leave.approved';
    public const EVENT_LEAVE_REJECTED         = 'leave.rejected';
    public const EVENT_ATTENDANCE_MODIFIED    = 'attendance.modified';

    // ── Core record method ─────────────────────────────────────────────

    public function record(
        string $event,
        ?Request $request = null,
        ?User $actor = null,
        ?Model $subject = null,
        array $metadata = [],
        string $severity = 'info',
    ): void {
        try {
            SecurityAuditLog::create([
                'actor_id'     => $actor?->id,
                'event'        => $event,
                'severity'     => $severity,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id'   => $subject?->getKey(),
                'ip_address'   => $request?->ip(),
                'user_agent'   => $request ? substr((string) $request->userAgent(), 0, 500) : null,
                'metadata'     => $metadata,
            ]);
        } catch (\Throwable) {
            // Security logging must never take down auth or approval flows.
        }
    }

    // ── Severity shortcut methods ──────────────────────────────────────

    public function critical(
        string $event,
        ?Request $request = null,
        ?User $actor = null,
        ?Model $subject = null,
        array $metadata = [],
    ): void {
        $this->record($event, $request, $actor, $subject, $metadata, 'critical');
    }

    public function warning(
        string $event,
        ?Request $request = null,
        ?User $actor = null,
        ?Model $subject = null,
        array $metadata = [],
    ): void {
        $this->record($event, $request, $actor, $subject, $metadata, 'warning');
    }

    public function info(
        string $event,
        ?Request $request = null,
        ?User $actor = null,
        ?Model $subject = null,
        array $metadata = [],
    ): void {
        $this->record($event, $request, $actor, $subject, $metadata, 'info');
    }

    // ── Convenience named methods ──────────────────────────────────────

    /**
     * Record a throttled login attempt (brute-force protection event).
     */
    public function loginThrottled(Request $request, string $email): void
    {
        $this->warning(self::EVENT_LOGIN_THROTTLED, $request, null, null, [
            'email'    => $email,
            'attempts' => 'rate_limit_hit',
        ]);
    }

    /**
     * Record a failed login attempt.
     */
    public function loginFailed(Request $request, string $email): void
    {
        $this->warning(self::EVENT_LOGIN_FAILED, $request, null, null, [
            'email' => $email,
        ]);
    }

    /**
     * Record invite abuse detection.
     */
    public function inviteAbuse(Request $request, string $token, string $reason): void
    {
        $this->critical(self::EVENT_INVITE_ABUSE, $request, null, null, [
            'token_prefix' => substr($token, 0, 8) . '…',
            'reason'       => $reason,
        ]);
    }
}
