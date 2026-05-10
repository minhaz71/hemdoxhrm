<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'is_headquarters',
        'is_active',
    ];

    protected $casts = [
        'is_headquarters' => 'boolean',
        'is_active'       => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function officeHours(): HasMany
    {
        return $this->hasMany(OfficeHour::class)->orderBy('day_of_week');
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

    public function getDisplayNameAttribute(): string
    {
        return $this->is_headquarters
            ? "{$this->name} (HQ)"
            : $this->name;
    }
}
