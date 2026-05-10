<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'status',
        'is_unpaid_override',
        'reason',
        'rejection_note',
        'approved_by',
        'actioned_at',
    ];

    protected $casts = [
        'start_date'         => 'date',
        'end_date'           => 'date',
        'actioned_at'        => 'datetime',
        'is_unpaid_override' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('start_date', $year);
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getEffectiveTypeNameAttribute(): string
    {
        if ($this->is_unpaid_override) {
            return 'Unpaid (limit exceeded)';
        }
        return $this->leaveType->name ?? '—';
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->leaveType?->is_paid && ! $this->is_unpaid_override;
    }
}
