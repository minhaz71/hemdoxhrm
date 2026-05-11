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
        'overtime_enabled',
        'gross_salary',
        'late_deduction',
        'absent_deduction',
        'leave_deduction',
        'total_deductions',
        'net_salary',
        'working_days',
        'management_working_days',
        'calendar_working_days',
        'holiday_days',
        'weekly_off_days',
        'leave_days',
        'present_days',
        'absent_days',
        'late_days',
        'late_penalty_enabled',
        'late_penalty_amount',
        'paid_leave_days',
        'unpaid_leave_days',
        'leave_penalty_enabled',
        'leave_penalty_rate',
        'note',
        'status',
        'paid_at',
        'paid_by',
        'email_sent_at',
        'email_sent_by',
        // Salary resolution audit
        'salary_resolution_mode',
        'salary_had_mid_change',
        'salary_segments',
    ];

    protected $casts = [
        'paid_at'               => 'datetime',
        'email_sent_at'         => 'datetime',
        'salary_had_mid_change' => 'boolean',
        'late_penalty_enabled'  => 'boolean',
        'leave_penalty_enabled' => 'boolean',
        'overtime_enabled'      => 'boolean',
        'salary_segments'       => 'array',
        'base_salary'      => 'decimal:2',
        'bonus'            => 'decimal:2',
        'incentive'        => 'decimal:2',
        'overtime_amount'  => 'decimal:2',
        'late_penalty_amount' => 'decimal:2',
        'leave_penalty_rate' => 'decimal:4',
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

    public function emailSentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email_sent_by');
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
