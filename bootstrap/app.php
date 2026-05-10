<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\BlockTerminated::class,
            \App\Http\Middleware\EnsureAccountApproved::class,
            \App\Http\Middleware\CheckForcePasswordReset::class,
            \App\Http\Middleware\TrackLastActive::class,
        ]);

        $middleware->alias([
            'role'                    => \App\Http\Middleware\CheckRole::class,
            'permission'              => \App\Http\Middleware\CheckPermission::class,
            'can_manage_designations' => \App\Http\Middleware\CanManageDesignations::class,
            'prevent.escalation'      => \App\Http\Middleware\PreventRoleEscalation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
