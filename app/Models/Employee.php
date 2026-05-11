<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'login_id',
        'branch_id',
        'department_id',
        'shift_id',
        'team_leader_id',
        'employee_code',
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'nid',
        'nid_document',
        'certificate',
        'photo',
        'department',
        'designation',      // legacy string — kept in sync by EmployeeService
        'designation_id',   // FK to designations table
        'join_date',
        'employment_type',
        'status',
        'base_salary',
        'organization_employee_code',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'join_date'     => 'date',
        'base_salary'   => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'team_leader_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(Employee::class, 'team_leader_id');
    }

    public function designationModel(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function salarySnapshots(): HasMany
    {
        return $this->hasMany(SalarySnapshot::class)->orderByDesc('year')->orderByDesc('month');
    }

    public function salaryHistories(): HasMany
    {
        return $this->hasMany(SalaryHistory::class)->orderByDesc('effective_from');
    }

    /**
     * Return the correct base salary for a given payroll month/year.
     * Looks up salary_histories; falls back to the current base_salary field
     * if no history row is found (e.g. legacy employees with no history seeded).
     */
    public function salaryForPeriod(int $month, int $year): float
    {
        $periodStart = Carbon::createFromDate($year, $month, 1)->toDateString();

        $history = $this->salaryHistories()
            ->where('status', \App\Models\SalaryHistory::STATUS_APPROVED)
            ->where('effective_from', '<=', $periodStart)
            ->where(fn ($q) => $q->whereNull('effective_to')
                ->orWhere('effective_to', '>=', $periodStart))
            ->orderByDesc('effective_from')
            ->first();

        return $history
            ? (float) $history->base_salary
            : (float) $this->base_salary;
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function weeklyOffs(): HasMany
    {
        return $this->hasMany(EmployeeWeeklyOff::class);
    }

    public function holidays()
    {
        return $this->belongsToMany(Holiday::class, 'holiday_employees')->withTimestamps();
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class)->orderByDesc('passing_year');
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getIsTerminatedAttribute(): bool
    {
        return $this->status === 'terminated';
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTerminated($query)
    {
        return $query->where('status', 'terminated');
    }
}
