<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class SalarySnapshot extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_id',
        'month',
        'year',
        'base_salary',
        'bonus',
        'incentive',
        'overtime',
        'gross_salary',
        'late_deduction',
        'absent_deduction',
        'leave_deduction',
        'total_deductions',
        'net_salary',
        'employee_code',
        'employee_name',
        'department',
        'designation',
        'is_locked',
        'locked_at',
        'locked_by',
    ];

    protected $casts = [
        'is_locked'        => 'boolean',
        'locked_at'        => 'datetime',
        'base_salary'      => 'decimal:2',
        'bonus'            => 'decimal:2',
        'incentive'        => 'decimal:2',
        'overtime'         => 'decimal:2',
        'gross_salary'     => 'decimal:2',
        'late_deduction'   => 'decimal:2',
        'absent_deduction' => 'decimal:2',
        'leave_deduction'  => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary'       => 'decimal:2',
    ];

    // ── Immutability enforcement ──────────────────────────────────

    // Block any update once the snapshot is locked
    protected static function booted(): void
    {
        static::updating(function (self $snapshot) {
            // Use getOriginal() so we check the value *before* this update, not
            // the potentially already-mutated in-memory value.  This allows the
            // one-time lock transition (is_locked: false → true) to succeed while
            // blocking any other field changes on an already-locked snapshot.
            if ($snapshot->getOriginal('is_locked') && ! $snapshot->isDirty('is_locked')) {
                throw new RuntimeException(
                    "SalarySnapshot #{$snapshot->id} is locked and cannot be modified."
                );
            }
        });

        // Block hard-delete on locked snapshots
        static::deleting(function (self $snapshot) {
            if ($snapshot->is_locked) {
                throw new RuntimeException(
                    "SalarySnapshot #{$snapshot->id} is locked and cannot be deleted."
                );
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    // ── Lock mechanism ────────────────────────────────────────────

    public function lock(User $user): void
    {
        if ($this->is_locked) {
            return; // idempotent
        }

        // Bypass the updating guard for this one allowed transition
        $this->is_locked = true;
        $this->locked_at = now();
        $this->locked_by = $user->id;

        // Use query builder directly to avoid triggering the model event guard
        static::withoutEvents(fn () => $this->save());
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getMonthLabelAttribute(): string
    {
        return \Carbon\Carbon::createFromDate($this->year, $this->month, 1)
            ->format('F Y');
    }

    public function getEarningsBreakdownAttribute(): array
    {
        return [
            'Base Salary' => $this->base_salary,
            'Bonus'       => $this->bonus,
            'Incentive'   => $this->incentive,
            'Overtime'    => $this->overtime,
            'Gross'       => $this->gross_salary,
        ];
    }

    public function getDeductionsBreakdownAttribute(): array
    {
        return [
            'Late Deduction'   => $this->late_deduction,
            'Absent Deduction' => $this->absent_deduction,
            'Leave Deduction'  => $this->leave_deduction,
            'Total'            => $this->total_deductions,
        ];
    }
}
