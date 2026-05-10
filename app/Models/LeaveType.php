<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = ['name', 'slug', 'days_allowed', 'is_paid', 'is_active'];

    protected $casts = [
        'is_paid'   => 'boolean',
        'is_active' => 'boolean',
    ];

    public const PAID   = 'paid';
    public const SICK   = 'sick';
    public const UNPAID = 'unpaid';

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
