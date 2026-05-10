<?php

namespace App\Http\Controllers;

use App\Http\Requests\DegreeName\StoreDegreeNameRequest;
use App\Http\Requests\DegreeName\UpdateDegreeNameRequest;
use App\Models\DegreeName;
use App\Models\DegreeType;
use App\Services\DegreeNameService;
use App\Services\DegreeTypeService;
use Illuminate\Http\JsonResponse;

class DegreeNameController extends Controller
{
    public function __construct(
        private readonly DegreeNameService $service,
        private readonly DegreeTypeService $typeService,
    ) {}

    public function index(): \Illuminate\View\View
    {
        $degreeNames = $this->service->paginate();
        $degreeTypes = $this->typeService->allActive();

        return view('degree-names.index', compact('degreeNames', 'degreeTypes'));
    }

    public function store(StoreDegreeNameRequest $request): JsonResponse
    {
        $name = $this->service->store($request->validated());
        $name->load('degreeType');

        return response()->json([
            'success'     => true,
            'message'     => "Degree name \"{$name->name}\" created.",
            'degree_name' => $this->format($name),
        ], 201);
    }

    public function show(DegreeName $degreeName): JsonResponse
    {
        $degreeName->load('degreeType');

        return response()->json(['degree_name' => $this->format($degreeName)]);
    }

    public function update(UpdateDegreeNameRequest $request, DegreeName $degreeName): JsonResponse
    {
        $name = $this->service->update($degreeName, $request->validated());

        return response()->json([
            'success'     => true,
            'message'     => 'Degree name updated.',
            'degree_name' => $this->format($name),
        ]);
    }

    public function destroy(DegreeName $degreeName): JsonResponse
    {
        try {
            $this->service->delete($degreeName);

            return response()->json(['success' => true, 'message' => 'Degree name deleted.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Cannot delete.',
            ], 422);
        }
    }

    public function toggle(DegreeName $degreeName): JsonResponse
    {
        $name = $this->service->toggle($degreeName);

        return response()->json([
            'success' => true,
            'status'  => $name->status,
            'message' => "Status changed to {$name->status}.",
        ]);
    }

    // AJAX endpoint used by employee education form for cascading dropdown
    public function byType(DegreeType $degreeType): JsonResponse
    {
        $names = $this->service->byType($degreeType)->map(fn ($n) => [
            'id'   => $n->id,
            'name' => $n->name,
        ]);

        return response()->json(['names' => $names]);
    }

    private function format(DegreeName $n): array
    {
        return [
            'id'                       => $n->id,
            'name'                     => $n->name,
            'degree_type_id'           => $n->degree_type_id,
            'degree_type_name'         => $n->degreeType?->name ?? '—',
            'status'                   => $n->status,
            'employee_educations_count' => $n->employee_educations_count ?? $n->employeeEducations()->count(),
        ];
    }
}
