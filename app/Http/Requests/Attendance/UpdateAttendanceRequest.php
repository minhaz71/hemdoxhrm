<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'hr']);
    }

    /** Statuses that do not require a check-in time. */
    private const NO_CHECKIN_STATUSES = 'absent,holiday,weekly_off,leave';

    public function rules(): array
    {
        return [
            'check_in'  => ['nullable', 'required_unless:status,' . self::NO_CHECKIN_STATUSES, 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'status'    => ['required', 'in:present,late,absent,underworked,holiday,weekly_off,leave'],
            'note'      => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_out.after' => 'Check-out must be after check-in.',
        ];
    }
}
