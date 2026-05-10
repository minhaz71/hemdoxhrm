<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminPasswordResetLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'target_user_id',
        'action',
        'ip_address',
        'note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'reset_link_sent'      => 'Password Reset Link Sent',
            'password_reset'       => 'Password Reset Directly',
            'force_reset_enabled'  => 'Force Reset Enabled',
            'force_reset_disabled' => 'Force Reset Disabled',
            default                => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            'reset_link_sent'      => 'bi-envelope-check',
            'password_reset'       => 'bi-key',
            'force_reset_enabled'  => 'bi-shield-exclamation',
            'force_reset_disabled' => 'bi-shield-check',
            default                => 'bi-activity',
        };
    }

    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'reset_link_sent'      => 'info',
            'password_reset'       => 'warning',
            'force_reset_enabled'  => 'danger',
            'force_reset_disabled' => 'success',
            default                => 'secondary',
        };
    }
}
