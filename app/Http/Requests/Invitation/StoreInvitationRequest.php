<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\RegistrationInvitation::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'hours'    => ['required', 'integer', 'min:1', 'max:720'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'email'    => ['nullable', 'email', 'max:255'],
            'note'     => ['nullable', 'string', 'max:255'],
        ];
    }
}
