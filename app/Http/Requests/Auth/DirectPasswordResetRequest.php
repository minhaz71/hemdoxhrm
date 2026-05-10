<?php

namespace App\Http\Requests\Auth;

use App\Support\EnterprisePassword;
use Illuminate\Foundation\Http\FormRequest;

class DirectPasswordResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', EnterprisePassword::rules()],
        ];
    }
}
