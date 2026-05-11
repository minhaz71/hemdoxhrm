<?php

namespace App\Policies;

use App\Models\PayrollRegenerationLog;
use App\Models\User;

class PayrollRegenerationLogPolicy
{
    /**
     * Admins and HR can view the regeneration log list.
     * Only admins can create (trigger) regeneration.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isHR();
    }

    /**
     * Only admins may trigger payroll regeneration.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }
}
