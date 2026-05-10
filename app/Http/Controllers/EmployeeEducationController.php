<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeEducation\StoreEmployeeEducationRequest;
use App\Http\Requests\EmployeeEducation\UpdateEmployeeEducationRequest;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Services\DegreeNameService;
use App\Services\DegreeTypeService;
use App\Services\EmployeeEducationService;
use Illuminate\Http\JsonResponse;

class EmployeeEducationController extends Controller
{
    public function __construct(
        private readonly EmployeeEducationService $service,
        private readonly DegreeTypeService        $typeService,
        private readonly DegreeNameService        $nameService,
    ) {}

    public function index(Employee $employee): \Illuminate\View\View
    {
        $this->authorize('view', $employee);

        $educations  = $this->service->forEmployee($employee);
        $degreeTypes = $this->typeService->allActive();

        return view('employees.education', compact('employee', 'educations', 'degreeTypes'));
    }

    public function store(StoreEmployeeEducationRequest $request, Employee $employee): JsonResponse
    {
        $education = $this->service->store($employee, $request->validated());
        $education->load(['degreeType', 'degreeName']);

        return response()->json([
            'success'   => true,
            'message'   => 'Education record added.',
            'education' => $this->format($education),
        ], 201);
    }

    public function show(Employee $employee, EmployeeEducation $education): JsonResponse
    {
        abort_unless($education->employee_id === $employee->id, 404);
        $this->authorize('view', $employee);

        $education->load(['degreeType', 'degreeName']);

        return response()->json(['education' => $this->format($education)]);
    }

    public function update(UpdateEmployeeEducationRequest $request, Employee $employee, EmployeeEducation $education): JsonResponse
    {
        abort_unless($education->employee_id === $employee->id, 404);

        $education = $this->service->update($education, $request->validated());

        return response()->json([
            'success'   => true,
            'message'   => 'Education record updated.',
            'education' => $this->format($education),
        ]);
    }

    public function destroy(Employee $employee, EmployeeEducation $education): JsonResponse
    {
        abort_unless($education->employee_id === $employee->id, 404);
        $this->authorize('manageEducation', $employee);

        $this->service->delete($education);

        return response()->json(['success' => true, 'message' => 'Education record deleted.']);
    }

    private function format(EmployeeEducation $e): array
    {
        return [
            'id'               => $e->id,
            'degree_type_id'   => $e->degree_type_id,
            'degree_type_name' => $e->degreeType->name,
            'degree_name_id'   => $e->degree_name_id,
            'degree_name_name' => $e->degreeName->name,
            'institute_name'   => $e->institute_name,
            'passing_year'     => $e->passing_year,
            'result'           => $e->result,
            'board_university' => $e->board_university,
        ];
    }
}
