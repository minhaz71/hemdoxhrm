<?php

namespace App\Services;

use App\Models\AdminPasswordResetLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AdminPasswordResetService
{
    public function __construct(private readonly SecurityAuditService $audit) {}

    /**
     * Send a standard Laravel password-reset link to the target user's email.
     * Uses the built-in password broker so the token is stored securely in
     * password_reset_tokens and the official reset flow is preserved.
     */
    public function sendResetLink(User $target, User $admin, string $ip): string
    {
        // Use the default Laravel password broker
        $status = Password::broker()->sendResetLink(['email' => $target->email]);

        $this->log($admin, $target, 'reset_link_sent', $ip,
            "Reset link sent to {$target->email}. Status: {$status}");

        $this->audit->record('password.reset_link_sent', request(), $admin, $target, [
            'status' => $status,
        ], 'warning');

        return $status; // Password::RESET_LINK_SENT or Password::RESET_THROTTLED
    }

    /**
     * Directly set a new password chosen by the admin.
     * Marks force_password_reset = true so the user must change it on first login.
     */
    public function resetDirect(User $target, User $admin, string $newPassword, string $ip): void
    {
        $target->update([
            'password'             => Hash::make($newPassword),
            'force_password_reset' => true,
        ]);

        // Invalidate all existing sessions / remember tokens
        $target->forceFill(['remember_token' => null])->save();

        $this->log($admin, $target, 'password_reset', $ip,
            'Password reset directly by admin. Force-reset flag enabled.');

        $this->audit->record('password.direct_reset', request(), $admin, $target, [], 'critical');
    }

    /**
     * Flag the user so they must change their password at next login.
     */
    public function forceNextLogin(User $target, User $admin, string $ip): void
    {
        $target->update(['force_password_reset' => true]);

        $this->log($admin, $target, 'force_reset_enabled', $ip,
            'Force password-reset flag enabled by admin.');

        $this->audit->record('password.force_reset_enabled', request(), $admin, $target, [], 'warning');
    }

    /**
     * Clear the force-reset flag (admin undo).
     */
    public function clearForceReset(User $target, User $admin, string $ip): void
    {
        $target->update(['force_password_reset' => false]);

        $this->log($admin, $target, 'force_reset_disabled', $ip,
            'Force password-reset flag cleared by admin.');

        $this->audit->record('password.force_reset_disabled', request(), $admin, $target);
    }

    public function completeForcedChange(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        $user->update([
            'password'             => $newPassword,
            'force_password_reset' => false,
        ]);

        $this->audit->record('password.forced_change_completed', request(), $user, $user);
    }

    // ── Internal ──────────────────────────────────────────────────

    private function log(User $admin, User $target, string $action, string $ip, ?string $note = null): void
    {
        AdminPasswordResetLog::create([
            'admin_id'       => $admin->id,
            'target_user_id' => $target->id,
            'action'         => $action,
            'ip_address'     => $ip,
            'note'           => $note,
            'created_at'     => now(),
        ]);
    }
}
