<?php

namespace App\Services;

use App\Models\DegreeName;
use App\Models\DegreeType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DegreeNameService
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return DegreeName::with('degreeType')
            ->withCount('employeeEducations')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function allActive(): Collection
    {
        return DegreeName::active()->with('degreeType')->orderBy('name')->get();
    }

    /**
     * Returns active names linked to the given type.
     * Falls back to all active names when the type has no linked names.
     */
    public function byType(DegreeType $type): Collection
    {
        $names = DegreeName::active()
            ->where('degree_type_id', $type->id)
            ->orderBy('name')
            ->get();

        if ($names->isEmpty()) {
            $names = DegreeName::active()
                ->whereNull('degree_type_id')
                ->orderBy('name')
                ->get();
        }

        return $names;
    }

    public function store(array $data): DegreeName
    {
        return DegreeName::create([
            'name'           => $data['name'],
            'degree_type_id' => $data['degree_type_id'] ?? null,
            'status'         => $data['status'] ?? 'active',
        ]);
    }

    public function update(DegreeName $name, array $data): DegreeName
    {
        $name->update([
            'name'           => $data['name'],
            'degree_type_id' => $data['degree_type_id'] ?? null,
            'status'         => $data['status'] ?? $name->status,
        ]);

        return $name->fresh(['degreeType']);
    }

    public function toggle(DegreeName $name): DegreeName
    {
        $name->update([
            'status' => $name->status === 'active' ? 'inactive' : 'active',
        ]);

        return $name->fresh();
    }

    public function delete(DegreeName $name): void
    {
        if ($name->employeeEducations()->exists()) {
            throw ValidationException::withMessages([
                'degree_name' => "Cannot delete \"{$name->name}\" — it is used in employee education records.",
            ]);
        }

        $name->delete();
    }
}
