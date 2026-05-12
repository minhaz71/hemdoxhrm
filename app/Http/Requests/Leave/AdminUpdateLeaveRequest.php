<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:pending,approved,rejected'],
            'rejection_note' => ['required_if:status,rejected', 'nullable', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_note.required_if' => 'A rejection reason is required when setting leave to rejected.',
            'rejection_note.min' => 'Rejection reason must be at least 5 characters.',
        ];
    }
}
