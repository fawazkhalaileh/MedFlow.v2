<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'category_id', 'name', 'description',
        'duration_minutes', 'price', 'is_active', 'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price'     => 'decimal:2',
        'settings'  => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_services')
                    ->withPivot('price_override', 'is_active');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function packageUsages(): HasMany
    {
        return $this->hasMany(PackageUsage::class);
    }

    public function commissionRules(): HasMany
    {
        return $this->hasMany(EmployeeCommissionRule::class);
    }

    public function workAttributions(): HasMany
    {
        return $this->hasMany(WorkAttribution::class);
    }
}
