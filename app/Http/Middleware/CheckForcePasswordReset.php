<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckForcePasswordReset
{
    /**
     * If the authenticated user has force_password_reset = true, redirect them
     * to the forced change page unless they are already there.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->force_password_reset) {
            // Allow the forced-change routes and logout so the user is never trapped
            $allowedRoutes = [
                'auth.force-password-change',
                'auth.force-password-change.submit',
                'logout',
            ];

            if (! in_array($request->route()?->getName(), $allowedRoutes, true)) {
                return redirect()->route('auth.force-password-change');
            }
        }

        return $next($request);
    }
}
