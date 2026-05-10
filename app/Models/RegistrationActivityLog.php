<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationActivityLog extends Model
{
    protected $fillable = [
        'pending_registration_id',
        'actor_id',
        'action',
        'note',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function registration(): BelongsTo
    {
        return $this->belongsTo(PendingRegistration::class, 'pending_registration_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'submitted'            => 'Application Submitted',
            'edited'               => 'Details Edited',
            'approved'             => 'Application Approved',
            'rejected'             => 'Application Rejected',
            'correction_requested' => 'Correction Requested',
            'resubmitted'          => 'Details Resubmitted',
            default                => ucfirst($this->action),
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'submitted', 'resubmitted' => 'primary',
            'edited'                   => 'info',
            'approved'                 => 'success',
            'rejected'                 => 'danger',
            'correction_requested'     => 'warning',
            default                    => 'secondary',
        };
    }
}
