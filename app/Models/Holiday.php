<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Holiday extends Model
{
    protected $fillable = [
        'title',
        'reason',
        'holiday_year',
        'start_date',
        'end_date',
        'type',
        'branch_id',
        'department_id',
        'status',
        'notify_before_days',
        'email_sent_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'email_sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'holiday_employees')->withTimestamps();
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(HolidayEmailLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('holiday_year', $year);
    }

    public function scopeBetweenDates(Builder $query, string $date): Builder
    {
        return $query->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('type', 'global');
    }

    public function scopeEmployeeSpecific(Builder $query): Builder
    {
        return $query->where('type', 'employee_specific');
    }
}
