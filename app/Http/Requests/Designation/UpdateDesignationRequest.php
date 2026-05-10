<?php

namespace App\Http\Requests\Designation;

use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user->isAdmin()) return true;

        return $user->isHR() && app(SettingService::class)->get('designation_hr_access', false);
    }

    public function rules(): array
    {
        $id = $this->route('designation')?->id;

        return [
            'name'        => ['required', 'string', 'max:100', Rule::unique('designations', 'name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['required', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A designation with this name already exists.',
        ];
    }
}
