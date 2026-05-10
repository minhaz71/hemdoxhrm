<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\User;
use App\Policies\AttendancePolicy;
use App\Policies\EmployeePolicy;
use App\Policies\LeavePolicy;
use App\Policies\PayrollPolicy;
use App\Policies\PayslipPolicy;
use App\Services\MenuService;
use App\Services\PermissionService;
use App\Services\SecurityAuditService;
use App\Services\SettingService;
use App\Services\SmtpSettingsService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // PermissionService is stateful per-request (stores resolved cache)
        $this->app->singleton(PermissionService::class);

        // SettingService maintains a per-request memo on top of a shared cache
        $this->app->singleton(SettingService::class);

        // MenuService holds a per-request in-memory copy of the menu cache
        $this->app->singleton(MenuService::class);

        // InvitationService is stateless — singleton for DI convenience
        $this->app->singleton(\App\Services\InvitationService::class);

        // SmtpSettingsService wraps SettingService — singleton for DI convenience
        $this->app->singleton(SmtpSettingsService::class);

        $this->app->singleton(SecurityAuditService::class);

        // AttendanceCalendarService extends HolidayCalendarService and adds
        // getDayStatus() + SettingService injection — bind explicitly so
        // anywhere HolidayCalendarService is type-hinted it can also be
        // resolved as AttendanceCalendarService when needed.
        $this->app->singleton(\App\Services\AttendanceCalendarService::class);
    }

    public function boot(): void
    {
        // ── Apply dynamic settings from DB ────────────────────────────
        try {
            $svc = app(SettingService::class);

            // Timezone — affects Carbon::now(), all date operations
            $tz = $svc->get('timezone');
            if ($tz) {
                config(['app.timezone' => $tz]);
                date_default_timezone_set($tz);
            }

            // SMTP — apply DB-stored settings via SmtpSettingsService
            app(SmtpSettingsService::class)->applyToRuntime();
        } catch (\Throwable) {
            // DB not ready — safe to ignore during migrations / setup
        }

        // ── Policies ──────────────────────────────────────────────────
        Gate::policy(Employee::class,   EmployeePolicy::class);
        Gate::policy(Leave::class,      LeavePolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(Payroll::class,    PayrollPolicy::class);
        Gate::policy(Payslip::class,    PayslipPolicy::class);
        Gate::policy(\App\Models\RegistrationInvitation::class, \App\Policies\InvitationPolicy::class);
        Gate::policy(\App\Models\PendingRegistration::class,    \App\Policies\PendingRegistrationPolicy::class);

        // ── Admin bypass for ALL gates ────────────────────────────────
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('admin')) return true;
        });

        // ── Register each permission as a Gate ────────────────────────
        // Wrapped in try/catch so artisan commands run before migrations work.
        try {
            $names = app(PermissionService::class)->allNames();
            foreach ($names as $name) {
                Gate::define($name, function (User $user) use ($name) {
                    return app(PermissionService::class)->check($user, $name);
                });
            }
        } catch (\Throwable) {
            // DB not ready (e.g. during initial setup)
        }

        // ── Legacy gate: manage-designations ─────────────────────────
        Gate::define('manage-designations', function (User $user) {
            if ($user->hasRole('admin')) return true;
            if (app(PermissionService::class)->check($user, 'designations.manage')) {
                return true;
            }
            // backward-compat: HR setting toggle
            if ($user->hasRole('hr')) {
                return app(SettingService::class)->get('designation_hr_access', false);
            }
            return false;
        });

        // ── Blade components ──────────────────────────────────────────
        Blade::component('emails.layout', 'emails.layout');

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $identifier = mb_strtolower((string) $request->input('email'));
            return [
                Limit::perMinute(5)->by($identifier . '|' . $request->ip()),
                Limit::perMinutes(15, 20)->by($request->ip()),
            ];
        });

        RateLimiter::for('invite-view', fn (Request $request) => [
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('invite-submit', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perHour(10)->by($request->ip()),
        ]);

        RateLimiter::for('security-sensitive', function (Request $request) {
            $user = $request->user();

            // Admin and HR users perform many legitimate management actions per minute.
            // Give them a higher ceiling to prevent false 429s during normal workflow.
            if ($user && ($user->hasRole('admin') || $user->hasRole('hr'))) {
                return Limit::perMinute(200)->by($user->id . '|' . $request->ip());
            }

            // All other authenticated users: moderate limit
            if ($user) {
                return Limit::perMinute(30)->by($user->id . '|' . $request->ip());
            }

            // Unauthenticated (guest) requests: tight limit
            return Limit::perMinute(10)->by('guest|' . $request->ip());
        });
    }
}
