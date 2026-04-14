<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegisterSession extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'company_id',
        'branch_id',
        'opening_balance',
        'closing_balance',
        'cash_sales_total',
        'cash_received_total',
        'change_returned_total',
        'expected_closing_balance',
        'variance',
        'status',
        'opened_at',
        'closed_at',
        'opened_by',
        'closed_by',
        'notes',
        'closing_notes',
    ];

    protected $casts = [
        'opening_balance'          => 'decimal:2',
        'closing_balance'          => 'decimal:2',
        'cash_sales_total'         => 'decimal:2',
        'cash_received_total'      => 'decimal:2',
        'change_returned_total'    => 'decimal:2',
        'expected_closing_balance' => 'decimal:2',
        'variance'                 => 'decimal:2',
        'opened_at'                => 'datetime',
        'closed_at'                => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'cash_register_session_id');
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function refreshTotals(): void
    {
        $cashTransactions = $this->transactions()
            ->where('transaction_type', Transaction::TYPE_PAYMENT)
            ->where('payment_method', Transaction::METHOD_CASH);

        $cashSalesTotal = round((float) $cashTransactions->sum('amount'), 2);
        $cashReceivedTotal = round((float) $cashTransactions->sum('amount_received'), 2);
        $changeReturnedTotal = round((float) $cashTransactions->sum('change_returned'), 2);

        $this->forceFill([
            'cash_sales_total' => $cashSalesTotal,
            'cash_received_total' => $cashReceivedTotal,
            'change_returned_total' => $changeReturnedTotal,
            'expected_closing_balance' => round((float) $this->opening_balance + $cashSalesTotal, 2),
        ])->save();
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
