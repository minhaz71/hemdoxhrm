<?php

namespace App\Http\Controllers;

use App\Http\Requests\Designation\StoreDesignationRequest;
use App\Http\Requests\Designation\UpdateDesignationRequest;
use App\Models\Designation;
use App\Services\DesignationService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function __construct(
        private readonly DesignationService $designationService,
        private readonly SettingService     $settingService,
    ) {}

    // GET /designations
    public function index(): \Illuminate\View\View
    {
        $designations    = $this->designationService->paginate();
        $hrAccessEnabled = $this->settingService->get('designation_hr_access', false);
        $isAdmin         = auth()->user()->isAdmin();

        return view('designations.index', compact('designations', 'hrAccessEnabled', 'isAdmin'));
    }

    // POST /designations  (AJAX)
    public function store(StoreDesignationRequest $request): JsonResponse
    {
        $designation = $this->designationService->store($request->validated(), auth()->user());

        return response()->json([
            'success'     => true,
            'message'     => "Designation \"{$designation->name}\" created.",
            'designation' => $this->format($designation),
        ], 201);
    }

    // GET /designations/{designation}  (AJAX — for modal prefill)
    public function show(Designation $designation): JsonResponse
    {
        return response()->json(['designation' => $this->format($designation)]);
    }

    // PATCH /designations/{designation}  (AJAX)
    public function update(UpdateDesignationRequest $request, Designation $designation): JsonResponse
    {
        $designation = $this->designationService->update($designation, $request->validated());

        return response()->json([
            'success'     => true,
            'message'     => "Designation updated.",
            'designation' => $this->format($designation),
        ]);
    }

    // DELETE /designations/{designation}  (AJAX)
    public function destroy(Designation $designation): JsonResponse
    {
        try {
            $this->designationService->delete($designation);

            return response()->json(['success' => true, 'message' => "Designation deleted."]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()['designation'][0] ?? 'Cannot delete.',
            ], 422);
        }
    }

    // PATCH /designations/{designation}/toggle  (AJAX)
    public function toggle(Designation $designation): JsonResponse
    {
        $designation = $this->designationService->toggle($designation);

        return response()->json([
            'success' => true,
            'status'  => $designation->status,
            'message' => "Status changed to {$designation->status}.",
        ]);
    }

    // POST /designations/settings  (admin only, AJAX)
    public function updateSettings(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $this->settingService->set('designation_hr_access', $request->boolean('designation_hr_access'));

        return response()->json([
            'success' => true,
            'message' => 'Setting saved.',
            'value'   => $this->settingService->get('designation_hr_access'),
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────

    private function format(Designation $d): array
    {
        // Use employees_count if already loaded via withCount(); otherwise fall
        // back to a direct count so this method is safe in any calling context.
        $employeesCount = isset($d->employees_count)
            ? (int) $d->employees_count
            : $d->employees()->count();

        return [
            'id'              => $d->id,
            'name'            => $d->name,
            'description'     => $d->description,
            'status'          => $d->status,
            'employees_count' => $employeesCount,
            'created_by_name' => $d->createdBy?->name ?? 'System',
        ];
    }
}
