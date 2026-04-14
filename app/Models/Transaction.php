<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    public const METHOD_CASH = 'cash';
    public const METHOD_CARD = 'card';
    public const METHOD_TRANSFER = 'transfer';
    public const METHOD_INSURANCE = 'insurance';

    protected $fillable = [
        'company_id',
        'branch_id',
        'patient_id',
        'treatment_plan_id',
        'appointment_id',
        'amount',
        'amount_received',
        'change_returned',
        'payment_method',
        'reference_number',
        'received_at',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'amount_received'  => 'decimal:2',
        'change_returned'  => 'decimal:2',
        'received_at'      => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public static function paymentMethods(): array
    {
        return [
            self::METHOD_CASH,
            self::METHOD_CARD,
            self::METHOD_TRANSFER,
            self::METHOD_INSURANCE,
        ];
    }
}
