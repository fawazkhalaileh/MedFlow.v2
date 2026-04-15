<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientPackage extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FROZEN = 'frozen';
    public const STATUS_EXHAUSTED = 'exhausted';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'company_id',
        'branch_id',
        'package_id',
        'patient_id',
        'sessions_purchased',
        'sessions_used',
        'final_price',
        'expiry_date',
        'status',
        'purchased_at',
        'purchased_by',
        'notes',
    ];

    protected $casts = [
        'final_price' => 'decimal:2',
        'expiry_date' => 'date',
        'purchased_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function purchasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PackageUsage::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function getRemainingSessionsAttribute(): int
    {
        return max(0, (int) $this->sessions_purchased - (int) $this->sessions_used);
    }

    public function isUsable(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->remaining_sessions > 0
            && (!$this->expiry_date || !$this->expiry_date->isPast())
            && !$this->package?->isFrozen()
            && !$this->package?->isExpired();
    }
}
