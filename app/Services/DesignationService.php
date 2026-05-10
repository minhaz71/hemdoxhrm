<?php

namespace App\Services;

use App\Models\Designation;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class DesignationService
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Designation::withCount('employees')
            ->with('createdBy')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function allActive(): \Illuminate\Database\Eloquent\Collection
    {
        return Designation::active()->orderBy('name')->get(['id', 'name']);
    }

    public function store(array $data, User $createdBy): Designation
    {
        return Designation::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'active',
            'created_by'  => $createdBy->id,
        ]);
    }

    public function update(Designation $designation, array $data): Designation
    {
        $designation->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? $designation->status,
        ]);

        return $designation->fresh();
    }

    public function toggle(Designation $designation): Designation
    {
        $designation->update([
            'status' => $designation->status === 'active' ? 'inactive' : 'active',
        ]);

        return $designation->fresh();
    }

    public function delete(Designation $designation): void
    {
        $count = $designation->employees()->count();

        if ($count > 0) {
            throw ValidationException::withMessages([
                'designation' => "Cannot delete \"{$designation->name}\" — {$count} employee(s) are assigned to it. Reassign them first.",
            ]);
        }

        $designation->delete();
    }
}
