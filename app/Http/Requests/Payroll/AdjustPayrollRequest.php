<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class AdjustPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'hr']);
    }

    public function rules(): array
    {
        return [
            'bonus'           => ['nullable', 'numeric', 'min:0'],
            'incentive'       => ['nullable', 'numeric', 'min:0'],
            'overtime_amount' => ['nullable', 'numeric', 'min:0'],
            'note'            => ['nullable', 'string', 'max:500'],
        ];
    }
}
