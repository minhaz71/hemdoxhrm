<?php

namespace App\Http\Middleware;

use App\Services\SecurityAuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountApproved
{
    public function __construct(private readonly SecurityAuditService $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $request->routeIs('logout') || $request->routeIs('impersonation.stop')) {
            return $next($request);
        }

        if (in_array($user->approval_status, ['pending', 'correction_required', 'rejected'], true)) {
            $this->audit->record('auth.blocked_unapproved_session', $request, $user, $user, [
                'approval_status' => $user->approval_status,
            ], 'warning');

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is not approved for access.']);
        }

        return $next($request);
    }
}
