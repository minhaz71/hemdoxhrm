<?php

namespace App\Http\Requests\SalaryHistory;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryIncrementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization checked in controller
    }

    public function rules(): array
    {
        return [
            'employee_id'          => ['required', 'exists:employees,id'],
            'salary_type'          => ['required', 'in:increment,decrement,adjustment'],
            'effective_from'       => ['required', 'date_format:Y-m'],
            'new_salary'           => ['nullable', 'numeric', 'min:0'],
            'increment_amount'     => ['nullable', 'numeric'],
            'increment_percentage' => ['nullable', 'numeric'],
            'reason'               => ['nullable', 'string', 'max:300'],
            'note'                 => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasOne = !empty($this->new_salary)
                || $this->input('increment_amount') !== null && $this->input('increment_amount') !== ''
                || $this->input('increment_percentage') !== null && $this->input('increment_percentage') !== '';

            if (!$hasOne) {
                $validator->errors()->add('new_salary', 'Please provide a new salary, increment amount, or increment percentage.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'employee_id.required'    => 'Please select an employee.',
            'employee_id.exists'      => 'The selected employee does not exist.',
            'salary_type.required'    => 'Please select a type (increment / decrement / adjustment).',
            'salary_type.in'          => 'Invalid salary change type.',
            'effective_from.required' => 'Effective month is required.',
            'effective_from.date_format' => 'Effective month must be in YYYY-MM format.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->effective_from && str_contains($this->effective_from, '-')) {
            $parts = explode('-', $this->effective_from);
            if (count($parts) >= 2) {
                $this->merge(['effective_from' => $parts[0] . '-' . $parts[1]]);
            }
        }
    }
}
