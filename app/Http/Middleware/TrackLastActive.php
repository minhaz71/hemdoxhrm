<?php

namespace App\Http\Middleware;

use App\Services\UserActivityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastActive
{
    public function __construct(private readonly UserActivityService $activityService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track authenticated, non-API requests
        if ($request->user() && $request->hasSession()) {
            // Use session to throttle: only write to DB every 5 minutes
            $sessionKey = '_last_active_ts';
            $lastTs     = $request->session()->get($sessionKey, 0);

            if ((time() - $lastTs) >= 300) {
                $this->activityService->touchLastActive($request->user(), $request);
                $request->session()->put($sessionKey, time());
            }
        }

        return $response;
    }
}
