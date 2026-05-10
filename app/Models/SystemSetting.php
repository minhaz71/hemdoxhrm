<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key', 'group', 'type', 'sort_order',
        'value', 'label', 'description',
    ];

    // ── Typed value accessor ──────────────────────────────────────
    // Coerces stored string values to their natural PHP types.

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->value) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            default => $this->value,
        };
    }
}
