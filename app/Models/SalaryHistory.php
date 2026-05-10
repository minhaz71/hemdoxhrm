<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryHistory extends Model
{
    protected $fillable = [
        'employee_id',
        'base_salary',
        'effective_from',
        'effective_to',
        'changed_by',
        'note',
    ];

    protected $casts = [
        'base_salary'    => 'decimal:2',
        'effective_from' => 'date',
        'effective_to'   => 'date',
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

    // ── Scopes ────────────────────────────────────────────────────

    /**
     * Return the salary record effective during a given month/year.
     * effective_from <= first-day-of-month AND (effective_to is null OR >= first-day-of-month)
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        $periodStart = Carbon::createFromDate($year, $month, 1)->toDateString();

        return $query
            ->where('effective_from', '<=', $periodStart)
            ->where(fn ($q) => $q->whereNull('effective_to')
                ->orWhere('effective_to', '>=', $periodStart));
    }
}
