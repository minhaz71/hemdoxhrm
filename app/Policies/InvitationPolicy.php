<?php

namespace App\Policies;

use App\Models\RegistrationInvitation;
use App\Models\User;
use App\Services\PermissionService;

class InvitationPolicy
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

    public function create(User $user): bool
    {
        return $this->can($user);
    }

    public function delete(User $user, RegistrationInvitation $invitation): bool
    {
        return $this->can($user);
    }
}
