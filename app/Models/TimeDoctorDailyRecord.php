<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeDoctorDailyRecord extends Model
{
    protected $fillable = [
        'time_doctor_import_id',
        'employee_id',
        'attendance_id',
        'leave_id',
        'name',
        'email',
        'time_doctor_employee_id',
        'user_groups',
        'work_date',
        'time_tracked_minutes',
        'idle_minutes',
        'idle_minutes_percent',
        'start_time',
        'end_time',
        'active_minutes',
        'productive_minutes',
        'activity_percent',
        'productivity_percentage',
        'attendance_status',
        'worked_on_leave',
        'penalty_warning_sent_at',
        'raw_row',
    ];

    protected $casts = [
        'work_date' => 'date',
        'idle_minutes_percent' => 'decimal:2',
        'activity_percent' => 'decimal:2',
        'productivity_percentage' => 'decimal:2',
        'worked_on_leave' => 'boolean',
        'penalty_warning_sent_at' => 'datetime',
        'raw_row' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(TimeDoctorImport::class, 'time_doctor_import_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class);
    }
}
