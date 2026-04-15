<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    public const TYPE_STOCK_IN = 'stock_in';
    public const TYPE_USAGE = 'usage';
    public const TYPE_WASTE = 'waste';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_TRANSFER_IN = 'transfer_in';

    protected $fillable = [
        'company_id',
        'branch_id',
        'inventory_item_id',
        'branch_inventory_id',
        'inventory_batch_id',
        'branch_transfer_id',
        'patient_id',
        'movement_type',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'occurred_at',
        'performed_by',
        'notes',
        'meta',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function branchInventory(): BelongsTo
    {
        return $this->belongsTo(BranchInventory::class);
    }

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    public function branchTransfer(): BelongsTo
    {
        return $this->belongsTo(BranchTransfer::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
