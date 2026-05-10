<?php

namespace App\Http\Requests\WeeklyOff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDefaultWeeklyOffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'hr']) ?? false;
    }

    public function rules(): array
    {
        return [
            'default_weekly_off_days' => ['nullable', 'array'],
            'default_weekly_off_days.*' => ['in:sunday,monday,tuesday,wednesday,thursday,friday,saturday'],
        ];
    }
}
