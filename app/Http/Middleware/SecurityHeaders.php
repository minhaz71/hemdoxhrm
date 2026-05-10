<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Paths where we should NOT add no-cache headers
     * (downloads / exports need normal browser caching).
     */
    private const DOWNLOAD_PATTERNS = [
        'payslips',
        'exports',
        'time-doctor',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ── Core anti-clickjacking / sniffing headers ─────────────────
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // ── Permissions Policy (feature policy) ───────────────────────
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), magnetometer=(), gyroscope=()'
        );

        // ── HSTS (only over HTTPS) ────────────────────────────────────
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // ── Content Security Policy ───────────────────────────────────
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            // unsafe-inline / unsafe-eval needed for Livewire, Alpine, inline scripts
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net https://cdn.jsdelivr.net data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]));

        // ── Remove server fingerprint headers ─────────────────────────
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // ── Cache-control for authenticated HTML pages ────────────────
        if ($this->shouldNoCacheResponse($request, $response)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    /**
     * Returns true when we should force no-caching.
     * We skip downloads/exports so the browser can handle them normally.
     */
    private function shouldNoCacheResponse(Request $request, Response $response): bool
    {
        // Only bother for authenticated users
        if (!auth()->check()) {
            return false;
        }

        // Skip download / export routes
        $path = $request->path();
        foreach (self::DOWNLOAD_PATTERNS as $pattern) {
            if (str_contains($path, $pattern)) {
                return false;
            }
        }

        // Only apply to HTML responses (not JSON APIs, file downloads, etc.)
        $contentType = $response->headers->get('Content-Type', '');
        return str_contains($contentType, 'text/html') || $contentType === '';
    }
}
