<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEducation extends Model
{
    protected $table = 'employee_educations';

    protected $fillable = [
        'employee_id',
        'degree_type_id',
        'degree_name_id',
        'institute_name',
        'passing_year',
        'result',
        'board_university',
    ];

    protected $casts = [
        'passing_year' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function degreeType(): BelongsTo
    {
        return $this->belongsTo(DegreeType::class);
    }

    public function degreeName(): BelongsTo
    {
        return $this->belongsTo(DegreeName::class);
    }
}
