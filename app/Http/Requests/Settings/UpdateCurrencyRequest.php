<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'currency_code'      => ['required', 'string', 'max:10'],
            'currency_symbol'    => ['required', 'string', 'max:10'],
            'currency_position'  => ['required', 'in:before,after'],
            'decimal_separator'  => ['required', 'string', 'max:1'],
            'thousand_separator' => ['required', 'string', 'max:1'],
        ];
    }
}
