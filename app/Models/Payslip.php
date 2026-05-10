<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_id',
        'employee_id',
        'month',
        'year',
        'file_path',
        'file_name',
        'file_size',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
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

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
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

    // ── Accessors ─────────────────────────────────────────────────

    public function getDownloadUrlAttribute(): string
    {
        return route('payslips.download', $this);
    }

    public function getMonthLabelAttribute(): string
    {
        return \Carbon\Carbon::createFromDate($this->year, $this->month, 1)->format('F Y');
    }

    public function getFileSizeHumanAttribute(): string
    {
        if (! $this->file_size) return '—';
        return number_format($this->file_size / 1024, 1) . ' KB';
    }

    public function fileExists(): bool
    {
        return Storage::disk('local')->exists($this->file_path);
    }
}
