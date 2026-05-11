<?php

namespace App\Http\Requests\SalaryHistory;

use App\Models\SalaryHistory;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        return [
            'base_salary'    => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'salary_type'    => ['required', 'in:' . implode(',', array_keys(SalaryHistory::TYPE_LABELS))],
            'effective_from' => ['required', 'date_format:Y-m'],
            'reason'         => ['nullable', 'string', 'max:300'],
            'note'           => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'base_salary.required'    => 'New salary amount is required.',
            'base_salary.numeric'     => 'Salary must be a valid number.',
            'base_salary.min'         => 'Salary cannot be negative.',
            'salary_type.required'    => 'Please select the type of salary change.',
            'salary_type.in'          => 'Invalid salary change type.',
            'effective_from.required' => 'Effective month is required.',
            'effective_from.date_format' => 'Effective month must be in YYYY-MM format.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalise effective_from: accept both "YYYY-MM" and "YYYY-MM-DD"
        if ($this->effective_from && str_contains($this->effective_from, '-')) {
            $parts = explode('-', $this->effective_from);
            if (count($parts) >= 2) {
                $this->merge(['effective_from' => $parts[0] . '-' . $parts[1]]);
            }
        }
    }
}
