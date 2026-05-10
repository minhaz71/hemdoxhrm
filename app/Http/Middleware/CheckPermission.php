<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(private readonly PermissionService $permissionService) {}

    /**
     * Usage: permission:employee.view
     *        permission:employee.create,employee.edit   (needs ALL)
     *        permission:payroll.view|reports.view       (needs ANY one)
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        foreach ($permissions as $permission) {
            // Pipe-separated = OR (any one is enough)
            if (str_contains($permission, '|')) {
                $options = explode('|', $permission);
                $any = false;
                foreach ($options as $option) {
                    if ($this->permissionService->check($request->user(), trim($option))) {
                        $any = true;
                        break;
                    }
                }
                if (!$any) {
                    return $this->deny($request);
                }
            } else {
                // Plain permission = required
                if (!$this->permissionService->check($request->user(), trim($permission))) {
                    return $this->deny($request);
                }
            }
        }

        return $next($request);
    }

    private function deny(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        abort(403, 'You do not have permission to perform this action.');
    }
}
