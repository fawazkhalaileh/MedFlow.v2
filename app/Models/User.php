<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'company_id', 'employee_id', 'name', 'first_name', 'last_name',
        'email', 'password', 'phone', 'gender', 'date_of_birth', 'address',
        'employee_type', 'employment_status', 'hire_date', 'profile_photo',
        'employee_notes', 'certifications', 'specialties',
        'primary_branch_id', 'role', 'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth'     => 'date',
            'hire_date'         => 'date',
            'password'          => 'hashed',
            'certifications'    => 'array',
            'specialties'       => 'array',
        ];
    }

    // --- Computed attributes ---

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: $this->name;
    }

    // --- Relationships ---

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function primaryBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'primary_branch_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branch_roles')
                    ->withPivot('role_id', 'is_primary')
                    ->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_branch_roles')
                    ->withPivot('branch_id', 'is_primary');
    }

    public function managedBranches(): HasMany
    {
        return $this->hasMany(Branch::class, 'manager_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'assigned_staff_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // --- Permission helpers ---

    public function hasRole(string $roleName, ?int $branchId = null): bool
    {
        return $this->roles()
            ->where('roles.name', $roleName)
            ->when($branchId, fn($q) => $q->wherePivot('branch_id', $branchId))
            ->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function can($abilities, $arguments = []): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return parent::can($abilities, $arguments);
    }

    public function isActive(): bool
    {
        return $this->employment_status === 'active';
    }
}
