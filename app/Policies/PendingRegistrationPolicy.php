<?php

namespace App\Policies;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Services\PermissionService;

class PendingRegistrationPolicy
{
    public function __construct(private readonly PermissionService $permissions) {}

    private function can(User $user): bool
    {
        return $this->permissions->check($user, 'invitations.manage');
    }

    public function viewAny(User $user): bool
    {
        return $this->can($user);
    }

    public function view(User $user, PendingRegistration $registration): bool
    {
        return $this->can($user);
    }

    public function update(User $user, PendingRegistration $registration): bool
    {
        return $this->can($user) && in_array($registration->status, ['pending', 'correction_required'], true);
    }

    public function approve(User $user, PendingRegistration $registration): bool
    {
        // HR can approve both freshly-submitted and correction_required registrations.
        return $this->can($user)
            && in_array($registration->status, ['pending', 'correction_required'], true);
    }
}
