<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Package extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FROZEN = 'frozen';
    public const STATUS_EXHAUSTED = 'exhausted';
    public const STATUS_EXPIRED = 'expired';

    public const DISCOUNT_PERCENTAGE = 'percentage';
    public const DISCOUNT_FIXED = 'fixed';

    protected $fillable = [
        'company_id',
        'branch_id',
        'service_id',
        'name',
        'sessions_purchased',
        'original_price',
        'discount_type',
        'discount_value',
        'final_price',
        'expiry_date',
        'status',
        'created_by',
        'frozen_at',
        'unfrozen_at',
        'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'original_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'final_price' => 'decimal:2',
        'frozen_at' => 'datetime',
        'unfrozen_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (Package $package) {
            if ($package->isDirty(['original_price', 'discount_type', 'discount_value', 'final_price'])) {
                throw ValidationException::withMessages([
                    'pricing' => 'Package pricing is locked after creation.',
                ]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function patientPackages(): HasMany
    {
        return $this->hasMany(PatientPackage::class);
    }

    public function commissionRules(): HasMany
    {
        return $this->hasMany(EmployeeCommissionRule::class);
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_FROZEN,
            self::STATUS_EXHAUSTED,
            self::STATUS_EXPIRED,
        ];
    }

    public static function discountTypes(): array
    {
        return [
            self::DISCOUNT_PERCENTAGE,
            self::DISCOUNT_FIXED,
        ];
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function isFrozen(): bool
    {
        return $this->status === self::STATUS_FROZEN;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expiry_date !== null && $this->expiry_date->isPast());
    }
}
