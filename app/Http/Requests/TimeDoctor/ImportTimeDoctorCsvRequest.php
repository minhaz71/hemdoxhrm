<?php

namespace App\Http\Requests\TimeDoctor;

use Illuminate\Foundation\Http\FormRequest;

class ImportTimeDoctorCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isAdmin()
            || $user?->isHR()
            || ($user?->can('attendance.edit') ?? false);
    }

    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ];
    }
}
