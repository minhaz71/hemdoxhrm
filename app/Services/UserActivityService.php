<?php

namespace App\Services;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Http\Request;

class UserActivityService
{
    // ── Public API ────────────────────────────────────────────────

    public function recordLogin(User $user, Request $request): void
    {
        $this->record('login', $request, $user);

        // Update last_active_at immediately on login
        $user->timestamps = false;
        $user->update(['last_active_at' => now()]);
        $user->timestamps = true;
    }

    public function recordLogout(User $user, Request $request): void
    {
        $this->record('logout', $request, $user);
    }

    public function recordFailed(string $identifier, Request $request): void
    {
        LoginActivity::create(array_merge(
            $this->parseRequest($request),
            [
                'user_id'    => null,
                'event'      => 'failed',
                'identifier' => $identifier,
                'created_at' => now(),
            ]
        ));
    }

    /**
     * Update last_active_at for the authenticated user — called by middleware.
     * Throttled via session so the DB is only written at most once per 5 minutes.
     */
    public function touchLastActive(User $user, Request $request): void
    {
        $key = 'last_active_touched_' . $user->id;

        if ($request->session()->has($key)) {
            return;
        }

        $user->timestamps = false;
        $user->update(['last_active_at' => now()]);
        $user->timestamps = true;

        // Mark as touched for 5 minutes
        $request->session()->put($key, now()->timestamp);
        $request->session()->put("_flash.old", array_merge($request->session()->get("_flash.old", []), [$key]));
    }

    // ── Private helpers ───────────────────────────────────────────

    private function record(string $event, Request $request, User $user): void
    {
        LoginActivity::create(array_merge(
            $this->parseRequest($request),
            [
                'user_id'    => $user->id,
                'event'      => $event,
                'identifier' => $user->email,
                'created_at' => now(),
            ]
        ));
    }

    private function parseRequest(Request $request): array
    {
        $ua      = $request->userAgent() ?? '';
        $parsed  = $this->parseUserAgent($ua);

        return [
            'ip_address'      => $request->ip(),
            'user_agent'      => substr($ua, 0, 500),
            'browser'         => $parsed['browser'] ?? 'Unknown',
            'browser_version' => $parsed['browser_version'] ?? '',
            'device'          => $parsed['device'] ?? 'Desktop',
            'os'              => $parsed['os'] ?? 'Unknown',
            'os_version'      => $parsed['os_version'] ?? '',
            'session_id'      => $request->hasSession() ? substr($request->session()->getId(), 0, 40) : null,
        ];
    }

    /**
     * Parse a User-Agent string into browser / device / OS components.
     * Pure regex — no extra dependencies required.
     */
    public function parseUserAgent(string $ua): array
    {
        $browser        = 'Unknown';
        $browserVersion = '';
        $os             = 'Unknown';
        $osVersion      = '';
        $device         = 'Desktop';

        // ── Device ───────────────────────────────────────────────
        if (preg_match('/iPhone|Windows Phone|BB10|BlackBerry(?!.*Tablet)/i', $ua)) {
            $device = 'Mobile';
        } elseif (preg_match('/Android/i', $ua) && preg_match('/Mobile/i', $ua)) {
            $device = 'Mobile';
        } elseif (preg_match('/iPad|Android(?!.*Mobile)|Kindle|Tablet|PlayBook/i', $ua)) {
            $device = 'Tablet';
        }

        // ── OS ────────────────────────────────────────────────────
        $osPlatterns = [
            '/Windows NT 11\.0/i'        => ['Windows', '11'],
            '/Windows NT 10\.0/i'        => ['Windows', '10'],
            '/Windows NT 6\.3/i'         => ['Windows', '8.1'],
            '/Windows NT 6\.2/i'         => ['Windows', '8'],
            '/Windows NT 6\.1/i'         => ['Windows', '7'],
            '/Windows NT 6\.0/i'         => ['Windows', 'Vista'],
            '/Windows NT 5\.1/i'         => ['Windows', 'XP'],
            '/Windows/i'                 => ['Windows', ''],
            '/iPhone OS ([0-9_]+)/i'     => ['iOS', null],   // version captured below
            '/iPad.*OS ([0-9_]+)/i'      => ['iPadOS', null],
            '/Android ([0-9\.]+)/i'      => ['Android', null],
            '/Mac OS X ([0-9_\.]+)/i'    => ['macOS', null],
            '/Mac OS X/i'                => ['macOS', ''],
            '/CrOS/i'                    => ['Chrome OS', ''],
            '/Ubuntu/i'                  => ['Ubuntu', ''],
            '/Fedora/i'                  => ['Fedora', ''],
            '/Linux/i'                   => ['Linux', ''],
        ];

        foreach ($osPlatterns as $pattern => $info) {
            if (preg_match($pattern, $ua, $m)) {
                $os = $info[0];
                if ($info[1] === null && isset($m[1])) {
                    $osVersion = str_replace('_', '.', $m[1]);
                } else {
                    $osVersion = $info[1] ?? '';
                }
                break;
            }
        }

        // ── Browser ───────────────────────────────────────────────
        // Order matters: check more-specific tokens before generic ones
        $browserPatterns = [
            '/Edg\/([0-9\.]+)/i'           => 'Edge',
            '/EdgA\/([0-9\.]+)/i'          => 'Edge (Android)',
            '/OPR\/([0-9\.]+)/i'           => 'Opera',
            '/Opera\/([0-9\.]+)/i'         => 'Opera',
            '/SamsungBrowser\/([0-9\.]+)/i'=> 'Samsung Browser',
            '/UCBrowser\/([0-9\.]+)/i'     => 'UC Browser',
            '/YaBrowser\/([0-9\.]+)/i'     => 'Yandex Browser',
            '/Brave/i'                     => 'Brave',       // no version in token
            '/CriOS\/([0-9\.]+)/i'         => 'Chrome (iOS)',
            '/FxiOS\/([0-9\.]+)/i'         => 'Firefox (iOS)',
            '/Chrome\/([0-9\.]+)/i'        => 'Chrome',
            '/Firefox\/([0-9\.]+)/i'       => 'Firefox',
            '/Safari\/([0-9\.]+)/i'        => 'Safari',
            '/MSIE ([0-9\.]+)/i'           => 'Internet Explorer',
            '/Trident\/.*rv:([0-9\.]+)/i'  => 'Internet Explorer',
        ];

        foreach ($browserPatterns as $pattern => $name) {
            if (preg_match($pattern, $ua, $m)) {
                $browser = $name;
                // For Brave there's no version token in the UA
                $browserVersion = isset($m[1]) ? explode('.', $m[1])[0] : '';
                // Special: Safari UA also has Chrome token — handled by ordering above
                break;
            }
        }

        // Safari version is in "Version/x.x"
        if ($browser === 'Safari' && preg_match('/Version\/([0-9\.]+)/i', $ua, $m)) {
            $browserVersion = explode('.', $m[1])[0];
        }

        return [
            'browser'         => $browser,
            'browser_version' => $browserVersion,
            'device'          => $device,
            'os'              => $os,
            'os_version'      => $osVersion,
        ];
    }
}
