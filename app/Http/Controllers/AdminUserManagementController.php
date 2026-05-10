<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminUserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminUserManagementController extends Controller
{
    public function __construct(private readonly AdminUserManagementService $users) {}

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->users->suspend($user, $request->user(), $request);

        return back()->with('success', "{$user->name} has been suspended and logged out.");
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $this->users->activate($user, $request->user(), $request);

        return back()->with('success', "{$user->name} has been activated.");
    }

    public function forceLogout(Request $request, User $user): RedirectResponse
    {
        $count = $this->users->forceLogout($user, $request->user(), $request);

        return back()->with('success', "{$user->name} was logged out from {$count} session(s).");
    }

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        $this->users->impersonate($user, $request->user(), $request);

        return redirect()->route('dashboard')->with('success', "You are now impersonating {$user->name}.");
    }

    public function stopImpersonating(Request $request): RedirectResponse
    {
        $this->users->stopImpersonating($request);

        return redirect()->route('rbac.users.index')->with('success', 'Impersonation ended.');
    }

    public function loginHistory(User $user): JsonResponse
    {
        return response()->json($this->users->loginHistory($user)->map(fn ($row) => [
            'event' => $row->event_label,
            'event_raw' => $row->event,
            'browser' => trim(($row->browser ?? 'Unknown') . ' ' . ($row->browser_version ?? '')),
            'device' => $row->device,
            'os' => trim(($row->os ?? 'Unknown') . ' ' . ($row->os_version ?? '')),
            'ip_address' => $row->ip_address,
            'created_at' => $row->created_at?->format('d M Y H:i'),
        ]));
    }

    public function auditHistory(User $user): JsonResponse
    {
        return response()->json($this->users->auditHistory($user)->map(fn ($row) => [
            'event' => $row->event,
            'severity' => $row->severity,
            'actor' => $row->actor?->name ?? 'System',
            'ip_address' => $row->ip_address,
            'metadata' => $row->metadata ?? [],
            'created_at' => $row->created_at?->format('d M Y H:i'),
        ]));
    }
}
