<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeDoctorImport extends Model
{
    protected $fillable = [
        'imported_by',
        'original_filename',
        'stored_path',
        'file_hash',
        'headers',
        'total_rows',
        'processed_rows',
        'created_records',
        'updated_records',
        'attendance_synced',
        'skipped_rows',
        'errors',
        'imported_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'errors' => 'array',
        'imported_at' => 'datetime',
    ];

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(TimeDoctorDailyRecord::class);
    }
}
