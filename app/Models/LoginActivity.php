<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginActivity extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'event',
        'identifier',
        'ip_address',
        'user_agent',
        'browser',
        'browser_version',
        'device',
        'os',
        'os_version',
        'session_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getEventLabelAttribute(): string
    {
        return match($this->event) {
            'login'  => 'Login',
            'logout' => 'Logout',
            'failed' => 'Failed Attempt',
            default  => ucfirst($this->event),
        };
    }

    public function getEventColorAttribute(): string
    {
        return match($this->event) {
            'login'  => 'success',
            'logout' => 'secondary',
            'failed' => 'danger',
            default  => 'secondary',
        };
    }

    public function getEventIconAttribute(): string
    {
        return match($this->event) {
            'login'  => 'bi-box-arrow-in-right',
            'logout' => 'bi-box-arrow-right',
            'failed' => 'bi-shield-x',
            default  => 'bi-activity',
        };
    }

    public function getDeviceIconAttribute(): string
    {
        return match(strtolower($this->device ?? '')) {
            'mobile'  => 'bi-phone',
            'tablet'  => 'bi-tablet',
            default   => 'bi-laptop',
        };
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeLogins($q)    { return $q->where('event', 'login'); }
    public function scopeLogouts($q)   { return $q->where('event', 'logout'); }
    public function scopeFailed($q)    { return $q->where('event', 'failed'); }
    public function scopeRecent($q, int $days = 30)
    {
        return $q->where('created_at', '>=', now()->subDays($days));
    }
}
