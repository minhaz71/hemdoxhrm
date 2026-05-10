<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockTerminated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && isset($request->user()->status)
            && in_array($request->user()->status, ['terminated', 'suspended'], true)) {
            $status = $request->user()->status;

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $status === 'suspended'
                ? 'Your account has been suspended.'
                : 'Your account has been terminated.';

            return redirect()->route('login')
                ->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
