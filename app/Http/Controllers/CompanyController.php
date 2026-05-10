<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\OfficeHour;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    // ── Company ───────────────────────────────────────────────────

    public function index()
    {
        $company     = Company::with(['branches.officeHours', 'departments', 'shifts'])->first();
        $branches    = Branch::with('officeHours')->orderBy('is_headquarters', 'desc')->orderBy('name')->get();
        $departments = Department::with(['branch', 'parent'])->orderBy('sort_order')->orderBy('name')->get();
        $shifts      = Shift::orderBy('name')->get();

        return view('admin.company.index', compact('company', 'branches', 'departments', 'shifts'));
    }

    /** Create or update the single company record. */
    public function upsertCompany(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'legal_name'          => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'tax_number'          => 'nullable|string|max:100',
            'email'               => 'nullable|email|max:255',
            'phone'               => 'nullable|string|max:50',
            'website'             => 'nullable|url|max:255',
            'address'             => 'nullable|string|max:500',
            'city'                => 'nullable|string|max:100',
            'state'               => 'nullable|string|max:100',
            'country'             => 'nullable|string|max:100',
            'postal_code'         => 'nullable|string|max:20',
            'industry'            => 'nullable|string|max:100',
            'description'         => 'nullable|string|max:1000',
        ]);

        // Call Company::first() once and reuse — avoids up to 3 extra queries
        $existing = Company::first();

        if ($existing) {
            $existing->update($data);
            $company = $existing->fresh();
        } else {
            $company = Company::create($data);
        }

        return response()->json(['message' => 'Company profile saved.', 'company' => $company]);
    }

    // ── Branches ──────────────────────────────────────────────────

    public function storeBranch(Request $request): JsonResponse
    {
        $company = $this->requireCompany();

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'code'             => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:50',
            'address'          => 'nullable|string|max:500',
            'city'             => 'nullable|string|max:100',
            'state'            => 'nullable|string|max:100',
            'country'          => 'nullable|string|max:100',
            'postal_code'      => 'nullable|string|max:20',
            'is_headquarters'  => 'boolean',
            'office_hours'     => 'nullable|array',
        ]);

        DB::transaction(function () use (&$data, $company, $request) {
            if (!empty($data['is_headquarters'])) {
                Branch::where('company_id', $company->id)->update(['is_headquarters' => false]);
            }

            $branch = Branch::create([
                'company_id'      => $company->id,
                'name'            => $data['name'],
                'code'            => $data['code'] ?? null,
                'email'           => $data['email'] ?? null,
                'phone'           => $data['phone'] ?? null,
                'address'         => $data['address'] ?? null,
                'city'            => $data['city'] ?? null,
                'state'           => $data['state'] ?? null,
                'country'         => $data['country'] ?? null,
                'postal_code'     => $data['postal_code'] ?? null,
                'is_headquarters' => $data['is_headquarters'] ?? false,
                'is_active'       => true,
            ]);

            $this->syncOfficeHours($branch, $request->input('office_hours', []));
            $data['branch'] = $branch->load('officeHours');
        });

        return response()->json(['message' => 'Branch created.', 'branch' => $data['branch']]);
    }

    public function updateBranch(Request $request, Branch $branch): JsonResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'code'            => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:50',
            'address'         => 'nullable|string|max:500',
            'city'            => 'nullable|string|max:100',
            'state'           => 'nullable|string|max:100',
            'country'         => 'nullable|string|max:100',
            'postal_code'     => 'nullable|string|max:20',
            'is_headquarters' => 'boolean',
            'is_active'       => 'boolean',
            'office_hours'    => 'nullable|array',
        ]);

        DB::transaction(function () use ($data, $branch, $request) {
            if (!empty($data['is_headquarters'])) {
                Branch::where('company_id', $branch->company_id)
                      ->where('id', '!=', $branch->id)
                      ->update(['is_headquarters' => false]);
            }

            $branch->update(collect($data)->except('office_hours')->toArray());
            $this->syncOfficeHours($branch, $request->input('office_hours', []));
        });

        return response()->json(['message' => 'Branch updated.', 'branch' => $branch->fresh('officeHours')]);
    }

    public function destroyBranch(Branch $branch): JsonResponse
    {
        if ($branch->employees()->count()) {
            return response()->json(['message' => 'Cannot delete branch with assigned employees.'], 422);
        }
        $branch->delete();
        return response()->json(['message' => 'Branch deleted.']);
    }

    public function toggleBranch(Branch $branch): JsonResponse
    {
        $branch->update(['is_active' => ! $branch->is_active]);
        return response()->json(['message' => 'Branch ' . ($branch->is_active ? 'activated' : 'deactivated') . '.', 'is_active' => $branch->is_active]);
    }

    // ── Departments ───────────────────────────────────────────────

    public function storeDepartment(Request $request): JsonResponse
    {
        $company = $this->requireCompany();

        $data = $request->validate([
            'branch_id'    => 'nullable|exists:branches,id',
            'parent_id'    => 'nullable|exists:departments,id',
            'name'         => 'required|string|max:255',
            'code'         => 'nullable|string|max:20',
            'manager_name' => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'description'  => 'nullable|string|max:500',
        ]);

        $dept = Department::create(array_merge($data, [
            'company_id' => $company->id,
            'is_active'  => true,
            'sort_order' => Department::where('company_id', $company->id)->max('sort_order') + 1,
        ]));

        return response()->json(['message' => 'Department created.', 'department' => $dept->load(['branch', 'parent'])]);
    }

    public function updateDepartment(Request $request, Department $department): JsonResponse
    {
        $data = $request->validate([
            'branch_id'    => 'nullable|exists:branches,id',
            'parent_id'    => ['nullable', 'exists:departments,id', Rule::notIn([$department->id])],
            'name'         => 'required|string|max:255',
            'code'         => 'nullable|string|max:20',
            'manager_name' => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'description'  => 'nullable|string|max:500',
            'is_active'    => 'boolean',
        ]);

        $department->update($data);
        return response()->json(['message' => 'Department updated.', 'department' => $department->fresh(['branch', 'parent'])]);
    }

    public function destroyDepartment(Department $department): JsonResponse
    {
        if ($department->employees()->count()) {
            return response()->json(['message' => 'Cannot delete department with assigned employees.'], 422);
        }
        if ($department->children()->count()) {
            return response()->json(['message' => 'Cannot delete department that has sub-departments.'], 422);
        }
        $department->delete();
        return response()->json(['message' => 'Department deleted.']);
    }

    /** AJAX: departments filtered by branch (for employee form cascade) */
    public function departmentsByBranch(Request $request): JsonResponse
    {
        $branchId = $request->query('branch_id');

        $depts = Department::active()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'branch_id']);

        return response()->json($depts);
    }

    // ── Shifts ────────────────────────────────────────────────────

    public function storeShift(Request $request): JsonResponse
    {
        $company = $this->requireCompany();

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0|max:480',
            'working_days'  => 'nullable|array',
            'working_days.*'=> 'integer|between:0,6',
            'is_overnight'  => 'boolean',
            'color'         => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $shift = Shift::create(array_merge($data, ['company_id' => $company->id, 'is_active' => true]));

        return response()->json(['message' => 'Shift created.', 'shift' => $shift]);
    }

    public function updateShift(Request $request, Shift $shift): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0|max:480',
            'working_days'  => 'nullable|array',
            'working_days.*'=> 'integer|between:0,6',
            'is_overnight'  => 'boolean',
            'color'         => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_active'     => 'boolean',
        ]);

        $shift->update($data);
        return response()->json(['message' => 'Shift updated.', 'shift' => $shift->fresh()]);
    }

    public function destroyShift(Shift $shift): JsonResponse
    {
        if ($shift->employees()->count()) {
            return response()->json(['message' => 'Cannot delete shift assigned to employees.'], 422);
        }
        $shift->delete();
        return response()->json(['message' => 'Shift deleted.']);
    }

    public function toggleShift(Shift $shift): JsonResponse
    {
        $shift->update(['is_active' => ! $shift->is_active]);
        return response()->json(['message' => 'Shift ' . ($shift->is_active ? 'activated' : 'deactivated') . '.', 'is_active' => $shift->is_active]);
    }

    // ── Private helpers ───────────────────────────────────────────

    private function requireCompany(): Company
    {
        $company = Company::first();
        if (! $company) {
            abort(422, 'Please save company profile first.');
        }
        return $company;
    }

    private function syncOfficeHours(Branch $branch, array $hours): void
    {
        if (empty($hours)) {
            return;
        }

        $rows = [];
        foreach ($hours as $dayData) {
            $day = (int) ($dayData['day_of_week'] ?? -1);
            if ($day < 0 || $day > 6) {
                continue;
            }

            $isWorking = (bool) ($dayData['is_working'] ?? false);

            $rows[] = [
                'branch_id'   => $branch->id,
                'day_of_week' => $day,
                'is_working'  => $isWorking,
                'open_time'   => $isWorking ? ($dayData['open_time']  ?? null) : null,
                'close_time'  => $isWorking ? ($dayData['close_time'] ?? null) : null,
            ];
        }

        if (! empty($rows)) {
            // Single multi-row upsert instead of one query per day
            OfficeHour::upsert(
                $rows,
                ['branch_id', 'day_of_week'],
                ['is_working', 'open_time', 'close_time']
            );
        }
    }
}
