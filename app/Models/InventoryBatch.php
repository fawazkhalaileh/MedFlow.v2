<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    protected $fillable = [
        'company_id',
        'branch_inventory_id',
        'batch_number',
        'expires_on',
        'received_on',
        'quantity_received',
        'quantity_remaining',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'expires_on' => 'date',
        'received_on' => 'date',
        'quantity_received' => 'decimal:2',
        'quantity_remaining' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branchInventory(): BelongsTo
    {
        return $this->belongsTo(BranchInventory::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('quantity_remaining', '>', 0);
    }

    public function getQuantityReceivedAttribute($value): int|float
    {
        return $this->normalizeQuantity((float) $value);
    }

    public function getQuantityRemainingAttribute($value): int|float
    {
        return $this->normalizeQuantity((float) $value);
    }

    private function normalizeQuantity(float $quantity): int|float
    {
        $rounded = round($quantity, 2);

        return fmod($rounded, 1.0) === 0.0
            ? (int) $rounded
            : $rounded;
    }
}
