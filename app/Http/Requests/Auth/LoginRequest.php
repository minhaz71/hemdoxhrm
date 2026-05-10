<?php

namespace App\Http\Requests\Auth;

use App\Models\PendingRegistration;
use App\Services\SecurityAuditService;
use App\Services\UserActivityService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * The 'email' field now accepts both email addresses and login_ids.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     * Supports both email address and login_id in the 'email' field.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $input    = $this->string('email')->value();
        $password = $this->string('password')->value();
        $remember = $this->boolean('remember');

        $authenticated = false;

        // 1. If it looks like an email, try email auth first
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            if (Auth::attempt(['email' => $input, 'password' => $password], $remember)) {
                $authenticated = true;
            }
        }

        // 2. Try login_id auth (also as fallback when email auth failed)
        if (! $authenticated) {
            if (Auth::attempt(['login_id' => $input, 'password' => $password], $remember)) {
                $authenticated = true;
            }
        }

        if ($authenticated) {
            $user = Auth::user();

            if (in_array($user?->status, ['terminated', 'suspended'], true)) {
                app(SecurityAuditService::class)->warning(SecurityAuditService::EVENT_LOGIN_FAILED, $this, $user, $user, [
                    'reason' => 'account_inactive',
                    'status' => $user->status,
                ]);

                Auth::logout();
                RateLimiter::hit($this->throttleKey());

                $message = $user->status === 'suspended'
                    ? 'Your account has been suspended. Please contact HR.'
                    : 'Your account has been terminated. Please contact HR.';

                throw ValidationException::withMessages([
                    'email' => $message,
                ]);
            }

            if (in_array($user?->approval_status, ['pending', 'correction_required', 'rejected'], true)) {
                app(SecurityAuditService::class)->warning(SecurityAuditService::EVENT_LOGIN_FAILED, $this, $user, $user, [
                    'reason'          => 'account_unapproved',
                    'approval_status' => $user->approval_status,
                ]);

                Auth::logout();
                RateLimiter::hit($this->throttleKey());

                $message = match ($user->approval_status) {
                    'pending'             => 'Your account is still pending HR approval.',
                    'correction_required' => 'Your registration requires correction before login is allowed.',
                    'rejected'            => 'Your registration has been rejected. Please contact HR.',
                };

                throw ValidationException::withMessages(['email' => $message]);
            }

            RateLimiter::clear($this->throttleKey());
            return;
        }

        // Record failed login attempt
        try {
            app(UserActivityService::class)->recordFailed($input, $this);
            app(SecurityAuditService::class)->loginFailed($this, $input);
        } catch (\Throwable) {
            // Never block login flow due to activity tracking failure
        }

        // Auth failed — check if there is a pending registration with this login_id
        $pending = PendingRegistration::where('login_id', $input)->first();

        RateLimiter::hit($this->throttleKey());

        if ($pending) {
            $message = match ($pending->status) {
                'pending'              => 'Your account is still pending review. Please wait for HR approval.',
                'correction_required'  => 'Your application requires corrections. Please check your email or contact HR.',
                'rejected'             => 'Your application has been rejected. Please contact HR for more information.',
                default                => trans('auth.failed'),
            };

            throw ValidationException::withMessages(['email' => $message]);
        }

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        // Audit-log the lockout event
        try {
            app(SecurityAuditService::class)->loginThrottled($this, $this->string('email')->value());
        } catch (\Throwable) {
            // Never block auth flow due to audit logging failure
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}
