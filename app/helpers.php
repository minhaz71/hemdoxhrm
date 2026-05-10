<?php

use App\Services\CurrencyService;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

// ── Currency ──────────────────────────────────────────────────────────────────

if (! function_exists('currency')) {
    /** Format a monetary amount using the system's active currency settings. */
    function currency(float|int|string $amount, int $decimals = 2): string
    {
        return app(CurrencyService::class)->format($amount, $decimals);
    }
}

if (! function_exists('currency_symbol')) {
    /** Return just the currency symbol for input-group prefixes. */
    function currency_symbol(): string
    {
        return app(CurrencyService::class)->symbol();
    }
}

if (! function_exists('currency_code')) {
    function currency_code(): string
    {
        return app(CurrencyService::class)->code();
    }
}

// ── Settings ──────────────────────────────────────────────────────────────────

if (! function_exists('setting')) {
    /**
     * Read a system setting by key.
     *
     * {{ setting('company_name', 'My Company') }}
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingService::class)->get($key, $default);
    }
}

// ── Company ───────────────────────────────────────────────────────────────────

if (! function_exists('company_name')) {
    function company_name(): string
    {
        return setting('company_name', config('app.name', 'HRMS'));
    }
}

if (! function_exists('company_tagline')) {
    function company_tagline(): string
    {
        return setting('company_tagline', '');
    }
}

if (! function_exists('company_logo_url')) {
    /**
     * Returns the public URL for the company logo, or null if not set.
     */
    function company_logo_url(): ?string
    {
        $path = setting('company_logo', '');
        if (! $path) return null;
        return Storage::disk('public')->url($path);
    }
}

// ── Date / Time Formatting ────────────────────────────────────────────────────

if (! function_exists('app_timezone')) {
    function app_timezone(): string
    {
        return setting('timezone', config('app.timezone', 'UTC'));
    }
}

if (! function_exists('format_date')) {
    /**
     * Format a date using the system date format setting.
     *
     * {{ format_date($employee->hire_date) }}           → e.g. 09 May 2026
     * {{ format_date($employee->hire_date, 'Y-m-d') }}  → 2026-05-09 (forced format)
     */
    function format_date(mixed $date, ?string $format = null): string
    {
        if (! $date) return '—';

        try {
            $fmt = $format ?? setting('date_format', 'Y-m-d');
            return Carbon::parse($date)->timezone(app_timezone())->format($fmt);
        } catch (\Throwable) {
            return (string) $date;
        }
    }
}

if (! function_exists('format_time')) {
    /**
     * Format a time using the system time format setting.
     *
     * {{ format_time($attendance->check_in) }}   → e.g. 09:15 or 9:15 AM
     */
    function format_time(mixed $time, ?string $format = null): string
    {
        if (! $time) return '—';

        try {
            $fmt = $format ?? setting('time_format', 'H:i');
            return Carbon::parse($time)->timezone(app_timezone())->format($fmt);
        } catch (\Throwable) {
            return (string) $time;
        }
    }
}

if (! function_exists('branches_enabled')) {
    /**
     * Whether the multi-branch feature is switched on in system settings.
     * When false, branch fields are hidden and all branch-related UI disappears.
     */
    function branches_enabled(): bool
    {
        return setting('enable_branches', '0') === '1';
    }
}

if (! function_exists('format_datetime')) {
    /**
     * Format a datetime using both system date and time format settings.
     *
     * {{ format_datetime($leave->created_at) }}  → e.g. 09 May 2026 09:15 AM
     */
    function format_datetime(mixed $datetime): string
    {
        if (! $datetime) return '—';

        try {
            $dateFmt = setting('date_format', 'Y-m-d');
            $timeFmt = setting('time_format', 'H:i');
            return Carbon::parse($datetime)->timezone(app_timezone())->format("{$dateFmt} {$timeFmt}");
        } catch (\Throwable) {
            return (string) $datetime;
        }
    }
}
