<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRegenerationLog extends Model
{
    protected $fillable = [
        'payroll_id',
        'employee_id',
        'month',
        'year',
        'was_locked',
        'old_snapshot',
        'new_snapshot',
        'reason',
        'regenerated_by',
    ];

    protected $casts = [
        'was_locked'   => 'boolean',
        'old_snapshot' => 'array',
        'new_snapshot' => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function regeneratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'regenerated_by');
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getMonthLabelAttribute(): string
    {
        return \Carbon\Carbon::createFromDate($this->year, $this->month, 1)->format('F Y');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }
}
