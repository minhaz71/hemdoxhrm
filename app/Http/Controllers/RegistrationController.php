<?php

namespace App\Http\Controllers;

use App\Http\Requests\Registration\SubmitRegistrationRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\PendingRegistration;
use App\Models\Shift;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\WeeklyOffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService,
        private readonly WeeklyOffService $weeklyOffService,
    ) {}

    /** Show the registration form for a valid invitation token. */
    public function show(string $token)
    {
        $invitation = $this->invitationService->findValidOrFail($token);

        $designations    = Designation::active()->orderBy('name')->get(['id', 'name']);
        $departments     = Department::active()->orderBy('name')->get(['id', 'name']);
        $shifts          = Shift::active()->orderBy('name')->get(['id', 'name']);
        $branchesEnabled = branches_enabled();
        $branches        = $branchesEnabled ? Branch::active()->orderBy('name')->get(['id', 'name']) : collect();
        $defaultWeeklyOffDays = $this->weeklyOffService->defaultDays();

        return view('register.form', compact(
            'invitation', 'designations', 'departments', 'shifts', 'branches', 'branchesEnabled', 'defaultWeeklyOffDays'
        ));
    }

    /** Handle form submission. */
    public function store(SubmitRegistrationRequest $request, string $token)
    {
        $invitation = $this->invitationService->findValidOrFail($token);

        $data = $request->validated();

        // Handle file uploads — store in public disk under registrations/
        $filePaths = [];
        foreach (['nid_document', 'certificate', 'photo'] as $field) {
            if ($request->hasFile($field)) {
                $filePaths[$field] = $request->file($field)->store("registrations/{$field}s", 'public');
            }
        }

        // Hash password (bcrypt via Hash::make)
        $data['password'] = Hash::make($data['password']);

        // Merge uploaded file paths into data
        $data = array_merge($data, $filePaths);

        // Salary & employment defaults — HR sets real values after review
        $data['join_date']       = now()->format('Y-m-d');
        $data['employment_type'] = 'full_time';
        $data['base_salary']     = 0;

        $this->invitationService->submitRegistration($invitation, $data);

        return redirect()->route('register.success');
    }

    /** Success page shown after submission. */
    public function success()
    {
        return view('register.success');
    }

    /**
     * AJAX: check whether a login_id is available.
     * GET /check-login-id?login_id=xxx
     */
    /**
     * AJAX: check whether a login_id is available.
     * GET /check-login-id?login_id=xxx[&exclude=user_id]
     */
    public function checkLoginId(Request $request): JsonResponse
    {
        $id      = $request->input('login_id', '');
        $exclude = $request->input('exclude'); // user ID to ignore (for edits)

        if (! $id || strlen($id) < 4) {
            return response()->json(['available' => false, 'message' => 'Too short']);
        }

        $userQuery = User::where('login_id', $id);
        if ($exclude) {
            $userQuery->where('id', '!=', $exclude);
        }

        $taken = $userQuery->exists()
              || PendingRegistration::where('login_id', $id)->exists();

        return response()->json([
            'available' => ! $taken,
            'message'   => $taken ? 'Already taken' : 'Available',
        ]);
    }
}
