<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'start_time',
        'end_time',
        'break_minutes',
        'working_days',
        'is_overnight',
        'color',
        'is_active',
    ];

    protected $casts = [
        'working_days'  => 'array',
        'is_overnight'  => 'boolean',
        'is_active'     => 'boolean',
        'break_minutes' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** "09:00 – 17:00 (8h)" */
    public function getTimingLabelAttribute(): string
    {
        $start = substr($this->start_time, 0, 5);
        $end   = substr($this->end_time,   0, 5);
        $hours = $this->totalWorkMinutes() / 60;
        return "{$start} – {$end} ({$hours}h)";
    }

    /** Working minutes excluding break */
    public function totalWorkMinutes(): int
    {
        [$sh, $sm] = array_map('intval', explode(':', $this->start_time));
        [$eh, $em] = array_map('intval', explode(':', $this->end_time));

        $startMins = $sh * 60 + $sm;
        $endMins   = $eh * 60 + $em;

        if ($this->is_overnight && $endMins <= $startMins) {
            $endMins += 1440; // add 24 hours
        }

        return max(0, $endMins - $startMins - $this->break_minutes);
    }

    /** Day names for working_days array */
    public function getWorkingDayNamesAttribute(): string
    {
        if (empty($this->working_days)) {
            return '—';
        }
        $map = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];
        return implode(', ', array_map(fn($d) => $map[$d] ?? $d, $this->working_days));
    }
}
