<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryHistory extends Model
{
    // ── Type constants ─────────────────────────────────────────────
    const TYPE_INITIAL    = 'initial';
    const TYPE_INCREMENT  = 'increment';
    const TYPE_DECREMENT  = 'decrement';
    const TYPE_ADJUSTMENT = 'adjustment';

    // ── Status constants ───────────────────────────────────────────
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // ── Type labels for UI ─────────────────────────────────────────
    const TYPE_LABELS = [
        self::TYPE_INITIAL    => 'Initial Salary',
        self::TYPE_INCREMENT  => 'Increment',
        self::TYPE_DECREMENT  => 'Decrement',
        self::TYPE_ADJUSTMENT => 'Adjustment',
    ];

    // ── Type badge colours (Bootstrap) ────────────────────────────
    const TYPE_BADGES = [
        self::TYPE_INITIAL    => 'secondary',
        self::TYPE_INCREMENT  => 'success',
        self::TYPE_DECREMENT  => 'danger',
        self::TYPE_ADJUSTMENT => 'warning',
    ];

    // ── Status badge colours ───────────────────────────────────────
    const STATUS_BADGES = [
        self::STATUS_PENDING  => 'warning',
        self::STATUS_APPROVED => 'success',
        self::STATUS_REJECTED => 'danger',
    ];

    protected $fillable = [
        'employee_id',
        'previous_salary',
        'base_salary',        // = new/effective salary amount
        'increment_amount',
        'increment_percentage',
        'salary_type',
        'effective_from',
        'effective_to',
        'reason',
        'note',
        'changed_by',
        'approved_by',
        'status',
    ];

    protected $casts = [
        'previous_salary'     => 'decimal:2',
        'base_salary'         => 'decimal:2',
        'increment_amount'    => 'decimal:2',
        'increment_percentage'=> 'decimal:4',
        'effective_from'      => 'date',
        'effective_to'        => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ────────────────────────────────────────────────────

    /** Only approved records — these are the ones payroll trusts. */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Salary record that was effective on the first day of the given month/year.
     * Only approved records are considered.
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        $periodStart = Carbon::createFromDate($year, $month, 1)->toDateString();

        return $query
            ->approved()
            ->where('effective_from', '<=', $periodStart)
            ->where(fn ($q) => $q->whereNull('effective_to')
                ->orWhere('effective_to', '>=', $periodStart));
    }

    /** Records effective today or in the future (scheduled). */
    public function scopeFuture($query)
    {
        return $query->approved()->where('effective_from', '>', now()->toDateString());
    }

    /** Records effective on or before today. */
    public function scopeCurrent($query)
    {
        return $query->approved()->where('effective_from', '<=', now()->toDateString());
    }

    // ── Accessors ─────────────────────────────────────────────────

    /** The effective salary amount (alias for base_salary). */
    public function getNewSalaryAttribute(): float
    {
        return (float) $this->base_salary;
    }

    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->salary_type] ?? ucfirst($this->salary_type);
    }

    public function getTypeBadge(): string
    {
        return self::TYPE_BADGES[$this->salary_type] ?? 'secondary';
    }

    public function getStatusBadge(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'secondary';
    }

    /** Net change vs previous salary (positive = increment, negative = decrement). */
    public function getDeltaAttribute(): float
    {
        if ($this->previous_salary === null) return 0;
        return (float) $this->base_salary - (float) $this->previous_salary;
    }

    /** Delta as a formatted percentage string (e.g. +12.5%). */
    public function getDeltaPercentAttribute(): string
    {
        if (! $this->previous_salary || (float) $this->previous_salary == 0) return '—';
        $pct = (((float) $this->base_salary - (float) $this->previous_salary) / (float) $this->previous_salary) * 100;
        return ($pct >= 0 ? '+' : '') . number_format($pct, 1) . '%';
    }

    public function isActive(): bool
    {
        $today = now()->toDateString();
        return $this->status === self::STATUS_APPROVED
            && $this->effective_from->toDateString() <= $today
            && ($this->effective_to === null || $this->effective_to->toDateString() >= $today);
    }

    public function isFuture(): bool
    {
        return $this->status === self::STATUS_APPROVED
            && $this->effective_from->toDateString() > now()->toDateString();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Whether this record has been used in any generated payroll.
     * If true, it must not be deleted.
     */
    public function usedInPayroll(): bool
    {
        if (! $this->effective_from) return false;

        $fromPeriod = (int) $this->effective_from->format('Ym');
        $toPeriod = $this->effective_to ? (int) $this->effective_to->format('Ym') : null;

        return \App\Models\Payroll::where('employee_id', $this->employee_id)
            ->whereRaw('(year * 100 + month) >= ?', [$fromPeriod])
            ->when($toPeriod, fn ($query) => $query->whereRaw('(year * 100 + month) <= ?', [$toPeriod]))
            ->exists();
    }
}
