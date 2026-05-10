<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSmtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'mail_enabled'      => ['sometimes', 'boolean'],
            'smtp_host'         => ['nullable', 'string', 'max:255'],
            'smtp_port'         => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_username'     => ['nullable', 'string', 'max:255'],
            'smtp_password'     => ['nullable', 'string', 'max:255'],
            'smtp_encryption'   => ['required', 'in:tls,ssl,starttls,'],
            'mail_from_name'    => ['required', 'string', 'max:150'],
            'mail_from_address' => ['nullable', 'email', 'max:150'],
        ];
    }
}
