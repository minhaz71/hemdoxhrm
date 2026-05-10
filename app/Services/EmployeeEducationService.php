<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeEducation;
use Illuminate\Support\Collection;

class EmployeeEducationService
{
    public function forEmployee(Employee $employee): Collection
    {
        return $employee->educations()
            ->with(['degreeType', 'degreeName'])
            ->get();
    }

    public function store(Employee $employee, array $data): EmployeeEducation
    {
        return $employee->educations()->create([
            'degree_type_id'  => $data['degree_type_id'],
            'degree_name_id'  => $data['degree_name_id'],
            'institute_name'  => $data['institute_name'],
            'passing_year'    => $data['passing_year'],
            'result'          => $data['result'] ?? null,
            'board_university' => $data['board_university'] ?? null,
        ]);
    }

    public function update(EmployeeEducation $education, array $data): EmployeeEducation
    {
        $education->update([
            'degree_type_id'  => $data['degree_type_id'],
            'degree_name_id'  => $data['degree_name_id'],
            'institute_name'  => $data['institute_name'],
            'passing_year'    => $data['passing_year'],
            'result'          => $data['result'] ?? null,
            'board_university' => $data['board_university'] ?? null,
        ]);

        return $education->fresh(['degreeType', 'degreeName']);
    }

    public function delete(EmployeeEducation $education): void
    {
        $education->delete();
    }
}
