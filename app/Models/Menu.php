<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'label', 'icon', 'route', 'route_pattern',
        'permissions', 'roles',
        'sort_order', 'is_active', 'is_system', 'type',
    ];

    protected $casts = [
        'permissions' => 'array',
        'roles'       => 'array',
        'is_active'   => 'boolean',
        'is_system'   => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /** Safe route URL — returns '#' if the route name doesn't exist. */
    public function url(): string
    {
        if (! $this->route) return '#';
        try {
            return route($this->route);
        } catch (\Throwable) {
            return '#';
        }
    }

    /** Is this item currently active based on the request URI? */
    public function isActive(): bool
    {
        if ($this->route_pattern) {
            return request()->routeIs($this->route_pattern);
        }
        if ($this->route) {
            return request()->routeIs($this->route);
        }
        return false;
    }

    public function userOverrides(): HasMany
    {
        return $this->hasMany(UserMenuOverride::class);
    }
}
