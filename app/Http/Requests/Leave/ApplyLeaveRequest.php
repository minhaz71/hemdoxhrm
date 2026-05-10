<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class ApplyLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user       = $this->user();
        $employeeId = (int) $this->input('employee_id');

        // Admin and HR can apply leave for any employee
        if ($user->hasRole(['admin', 'hr'])) {
            return true;
        }

        // Managers and employees can only apply leave for themselves
        return $user->employee?->id === $employeeId;
    }

    public function rules(): array
    {
        return [
            'employee_id'   => ['required', 'integer', 'exists:employees,id'],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date', 'after_or_equal:today'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($v) {
            $start = $this->input('start_date');
            $end   = $this->input('end_date');
            if (! $start || ! $end) return;

            $days    = (int) \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end)) + 1;
            $maxDays = (int) setting('max_leave_per_request', 30);

            if ($days > $maxDays) {
                $v->errors()->add('end_date', "A single leave request cannot exceed {$maxDays} days (requested: {$days} days).");
            }
        });
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Leave cannot be applied for a past date.',
            'end_date.after_or_equal'   => 'End date must be on or after the start date.',
        ];
    }
}
