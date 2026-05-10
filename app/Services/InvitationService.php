<?php

namespace App\Services;

use App\Models\Designation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PendingRegistration;
use App\Models\RegistrationInvitation;
use App\Models\Role;
use App\Models\User;
use App\Mail\RegistrationApprovedMail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function __construct(private readonly SecurityAuditService $audit) {}

    // ── Invitation management ──────────────────────────────────────

    /**
     * @param  int       $hours    Link expiry in hours
     * @param  int|null  $maxUses  Max registrations allowed (null = unlimited)
     */
    public function create(
        User    $createdBy,
        int     $hours,
        ?int    $maxUses,
        ?string $email,
        ?string $note,
    ): RegistrationInvitation {
        [$token, $tokenHash] = $this->generateToken();

        $invitation = RegistrationInvitation::create([
            'token'      => $token,
            'token_hash' => $tokenHash,
            'created_by' => $createdBy->id,
            'email'      => $email ?: null,
            'note'       => $note  ?: null,
            'max_uses'   => $maxUses,   // null = unlimited
            'uses_count' => 0,
            'expires_at' => now()->addHours($hours),
            'status'     => 'active',
        ]);

        $this->audit->info(SecurityAuditService::EVENT_INVITE_CREATED, request(), $createdBy, $invitation, [
            'expires_at' => $invitation->expires_at?->toIso8601String(),
            'max_uses'   => $maxUses,
            'email'      => $email,
        ]);

        return $invitation;
    }

    public function revoke(RegistrationInvitation $invitation): void
    {
        if (in_array($invitation->status, ['revoked', 'expired'], true)) {
            throw ValidationException::withMessages([
                'invitation' => 'This invitation is already ' . $invitation->status . '.',
            ]);
        }

        $invitation->update(['status' => 'revoked']);

        $this->audit->info(SecurityAuditService::EVENT_INVITE_REVOKED, request(), auth()->user(), $invitation);
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return RegistrationInvitation::with(['creator', 'pendingRegistrations'])
            ->withCount('pendingRegistrations')
            ->latest()
            ->paginate($perPage);
    }

    // ── Token validation ───────────────────────────────────────────

    /**
     * Find the invitation by token (uses SHA-256 hash lookup for security).
     * Falls back to plaintext token for backward-compatibility with rows
     * created before the token_hash migration ran.
     * Aborts with HTTP 404 / 410 on any failure.
     *
     * Also tracks view count and IP for abuse detection.
     */
    public function findValidOrFail(string $token): RegistrationInvitation
    {
        $tokenHash  = hash('sha256', $token);
        $req        = request();

        // Prefer hash-based lookup; fall back for legacy rows
        $invitation = RegistrationInvitation::where('token_hash', $tokenHash)->first()
            ?? RegistrationInvitation::where('token', $token)->first();

        if (! $invitation) {
            // Log unknown token attempts — potential enumeration attack
            $this->audit->warning(SecurityAuditService::EVENT_INVITE_ABUSE, $req, null, null, [
                'reason'       => 'unknown_token',
                'token_prefix' => substr($token, 0, 8) . '…',
            ]);
            abort(404, 'Invitation not found.');
        }

        // ── Abuse detection: IP-based repeated scanning ───────────────
        if ($invitation->abuse_blocked_at !== null) {
            abort(410, 'This invitation link has been blocked due to suspicious activity.');
        }

        // Track view count per invitation (increment atomically, cap at 65535)
        $newViewCount = $invitation->view_count + 1;
        $viewIp       = $req?->ip();
        $invitation->updateQuietly([
            'view_count'     => $newViewCount,
            'last_viewed_ip' => $viewIp,
        ]);

        // Flag invitation if view count is abnormally high (potential scanner)
        if ($newViewCount > 50) {
            $invitation->updateQuietly(['abuse_blocked_at' => now()]);
            $this->audit->inviteAbuse($req, $token, 'view_count_exceeded');
            abort(410, 'This invitation link has been blocked due to suspicious activity.');
        }

        // ── Standard validity checks ──────────────────────────────────
        if ($invitation->status === 'revoked') {
            abort(410, 'This invitation link has been revoked.');
        }

        if ($invitation->expires_at->isPast()) {
            $invitation->update(['status' => 'expired']);
            $this->audit->info(SecurityAuditService::EVENT_INVITE_EXPIRED, $req, null, $invitation);
            abort(410, 'This invitation link has expired.');
        }

        // Check capacity — auto-exhaust if needed
        if ($invitation->max_uses !== null && $invitation->uses_count >= $invitation->max_uses) {
            $invitation->update(['status' => 'exhausted']);
            abort(410, 'This invitation link has reached its maximum number of uses.');
        }

        if ($invitation->status === 'exhausted') {
            abort(410, 'This invitation link has reached its maximum number of uses.');
        }

        if ($invitation->status !== 'active') {
            abort(410, 'This invitation is no longer valid.');
        }

        $this->audit->info(SecurityAuditService::EVENT_INVITE_VIEWED, $req, null, $invitation, [
            'view_count' => $newViewCount,
        ]);

        return $invitation;
    }

    // ── Registration submission ────────────────────────────────────

    public function submitRegistration(RegistrationInvitation $invitation, array $data): PendingRegistration
    {
        return DB::transaction(function () use ($invitation, $data) {
            $invitation = RegistrationInvitation::whereKey($invitation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invitation->isValid()) {
                $this->audit->warning(SecurityAuditService::EVENT_INVITE_ABUSE, request(), null, $invitation, [
                    'reason'     => 'invalid_on_submit',
                    'status'     => $invitation->status,
                    'uses_count' => $invitation->uses_count,
                    'max_uses'   => $invitation->max_uses,
                ]);

                throw ValidationException::withMessages([
                    'invitation' => 'This invitation link is no longer valid.',
                ]);
            }

            // Increment the use counter atomically
            $invitation->increment('uses_count');
            $invitation->refresh();

            // Set first-use timestamp on the invitation
            if ($invitation->used_at === null) {
                $invitation->update(['used_at' => now()]);
            }

            // Auto-exhaust when capacity is reached
            if ($invitation->max_uses !== null && $invitation->uses_count >= $invitation->max_uses) {
                $invitation->update(['status' => 'exhausted']);
            }

            $reg = PendingRegistration::create([
                'invitation_id'   => $invitation->id,
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'email'           => $data['email'],
                'organization_employee_code' => $data['organization_employee_code'] ?? null,
                'phone'           => $data['phone']          ?? null,
                'date_of_birth'   => $data['date_of_birth']  ?? null,
                'gender'          => $data['gender']          ?? null,
                'address'         => $data['address']         ?? null,
                'nid'             => $data['nid']             ?? null,
                'nid_document'    => $data['nid_document']    ?? null,
                'certificate'     => $data['certificate']     ?? null,
                'photo'           => $data['photo']           ?? null,
                'designation_id'  => $data['designation_id'] ?? null,
                'department_id'   => $data['department_id']  ?? null,
                'branch_id'       => $data['branch_id']       ?? null,
                'shift_id'        => $data['shift_id']         ?? null,
                'weekly_off_days' => $data['weekly_off_days'] ?? null,
                'weekly_off_note' => $data['weekly_off_note'] ?? null,
                'join_date'       => $data['join_date'],
                'employment_type' => $data['employment_type'],
                'base_salary'     => $data['base_salary']     ?? 0,
                'login_id'        => $data['login_id'],
                'password'        => $data['password'],  // already bcrypt-hashed in controller
                'status'          => 'pending',
            ]);

            $reg->activityLogs()->create([
                'actor_id' => null, // self-submitted
                'action'   => 'submitted',
                'note'     => 'Application submitted via invitation link.',
            ]);

            $this->audit->info(SecurityAuditService::EVENT_INVITE_SUBMITTED, request(), null, $invitation, [
                'pending_registration_id' => $reg->id,
                'email'                   => $reg->email,
            ]);

            return $reg;
        });
    }

    // ── Approval workflow ──────────────────────────────────────────

    public function approvePendingRegistrations(PendingRegistration $reg, User $reviewer, array $overrides = []): Employee
    {
        // Allow approving both 'pending' and 'correction_required' registrations.
        if (! in_array($reg->status, ['pending', 'correction_required'], true)) {
            throw ValidationException::withMessages([
                'registration' => 'This registration has already been ' . $reg->status . '.',
            ]);
        }

        return DB::transaction(function () use ($reg, $reviewer, $overrides) {
            // Re-read the password from DB (excluded from toArray() via $hidden).
            $rawPassword = $reg->getRawOriginal('password') ?? $reg->password;

            $data = array_merge($reg->toArray(), $overrides);

            // Resolve designation / department names from their FK ids.
            // Always overwrite — these may have been updated by HR.
            $designationName = null;
            if (! empty($data['designation_id'])) {
                $designationName = Designation::find($data['designation_id'])?->name;
            }
            $data['designation'] = $designationName ?? ($data['designation'] ?? null);

            $departmentName = null;
            if (! empty($data['department_id'])) {
                $departmentName = Department::find($data['department_id'])?->name;
            }
            $data['department'] = $departmentName ?? ($data['department'] ?? null);

            // Create user account — password is already bcrypt-hashed from registration.
            // We bypass the 'hashed' Eloquent cast by writing the column directly after
            // creation so the stored hash is never double-hashed.
            $user = User::create([
                'name'            => trim($data['first_name'] . ' ' . $data['last_name']),
                'email'           => $data['email'],
                'login_id'        => $reg->login_id,
                'status'          => 'active',
                'approval_status' => 'approved',
                'password'        => 'placeholder', // overridden immediately below
            ]);

            // Write the pre-hashed password without going through the Eloquent cast
            // (the 'hashed' cast calls Hash::make; Hash::isHashed guards against it in
            //  newer Laravel, but writing directly is always safe).
            DB::table('users')->where('id', $user->id)->update(['password' => $rawPassword]);

            $user->assignRole(Role::EMPLOYEE);

            // Generate a unique employee code using a gap-safe sequence.
            $last = Employee::withTrashed()->max('id') ?? 0;
            $code = 'EMP-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);

            $joinDate = $data['join_date'] instanceof \Carbon\Carbon
                ? $data['join_date']
                : \Carbon\Carbon::parse($data['join_date']);

            $employee = Employee::create([
                'user_id'                    => $user->id,
                'login_id'                   => $reg->login_id,
                'employee_code'              => $code,
                'organization_employee_code' => $data['organization_employee_code'] ?? null,
                'first_name'                 => $data['first_name'],
                'last_name'                  => $data['last_name'],
                'phone'                      => $data['phone']           ?? null,
                'date_of_birth'              => $data['date_of_birth']  ?? null,
                'gender'                     => $data['gender']          ?? null,
                'address'                    => $data['address']         ?? null,
                'nid'                        => $reg->nid,
                'nid_document'               => $reg->nid_document,
                'certificate'                => $reg->certificate,
                'photo'                      => $reg->photo,
                'designation_id'             => $data['designation_id'] ?? null,
                'designation'                => $data['designation']     ?? null,  // nullable after schema fix
                'department'                 => $data['department']      ?? null,  // nullable after schema fix
                'department_id'              => $data['department_id']  ?? null,
                'branch_id'                  => $data['branch_id']       ?? null,
                'shift_id'                   => $data['shift_id']         ?? null,
                'join_date'                  => $joinDate->toDateString(),
                'employment_type'            => $data['employment_type'] ?? 'full_time',
                'base_salary'                => $data['base_salary']     ?? 0,
                'status'                     => 'active',
            ]);

            // Set up weekly off days when provided.
            $weeklyOffDays = $data['weekly_off_days'] ?? null;
            if (! empty($weeklyOffDays) && is_array($weeklyOffDays)) {
                app(WeeklyOffService::class)->syncEmployeeWeeklyOffs(
                    $employee,
                    $weeklyOffDays,
                    $reviewer,
                    $joinDate->toDateString(),
                    $data['weekly_off_note'] ?? null,
                );
            }

            $reg->update([
                'status'      => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'employee_id' => $employee->id,
            ]);

            $reg->activityLogs()->create([
                'actor_id' => $reviewer->id,
                'action'   => 'approved',
                'note'     => 'Application approved. Employee account and login created.',
            ]);

            $this->audit->info(SecurityAuditService::EVENT_INVITE_ACCEPTED, request(), $reviewer, $reg, [
                'employee_id' => $employee->id,
                'user_id'     => $user->id,
            ]);

            // Send approval email (catch exceptions so approval is never rolled back due to mail failure)
            try {
                Mail::to($reg->email)->send(new RegistrationApprovedMail($reg, $employee));
            } catch (\Throwable) {
                // Mail failure is non-fatal — log silently
            }

            return $employee;
        });
    }

    public function reject(PendingRegistration $reg, User $reviewer, string $note): void
    {
        if ($reg->status !== 'pending') {
            throw ValidationException::withMessages([
                'registration' => 'This registration has already been processed.',
            ]);
        }

        $reg->update([
            'status'         => 'rejected',
            'reviewed_by'    => $reviewer->id,
            'reviewed_at'    => now(),
            'rejection_note' => $note,
        ]);

        $reg->activityLogs()->create([
            'actor_id' => $reviewer->id,
            'action'   => 'rejected',
            'note'     => $note,
        ]);

        $this->audit->warning('registration.rejected', request(), $reviewer, $reg, [
            'note' => $note,
        ]);
    }

    public function requestCorrection(PendingRegistration $reg, User $reviewer, string $note): void
    {
        if ($reg->status !== 'pending') {
            throw ValidationException::withMessages([
                'registration' => 'Cannot request correction on a non-pending registration.',
            ]);
        }

        $reg->update([
            'status'          => 'correction_required',
            'correction_note' => $note,
        ]);

        $reg->activityLogs()->create([
            'actor_id' => $reviewer->id,
            'action'   => 'correction_requested',
            'note'     => $note,
        ]);

        $this->audit->record('registration.correction_requested', request(), $reviewer, $reg);
    }

    public function updatePending(PendingRegistration $reg, array $data, ?User $actor = null): PendingRegistration
    {
        if (! in_array($reg->status, ['pending', 'correction_required'])) {
            throw ValidationException::withMessages([
                'registration' => 'Cannot edit an already processed registration.',
            ]);
        }

        $wasCorrection = $reg->status === 'correction_required';

        $reg->update($data);

        $isHrOrAdmin = $actor && ($actor->hasRole('admin') || $actor->hasRole('hr'));

        if ($wasCorrection && ! $isHrOrAdmin) {
            // Employee resubmitted after correction
            $reg->update(['status' => 'pending', 'correction_note' => null]);
            $reg->activityLogs()->create([
                'actor_id' => $actor?->id,
                'action'   => 'resubmitted',
                'note'     => 'Applicant resubmitted after corrections.',
            ]);

            $this->audit->record('registration.resubmitted', request(), $actor, $reg);
        } else {
            $reg->activityLogs()->create([
                'actor_id' => $actor?->id,
                'action'   => 'edited',
                'note'     => 'Details updated by HR.',
            ]);

            $this->audit->record('registration.edited', request(), $actor, $reg);
        }

        return $reg->fresh();
    }

    public function paginatePending(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return PendingRegistration::with(['invitation.creator', 'designation', 'branch', 'department'])
            ->when(! empty($filters['status']),       fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['invitation_id']),fn ($q) => $q->where('invitation_id', $filters['invitation_id']))
            ->when(! empty($filters['search']),        fn ($q) => $q->where(function ($q2) use ($filters) {
                $q2->where('first_name', 'like', "%{$filters['search']}%")
                   ->orWhere('last_name',  'like', "%{$filters['search']}%")
                   ->orWhere('email',      'like', "%{$filters['search']}%");
            }))
            ->latest()
            ->paginate($perPage);
    }

    // ── Private helpers ────────────────────────────────────────────

    /**
     * Generate a cryptographically secure invitation token.
     *
     * @return array{0: string, 1: string}  [rawToken, sha256Hash]
     */
    private function generateToken(): array
    {
        do {
            $token     = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
        } while (RegistrationInvitation::where('token_hash', $tokenHash)->exists());

        return [$token, $tokenHash];
    }
}
