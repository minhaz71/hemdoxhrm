<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DegreeType extends Model
{
    protected $fillable = ['name', 'status'];

    public function degreeNames(): HasMany
    {
        return $this->hasMany(DegreeName::class);
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
