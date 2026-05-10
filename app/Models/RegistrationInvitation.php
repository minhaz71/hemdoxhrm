<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

class RegistrationInvitation extends Model
{
    protected $fillable = [
        'token', 'token_hash', 'created_by', 'email', 'note',
        'max_uses', 'uses_count',
        'expires_at', 'used_at', 'status',
        'abuse_blocked_at', 'view_count', 'last_viewed_ip',
    ];

    protected $casts = [
        'expires_at'       => 'datetime',
        'used_at'          => 'datetime',
        'abuse_blocked_at' => 'datetime',
        'max_uses'         => 'integer',
        'uses_count'       => 'integer',
        'view_count'       => 'integer',
    ];

    /**
     * Never expose the raw token or hash in serialized output.
     */
    protected $hidden = ['token', 'token_hash'];

    // ── Relationships ──────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** One invitation can now have many registrations (multi-use). */
    public function pendingRegistrations(): HasMany
    {
        return $this->hasMany(PendingRegistration::class, 'invitation_id');
    }

    // ── Validity ───────────────────────────────────────────────────

    /**
     * Returns true when this link can still accept a new registration.
     * Checks: active status, not expired, uses_count < max_uses (or unlimited).
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->expires_at->isPast())   return false;
        if ($this->isExhausted())          return false;

        return true;
    }

    /** True when max_uses is set and uses_count has reached it. */
    public function isExhausted(): bool
    {
        return $this->max_uses !== null && $this->uses_count >= $this->max_uses;
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getRegistrationUrlAttribute(): string
    {
        return URL::temporarySignedRoute('register.form', $this->expires_at, ['token' => $this->token]);
    }

    public function getSubmissionUrlAttribute(): string
    {
        return URL::temporarySignedRoute('register.submit', $this->expires_at, ['token' => $this->token]);
    }

    public function getExpiresLabelAttribute(): string
    {
        return $this->expires_at->diffForHumans();
    }

    /**
     * Human-readable uses label e.g. "3 / 10" or "3 / ∞".
     */
    public function getUsesLabelAttribute(): string
    {
        $max = $this->max_uses === null ? '∞' : $this->max_uses;
        return "{$this->uses_count} / {$max}";
    }

    /**
     * Remaining slots (null when unlimited, 0 when exhausted).
     */
    public function getRemainingUsesAttribute(): ?int
    {
        if ($this->max_uses === null) return null; // unlimited
        return max(0, $this->max_uses - $this->uses_count);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('expires_at', '>', now());
    }
}
