<?php

namespace App\Http\Controllers;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Admin activity dashboard — all users.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['user_id', 'event', 'ip', 'search', 'date_from', 'date_to']);

        $logs = LoginActivity::with('user')
            ->when($filters['user_id'] ?? null, fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when($filters['event']   ?? null, fn ($q) => $q->where('event',   $filters['event']))
            ->when($filters['ip']      ?? null, fn ($q) => $q->where('ip_address', 'like', "%{$filters['ip']}%"))
            ->when($filters['search']  ?? null, fn ($q) => $q->whereHas('user', fn ($u) =>
                $u->where('name',  'like', "%{$filters['search']}%")
                  ->orWhere('email','like', "%{$filters['search']}%")
            ))
            ->when($filters['date_from'] ?? null, fn ($q) => $q->where('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to']   ?? null, fn ($q) => $q->where('created_at', '<=', $filters['date_to'] . ' 23:59:59'))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        // Summary stats (last 30 days)
        $stats = [
            'logins'  => LoginActivity::logins()->recent(30)->count(),
            'logouts' => LoginActivity::logouts()->recent(30)->count(),
            'failed'  => LoginActivity::failed()->recent(30)->count(),
            'unique'  => LoginActivity::logins()->recent(30)->distinct('user_id')->count('user_id'),
        ];

        // Failed attempts grouped by IP (top 10, last 7 days)
        $topFailedIps = LoginActivity::failed()
            ->recent(7)
            ->selectRaw('ip_address, COUNT(*) as attempts')
            ->groupBy('ip_address')
            ->orderByDesc('attempts')
            ->limit(10)
            ->get();

        // Users list for filter dropdown
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('activity.index', compact('logs', 'stats', 'topFailedIps', 'users', 'filters'));
    }

    /**
     * Activity history for a single user.
     */
    public function user(Request $request, User $user)
    {
        $logs = $user->loginActivities()
            ->when($request->input('event'), fn ($q) => $q->where('event', $request->input('event')))
            ->paginate(30)
            ->withQueryString();

        $lastLogin  = $user->loginActivities()->where('event', 'login')->first();
        $lastLogout = $user->loginActivities()->where('event', 'logout')->first();
        $failCount  = $user->loginActivities()->where('event', 'failed')->recent(30)->count();

        return view('activity.user', compact('user', 'logs', 'lastLogin', 'lastLogout', 'failCount'));
    }

    /**
     * AJAX: real-time activity stats (used by dashboard widget).
     */
    public function stats()
    {
        return response()->json([
            'logins_today'  => LoginActivity::logins()->whereDate('created_at', today())->count(),
            'failed_today'  => LoginActivity::failed()->whereDate('created_at', today())->count(),
            'active_now'    => User::where('last_active_at', '>=', now()->subMinutes(15))->count(),
        ]);
    }
}
