<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'hr', 'manager']);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approved,rejected'],
            // exclude_unless removes the field entirely when action != rejected,
            // so an empty rejection_note on approval never triggers a validation error.
            // required + min:5 enforce a meaningful reason when rejecting.
            'rejection_note' => [
                'exclude_unless:action,rejected',
                'required',
                'string',
                'min:5',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_note.required' => 'A rejection reason is required.',
            'rejection_note.min'      => 'Rejection reason must be at least 5 characters.',
        ];
    }
}
