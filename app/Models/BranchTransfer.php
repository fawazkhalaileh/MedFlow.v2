<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BranchTransfer extends Model
{
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_INTERNAL_SALE = 'internal_sale';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SENT = 'sent';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'source_branch_id',
        'destination_branch_id',
        'inventory_item_id',
        'source_branch_inventory_id',
        'destination_branch_inventory_id',
        'quantity',
        'internal_unit_price',
        'internal_total',
        'transfer_type',
        'status',
        'transferred_at',
        'approved_at',
        'sent_at',
        'received_at',
        'cancelled_at',
        'transferred_by',
        'approved_by',
        'sent_by',
        'received_by',
        'cancelled_by',
        'notes',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'internal_unit_price' => 'decimal:2',
        'internal_total' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sourceBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function sourceBranchInventory(): BelongsTo
    {
        return $this->belongsTo(BranchInventory::class, 'source_branch_inventory_id');
    }

    public function destinationBranchInventory(): BelongsTo
    {
        return $this->belongsTo(BranchInventory::class, 'destination_branch_inventory_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public static function transferTypes(): array
    {
        return [
            self::TYPE_TRANSFER,
            self::TYPE_INTERNAL_SALE,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_SENT,
            self::STATUS_RECEIVED,
            self::STATUS_CANCELLED,
        ];
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeSent(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED], true);
    }

    public function canBeReceived(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED], true);
    }
}
