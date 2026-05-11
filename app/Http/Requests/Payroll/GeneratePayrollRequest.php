<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'hr']);
    }

    public function rules(): array
    {
        return [
            'month'       => ['required', 'integer', 'min:1', 'max:12'],
            'year'        => ['required', 'integer', 'min:2020', 'max:2100'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'management_working_days' => ['nullable', 'integer', 'min:0', 'max:31'],

            // Optional adjustments at generation time
            'bonus'           => ['nullable', 'numeric', 'min:0'],
            'incentive'       => ['nullable', 'numeric', 'min:0'],
            'overtime_amount' => ['nullable', 'numeric', 'min:0'],
            'late_deduction'  => ['nullable', 'numeric', 'min:0'],
            'leave_deduction' => ['nullable', 'numeric', 'min:0'],
            'note'            => ['nullable', 'string', 'max:500'],
        ];
    }
}
