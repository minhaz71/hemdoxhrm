<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryIncrementEmailLog extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_SENT    = 'sent';
    const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'employee_id',
        'salary_history_id',
        'email',
        'subject',
        'body',
        'status',
        'sent_by',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryHistory(): BelongsTo
    {
        return $this->belongsTo(SalaryHistory::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function wasSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_SENT    => 'success',
            self::STATUS_FAILED  => 'danger',
            default              => 'warning',
        };
    }
}
