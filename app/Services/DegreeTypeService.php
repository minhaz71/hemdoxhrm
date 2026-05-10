<?php

namespace App\Services;

use App\Models\DegreeType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DegreeTypeService
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return DegreeType::withCount(['degreeNames', 'employeeEducations'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function allActive(): Collection
    {
        return DegreeType::active()->orderBy('name')->get();
    }

    public function store(array $data): DegreeType
    {
        return DegreeType::create([
            'name'   => $data['name'],
            'status' => $data['status'] ?? 'active',
        ]);
    }

    public function update(DegreeType $type, array $data): DegreeType
    {
        $type->update([
            'name'   => $data['name'],
            'status' => $data['status'] ?? $type->status,
        ]);

        return $type->fresh();
    }

    public function toggle(DegreeType $type): DegreeType
    {
        $type->update([
            'status' => $type->status === 'active' ? 'inactive' : 'active',
        ]);

        return $type->fresh();
    }

    public function delete(DegreeType $type): void
    {
        if ($type->employeeEducations()->exists()) {
            throw ValidationException::withMessages([
                'degree_type' => "Cannot delete \"{$type->name}\" — it is used in employee education records.",
            ]);
        }

        $type->delete();
    }
}
