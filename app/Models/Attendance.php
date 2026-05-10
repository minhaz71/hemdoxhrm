<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'note',
        'source',
        'time_doctor_worked_minutes',
        'time_doctor_idle_minutes',
    ];

    protected $casts = [
        'date' => 'date',
        'time_doctor_worked_minutes' => 'integer',
        'time_doctor_idle_minutes' => 'integer',
    ];

    // Late threshold: 09:00 AM
    public const LATE_THRESHOLD = '09:00:00';

    // ── Relationships ─────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeForDate($query, string $date)
    {
        return $query->where('date', $date);
    }

    public function scopeToday($query)
    {
        return $query->where('date', today()->toDateString());
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', ['present', 'late', 'underworked']);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getWorkingHoursAttribute(): ?string
    {
        if ($this->time_doctor_worked_minutes !== null) {
            $mins = $this->time_doctor_worked_minutes;

            return sprintf('%dh %02dm', intdiv($mins, 60), $mins % 60);
        }

        if (! $this->check_out) {
            return null;
        }

        $in  = \Carbon\Carbon::parse($this->check_in);
        $out = \Carbon\Carbon::parse($this->check_out);

        $mins = $in->diffInMinutes($out);

        return sprintf('%dh %02dm', intdiv($mins, 60), $mins % 60);
    }
}
