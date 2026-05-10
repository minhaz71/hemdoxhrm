<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeHour extends Model
{
    protected $fillable = [
        'branch_id',
        'day_of_week',
        'is_working',
        'open_time',
        'close_time',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_working'  => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public static function dayName(int $day): string
    {
        return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$day] ?? '—';
    }

    public static function shortDayName(int $day): string
    {
        return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][$day] ?? '—';
    }

    public function getDayNameAttribute(): string
    {
        return static::dayName($this->day_of_week);
    }

    public function getTimingLabelAttribute(): string
    {
        if (! $this->is_working) {
            return 'Closed';
        }
        $open  = $this->open_time  ? substr($this->open_time, 0, 5)  : '--:--';
        $close = $this->close_time ? substr($this->close_time, 0, 5) : '--:--';
        return "{$open} – {$close}";
    }
}
