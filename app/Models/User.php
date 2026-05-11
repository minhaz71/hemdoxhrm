<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\LoginActivity;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'alternate_email', 'password', 'login_id', 'status', 'approval_status', 'force_password_reset', 'last_active_at'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Always eager-load roles so hasRole() / isAdmin() never trigger a lazy-load.
     * PermissionService::loadAll() also relies on roles being available.
     */
    protected $with = ['roles'];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'force_password_reset' => 'boolean',
            'last_active_at'       => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function passwordResetLogs(): HasMany
    {
        return $this->hasMany(AdminPasswordResetLog::class, 'target_user_id')->latest('created_at');
    }

    public function loginActivities(): HasMany
    {
        return $this->hasMany(LoginActivity::class)->latest('created_at');
    }

    // ── Convenience helpers ───────────────────────────────────────

    public function lastLogin(): ?LoginActivity
    {
        return $this->loginActivities()->where('event', 'login')->first();
    }

    public function lastLogout(): ?LoginActivity
    {
        return $this->loginActivities()->where('event', 'logout')->first();
    }

    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('granted')
            ->withTimestamps();
    }

    // ── Role Helpers ──────────────────────────────────────────────

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    public function assignRole(string $role): void
    {
        $roleModel = Role::where('name', $role)->firstOrFail();
        $this->roles()->syncWithoutDetaching($roleModel->id);
    }

    public function removeRole(string $role): void
    {
        $roleModel = Role::where('name', $role)->firstOrFail();
        $this->roles()->detach($roleModel->id);
    }

    public function syncRoles(array $roles): void
    {
        $ids = Role::whereIn('name', $roles)->pluck('id');
        $this->roles()->sync($ids);
    }

    public function isAdmin(): bool    { return $this->hasRole(Role::ADMIN); }
    public function isHR(): bool       { return $this->hasRole(Role::HR); }
    public function isManager(): bool  { return $this->hasRole(Role::MANAGER); }
    public function isEmployee(): bool { return $this->hasRole(Role::EMPLOYEE); }

    public function primaryRole(): ?string
    {
        return $this->roles->first()?->name;
    }
}
