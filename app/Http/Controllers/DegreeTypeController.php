<?php

namespace App\Http\Controllers;

use App\Http\Requests\DegreeType\StoreDegreeTypeRequest;
use App\Http\Requests\DegreeType\UpdateDegreeTypeRequest;
use App\Models\DegreeType;
use App\Services\DegreeTypeService;
use Illuminate\Http\JsonResponse;

class DegreeTypeController extends Controller
{
    public function __construct(private readonly DegreeTypeService $service) {}

    public function index(): \Illuminate\View\View
    {
        $degreeTypes = $this->service->paginate();

        return view('degree-types.index', compact('degreeTypes'));
    }

    public function store(StoreDegreeTypeRequest $request): JsonResponse
    {
        $type = $this->service->store($request->validated());

        return response()->json([
            'success'     => true,
            'message'     => "Degree type \"{$type->name}\" created.",
            'degree_type' => $this->format($type),
        ], 201);
    }

    public function show(DegreeType $degreeType): JsonResponse
    {
        return response()->json(['degree_type' => $this->format($degreeType)]);
    }

    public function update(UpdateDegreeTypeRequest $request, DegreeType $degreeType): JsonResponse
    {
        $type = $this->service->update($degreeType, $request->validated());

        return response()->json([
            'success'     => true,
            'message'     => 'Degree type updated.',
            'degree_type' => $this->format($type),
        ]);
    }

    public function destroy(DegreeType $degreeType): JsonResponse
    {
        try {
            $this->service->delete($degreeType);

            return response()->json(['success' => true, 'message' => 'Degree type deleted.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Cannot delete.',
            ], 422);
        }
    }

    public function toggle(DegreeType $degreeType): JsonResponse
    {
        $type = $this->service->toggle($degreeType);

        return response()->json([
            'success' => true,
            'status'  => $type->status,
            'message' => "Status changed to {$type->status}.",
        ]);
    }

    private function format(DegreeType $t): array
    {
        return [
            'id'                       => $t->id,
            'name'                     => $t->name,
            'status'                   => $t->status,
            'degree_names_count'       => $t->degree_names_count ?? $t->degreeNames()->count(),
            'employee_educations_count' => $t->employee_educations_count ?? $t->employeeEducations()->count(),
        ];
    }
}
