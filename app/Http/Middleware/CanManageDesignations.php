<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanManageDesignations
{
    public function __construct(private readonly SettingService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Admins always have access
        if ($user->isAdmin()) {
            return $next($request);
        }

        // HR gets access only when the admin has enabled it
        if ($user->isHR() && $this->settings->get('designation_hr_access', false)) {
            return $next($request);
        }

        abort(403, 'You are not authorized to manage designations.');
    }
}
