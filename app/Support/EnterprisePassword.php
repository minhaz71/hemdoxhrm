<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class EnterprisePassword
{
    public static function rules(): Password
    {
        $rule = Password::min(13)
            ->mixedCase()
            ->numbers()
            ->symbols();

        // The HIBP uncompromised() check makes outbound HTTP requests.
        // Skip it in test/local environments to keep tests fast, offline-safe,
        // and free of flakiness from the external API.
        if (app()->environment('production')) {
            $rule->uncompromised();
        }

        return $rule;
    }
}
