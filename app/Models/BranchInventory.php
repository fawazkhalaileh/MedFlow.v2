<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BranchInventory extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'inventory_item_id',
        'low_stock_threshold',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(BranchTransfer::class, 'source_branch_inventory_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(BranchTransfer::class, 'destination_branch_inventory_id');
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function getCurrentStockAttribute(): int
    {
        if ($this->relationLoaded('batches')) {
            return (int) $this->batches->sum('quantity_remaining');
        }

        return (int) $this->batches()->sum('quantity_remaining');
    }

    public function getLowStockAttribute(): bool
    {
        return $this->current_stock <= (int) $this->low_stock_threshold;
    }

    public function getNearestExpiryAttribute(): ?string
    {
        $batch = $this->relationLoaded('batches')
            ? $this->batches
                ->where('quantity_remaining', '>', 0)
                ->whereNotNull('expires_on')
                ->sortBy('expires_on')
                ->first()
            : $this->batches()
                ->where('quantity_remaining', '>', 0)
                ->whereNotNull('expires_on')
                ->orderBy('expires_on')
                ->value('expires_on');

        if (is_string($batch)) {
            return $batch;
        }

        return $batch?->expires_on?->toDateString();
    }
}
