<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DegreeName extends Model
{
    protected $fillable = ['name', 'degree_type_id', 'status'];

    public function degreeType(): BelongsTo
    {
        return $this->belongsTo(DegreeType::class);
    }

    public function employeeEducations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
