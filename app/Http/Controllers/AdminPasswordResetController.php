<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\DirectPasswordResetRequest;
use App\Http\Requests\Auth\ForcePasswordChangeRequest;
use App\Models\User;
use App\Services\AdminPasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AdminPasswordResetController extends Controller
{
    public function __construct(
        private readonly AdminPasswordResetService $service,
    ) {}

    /**
     * Send a password-reset link via Laravel's built-in broker.
     */
    public function sendLink(Request $request, User $user)
    {
        $status = $this->service->sendResetLink($user, auth()->user(), $request->ip());

        $message = $status === Password::RESET_LINK_SENT
            ? "Password reset link sent to {$user->email}."
            : 'Could not send reset link right now. Please try again later.';

        $type = $status === Password::RESET_LINK_SENT ? 'success' : 'error';

        return back()->with($type, $message);
    }

    /**
     * Directly reset a user's password (admin sets a new one).
     */
    public function resetDirect(DirectPasswordResetRequest $request, User $user)
    {
        $this->service->resetDirect($user, auth()->user(), $request->password, $request->ip());

        return back()->with('success', "Password for {$user->name} has been reset. They will be required to change it on next login.");
    }

    /**
     * Enable force-reset flag so the user must change password on next login.
     */
    public function forceReset(Request $request, User $user)
    {
        $this->service->forceNextLogin($user, auth()->user(), $request->ip());

        return back()->with('success', "{$user->name} will be required to change their password on next login.");
    }

    /**
     * Clear force-reset flag.
     */
    public function clearForceReset(Request $request, User $user)
    {
        $this->service->clearForceReset($user, auth()->user(), $request->ip());

        return back()->with('success', "Force password-reset flag cleared for {$user->name}.");
    }

    /**
     * Show audit log for a specific user (AJAX or full page).
     */
    public function logs(Request $request, User $user)
    {
        $logs = $user->passwordResetLogs()->with('admin')->get();

        if ($request->wantsJson()) {
            return response()->json($logs->map(fn($l) => [
                'id'           => $l->id,
                'action'       => $l->action,
                'action_label' => $l->action_label,
                'action_icon'  => $l->action_icon,
                'action_color' => $l->action_color,
                'admin_name'   => $l->admin?->name ?? 'System',
                'ip_address'   => $l->ip_address,
                'note'         => $l->note,
                'created_at'   => $l->created_at?->format('d M Y H:i'),
            ]));
        }

        return view('admin.password-reset.logs', compact('user', 'logs'));
    }

    /**
     * Show the force-change-password form (shown when force_password_reset=true).
     */
    public function showForceChange()
    {
        return view('auth.force-password-change');
    }

    /**
     * Handle the forced password change submission.
     */
    public function handleForceChange(ForcePasswordChangeRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $this->service->completeForcedChange($user, $request->current_password, $request->password);

        return redirect()->route('dashboard')
            ->with('success', 'Password changed successfully. Welcome!');
    }
}
