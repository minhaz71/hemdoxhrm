<?php

namespace App\Http\Requests\Auth;

use App\Support\EnterprisePassword;
use Illuminate\Foundation\Http\FormRequest;

class ForcePasswordChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', EnterprisePassword::rules()],
        ];
    }
}
