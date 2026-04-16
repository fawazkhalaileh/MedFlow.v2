<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeCommissionRule extends Model
{
    use SoftDeletes;

    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_BRANCH = 'branch';
    public const SCOPE_EMPLOYEE = 'employee';
    public const SCOPE_EMPLOYEE_BRANCH = 'employee_branch';

    public const SOURCE_COMPLETED_SERVICE = 'completed_service';
    public const SOURCE_PACKAGE_SALE = 'package_sale';
    public const SOURCE_PACKAGE_CONSUMPTION = 'package_consumption';
    public const SOURCE_PER_SESSION = 'per_session';

    public const CALC_PERCENTAGE = 'percentage';
    public const CALC_PER_SESSION = 'per_session';
    public const CALC_FIXED = 'fixed';

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'service_id',
        'package_id',
        'rule_scope',
        'source_type',
        'calculation_type',
        'rate',
        'flat_amount',
        'effective_from',
        'effective_to',
        'is_active',
        'priority',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'flat_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
