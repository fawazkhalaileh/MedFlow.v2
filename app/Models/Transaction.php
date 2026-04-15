<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Transaction extends Model
{
    public const TYPE_PAYMENT = 'payment';

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
        'cash_register_session_id',
        'amount',
        'amount_received',
        'change_returned',
        'transaction_type',
        'receipt_number',
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

    public function cashRegisterSession(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class);
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

    public function scopeCash($query)
    {
        return $query->where('payment_method', self::METHOD_CASH);
    }

    public function isPayment(): bool
    {
        return $this->transaction_type === self::TYPE_PAYMENT;
    }

    public function receiptFilename(): string
    {
        return ($this->receipt_number ?: 'receipt-' . $this->id) . '.pdf';
    }

    public static function makeReceiptNumber(?string $branchCode, Carbon|string $receivedAt, int $transactionId): string
    {
        $paddedTransactionId = str_pad((string) $transactionId, 6, '0', STR_PAD_LEFT);
        $normalizedBranchCode = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $branchCode));

        if (blank($normalizedBranchCode)) {
            return 'RCPT-' . $paddedTransactionId;
        }

        return sprintf(
            'RCPT-%s-%s-%s',
            $normalizedBranchCode,
            Carbon::parse($receivedAt)->format('Ymd'),
            $paddedTransactionId
        );
    }
}
