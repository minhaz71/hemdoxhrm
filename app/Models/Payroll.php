<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'base_salary',
        'bonus',
        'incentive',
        'overtime_amount',
        'gross_salary',
        'late_deduction',
        'absent_deduction',
        'leave_deduction',
        'total_deductions',
        'net_salary',
        'working_days',
        'holiday_days',
        'weekly_off_days',
        'leave_days',
        'present_days',
        'absent_days',
        'late_days',
        'paid_leave_days',
        'unpaid_leave_days',
        'note',
        'status',
        'paid_at',
        'paid_by',
    ];

    protected $casts = [
        'paid_at'          => 'datetime',
        'base_salary'      => 'decimal:2',
        'bonus'            => 'decimal:2',
        'incentive'        => 'decimal:2',
        'overtime_amount'  => 'decimal:2',
        'gross_salary'     => 'decimal:2',
        'late_deduction'   => 'decimal:2',
        'absent_deduction' => 'decimal:2',
        'leave_deduction'  => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary'       => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function salarySnapshot(): HasOne
    {
        return $this->hasOne(SalarySnapshot::class);
    }

    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isLocked(): bool
    {
        return $this->status === 'paid';
    }

    public function getMonthLabelAttribute(): string
    {
        return \Carbon\Carbon::createFromDate($this->year, $this->month, 1)
            ->format('F Y');
    }
}
