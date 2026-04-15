<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'code', 'email', 'phone', 'address',
        'city', 'country', 'working_hours', 'services_offered',
        'manager_id', 'status', 'notes', 'settings',
    ];

    protected $casts = [
        'working_hours'    => 'array',
        'services_offered' => 'array',
        'settings'         => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branch_roles')
                    ->withPivot('role_id', 'is_primary')
                    ->withTimestamps();
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'branch_services')
                    ->withPivot('price_override', 'is_active');
    }

    public function cashRegisterSessions(): HasMany
    {
        return $this->hasMany(CashRegisterSession::class);
    }

    public function branchInventories(): HasMany
    {
        return $this->hasMany(BranchInventory::class);
    }

    public function outgoingInventoryTransfers(): HasMany
    {
        return $this->hasMany(BranchTransfer::class, 'source_branch_id');
    }

    public function incomingInventoryTransfers(): HasMany
    {
        return $this->hasMany(BranchTransfer::class, 'destination_branch_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function patientPackages(): HasMany
    {
        return $this->hasMany(PatientPackage::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function patientAttachments(): HasMany
    {
        return $this->hasMany(PatientAttachment::class);
    }

    public function packageUsages(): HasMany
    {
        return $this->hasMany(PackageUsage::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
