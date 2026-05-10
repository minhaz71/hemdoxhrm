<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PendingRegistration extends Model
{
    protected $fillable = [
        'invitation_id',
        'first_name', 'last_name', 'email', 'organization_employee_code', 'phone',
        'date_of_birth', 'gender', 'address',
        'nid', 'nid_document', 'certificate', 'photo',
        'designation_id', 'department_id', 'branch_id', 'shift_id',
        'weekly_off_days', 'weekly_off_note',
        'department', 'join_date', 'employment_type', 'base_salary',
        'login_id', 'password',
        'status', 'reviewed_by', 'reviewed_at', 'rejection_note', 'correction_note', 'employee_id',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'date_of_birth' => 'date',
        'join_date'     => 'date',
        'reviewed_at'   => 'datetime',
        'base_salary'   => 'decimal:2',
        'changes'       => 'array',
        'weekly_off_days' => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(RegistrationInvitation::class, 'invitation_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(RegistrationActivityLog::class)->orderBy('created_at');
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
