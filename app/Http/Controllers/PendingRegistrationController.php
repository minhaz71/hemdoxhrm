<?php

namespace App\Http\Controllers;

use App\Http\Requests\Registration\UpdatePendingRegistrationRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\PendingRegistration;
use App\Models\Shift;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PendingRegistrationController extends Controller
{
    public function __construct(private readonly InvitationService $invitationService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PendingRegistration::class);

        $filters = $request->only(['status', 'search', 'invitation_id']);
        $registrations = $this->invitationService->paginatePending($filters);

        return view('registrations.index', compact('registrations', 'filters'));
    }

    public function show(PendingRegistration $registration)
    {
        $this->authorize('view', $registration);

        $registration->load([
            'invitation.creator',
            'designation',
            'branch',
            'department',
            'shift',
            'reviewer',
            'employee',
            'activityLogs.actor',
        ]);

        $designations    = Designation::active()->orderBy('name')->get(['id', 'name']);
        $departments     = Department::active()->orderBy('name')->get(['id', 'name']);
        $shifts          = Shift::active()->orderBy('name')->get(['id', 'name']);
        $branchesEnabled = branches_enabled();
        $branches        = $branchesEnabled ? Branch::active()->orderBy('name')->get(['id', 'name']) : collect();

        return view('registrations.show', compact(
            'registration', 'designations', 'departments', 'shifts', 'branches', 'branchesEnabled'
        ));
    }

    public function update(UpdatePendingRegistrationRequest $request, PendingRegistration $registration): RedirectResponse
    {
        $data = $request->validated();

        // Handle file uploads — keep existing file if no new one uploaded
        foreach (['nid_document', 'certificate', 'photo'] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store("registrations/{$field}s", 'public');
            } else {
                // Do not overwrite existing path with null
                unset($data[$field]);
            }
        }

        try {
            $this->invitationService->updatePending($registration, $data, auth()->user());
            return redirect()->route('registrations.show', $registration)
                ->with('success', 'Registration updated.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->with('error', $e->errors()['registration'][0]);
        }
    }

    public function approve(PendingRegistration $registration): RedirectResponse
    {
        $this->authorize('approve', $registration);

        try {
            $employee = $this->invitationService->approvePendingRegistrations($registration, auth()->user());
            return redirect()->route('employees.show', $employee)
                ->with('success', "✓ Approved! Employee {$employee->full_name} ({$employee->employee_code}) created. They can now log in.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->with('error', $e->errors()['registration'][0] ?? 'Validation failed.');
        } catch (\Throwable $e) {
            \Log::error('Registration approval failed', [
                'registration_id' => $registration->id,
                'error'           => $e->getMessage(),
                'trace'           => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    /**
     * Save all HR edits from the form AND approve in one atomic step.
     * This lets HR review, tweak fields, and approve without two separate clicks.
     */
    public function saveAndApprove(UpdatePendingRegistrationRequest $request, PendingRegistration $registration): RedirectResponse
    {
        $this->authorize('approve', $registration);

        $data = $request->validated();

        // Handle file uploads — keep existing if no new file sent
        foreach (['nid_document', 'certificate', 'photo'] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store("registrations/{$field}s", 'public');
            } else {
                unset($data[$field]);
            }
        }

        try {
            // Step 1: persist all HR edits
            $this->invitationService->updatePending($registration, $data, auth()->user());

            // Step 2: approve using the now-updated record
            $registration->refresh();
            $employee = $this->invitationService->approvePendingRegistrations($registration, auth()->user());

            return redirect()->route('employees.show', $employee)
                ->with('success', "✓ Saved & Approved! Employee {$employee->full_name} ({$employee->employee_code}) created. They can now log in.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msgs = collect($e->errors())->flatten()->implode(' ');
            return redirect()->back()->withInput()->with('error', $msgs);
        } catch (\Throwable $e) {
            \Log::error('Save-and-approve failed', [
                'registration_id' => $registration->id,
                'error'           => $e->getMessage(),
                'trace'           => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()->with('error', 'Save & Approve failed: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, PendingRegistration $registration): RedirectResponse
    {
        $this->authorize('approve', $registration);

        $data = $request->validate([
            'rejection_note' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->invitationService->reject($registration, auth()->user(), $data['rejection_note']);
            return redirect()->route('registrations.index')
                ->with('success', 'Registration rejected.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->with('error', $e->errors()['registration'][0]);
        }
    }

    public function correction(Request $request, PendingRegistration $registration): RedirectResponse
    {
        $this->authorize('approve', $registration);

        $data = $request->validate([
            'correction_note' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->invitationService->requestCorrection($registration, auth()->user(), $data['correction_note']);
            return redirect()->route('registrations.show', $registration)
                ->with('success', 'Correction requested. Applicant notified.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->with('error', $e->errors()['registration'][0]);
        }
    }
}
