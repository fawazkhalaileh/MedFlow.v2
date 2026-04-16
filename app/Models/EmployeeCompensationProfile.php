<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeCompensationProfile extends Model
{
    use SoftDeletes;

    public const TYPE_SALARY_ONLY = 'salary_only';
    public const TYPE_COMMISSION_ONLY = 'commission_only';
    public const TYPE_SALARY_PLUS_COMMISSION = 'salary_plus_commission';

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'compensation_type',
        'fixed_salary',
        'effective_from',
        'effective_to',
        'is_active',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'fixed_salary' => 'decimal:2',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
