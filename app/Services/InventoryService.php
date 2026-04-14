<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\BranchTransfer;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function addStock(
        User $user,
        InventoryItem $inventoryItem,
        Branch $branch,
        int $quantity,
        ?string $batchNumber,
        ?string $expiresOn,
        string $receivedOn,
        ?int $lowStockThreshold = null,
        ?string $notes = null,
        ?string $unitCost = null
    ): InventoryBatch {
        return DB::transaction(function () use (
            $user,
            $inventoryItem,
            $branch,
            $quantity,
            $batchNumber,
            $expiresOn,
            $receivedOn,
            $lowStockThreshold,
            $notes,
            $unitCost
        ) {
            $branchInventory = BranchInventory::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'inventory_item_id' => $inventoryItem->id,
                ],
                [
                    'company_id' => $inventoryItem->company_id,
                    'low_stock_threshold' => max(0, (int) ($lowStockThreshold ?? 5)),
                ]
            );

            if ($lowStockThreshold !== null && $branchInventory->low_stock_threshold !== $lowStockThreshold) {
                $branchInventory->update(['low_stock_threshold' => max(0, $lowStockThreshold)]);
            }

            $batch = InventoryBatch::create([
                'company_id' => $inventoryItem->company_id,
                'branch_inventory_id' => $branchInventory->id,
                'batch_number' => $batchNumber ?: null,
                'expires_on' => $expiresOn,
                'received_on' => $receivedOn,
                'quantity_received' => $quantity,
                'quantity_remaining' => $quantity,
                'unit_cost' => $unitCost,
                'notes' => $notes,
            ]);

            InventoryMovement::create([
                'company_id' => $inventoryItem->company_id,
                'branch_id' => $branch->id,
                'inventory_item_id' => $inventoryItem->id,
                'branch_inventory_id' => $branchInventory->id,
                'inventory_batch_id' => $batch->id,
                'movement_type' => InventoryMovement::TYPE_STOCK_IN,
                'quantity_change' => $quantity,
                'quantity_before' => 0,
                'quantity_after' => $quantity,
                'occurred_at' => now(),
                'performed_by' => $user->id,
                'notes' => $notes,
                'meta' => [
                    'batch_number' => $batch->batch_number,
                    'expires_on' => $batch->expires_on?->toDateString(),
                    'received_on' => $batch->received_on?->toDateString(),
                    'unit_cost' => $batch->unit_cost,
                ],
            ]);

            ActivityLog::record(
                'inventory_stock_added',
                $batch,
                "Added {$quantity} {$inventoryItem->unit} of {$inventoryItem->name} to {$branch->name}.",
                [],
                [
                    'branch_id' => $branch->id,
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity_added' => $quantity,
                    'expires_on' => $batch->expires_on?->toDateString(),
                ]
            );

            return $batch->load('branchInventory.inventoryItem', 'branchInventory.branch');
        });
    }

    public function deductStock(
        User $user,
        BranchInventory $branchInventory,
        int $quantity,
        string $movementType = InventoryMovement::TYPE_USAGE,
        ?string $notes = null,
        ?BranchTransfer $branchTransfer = null
    ): Collection {
        return DB::transaction(function () use ($user, $branchInventory, $quantity, $movementType, $notes, $branchTransfer) {
            $availableBatches = $branchInventory->batches()
                ->available()
                ->orderByRaw('case when expires_on is null then 1 else 0 end')
                ->orderBy('expires_on')
                ->orderBy('received_on')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $availableQuantity = (int) $availableBatches->sum('quantity_remaining');

            if ($availableQuantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$availableQuantity} {$branchInventory->inventoryItem->unit} available for {$branchInventory->inventoryItem->name}.",
                ]);
            }

            $remainingToDeduct = $quantity;
            $deductions = collect();

            foreach ($availableBatches as $batch) {
                if ($remainingToDeduct <= 0) {
                    break;
                }

                $take = min($remainingToDeduct, (int) $batch->quantity_remaining);
                $before = (int) $batch->quantity_remaining;
                $after = $before - $take;

                $batch->update([
                    'quantity_remaining' => $after,
                ]);

                InventoryMovement::create([
                    'company_id' => $branchInventory->company_id,
                    'branch_id' => $branchInventory->branch_id,
                    'inventory_item_id' => $branchInventory->inventory_item_id,
                    'branch_inventory_id' => $branchInventory->id,
                    'inventory_batch_id' => $batch->id,
                    'branch_transfer_id' => $branchTransfer?->id,
                    'movement_type' => $movementType,
                    'quantity_change' => $take * -1,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'occurred_at' => now(),
                    'performed_by' => $user->id,
                    'notes' => $notes,
                    'meta' => [
                        'batch_number' => $batch->batch_number,
                        'expires_on' => $batch->expires_on?->toDateString(),
                        'received_on' => $batch->received_on?->toDateString(),
                        'unit_cost' => $batch->unit_cost,
                    ],
                ]);

                $deductions->push([
                    'batch' => $batch->fresh(),
                    'quantity' => $take,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                ]);

                $remainingToDeduct -= $take;
            }

            if ($movementType === InventoryMovement::TYPE_USAGE) {
                ActivityLog::record(
                    'inventory_used',
                    $branchInventory,
                    "Used {$quantity} {$branchInventory->inventoryItem->unit} of {$branchInventory->inventoryItem->name}.",
                    [],
                    [
                        'branch_inventory_id' => $branchInventory->id,
                        'quantity_used' => $quantity,
                    ]
                );
            }

            return $deductions;
        });
    }

    public function createTransfer(
        User $user,
        BranchInventory $sourceInventory,
        Branch $destinationBranch,
        int $quantity,
        string $transferType,
        ?string $internalUnitPrice = null,
        ?string $notes = null
    ): BranchTransfer {
        if ($sourceInventory->branch_id === $destinationBranch->id) {
            throw ValidationException::withMessages([
                'destination_branch_id' => 'Destination branch must be different from the source branch.',
            ]);
        }

        return DB::transaction(function () use ($user, $sourceInventory, $destinationBranch, $quantity, $transferType, $internalUnitPrice, $notes) {
            $sourceInventory->loadMissing('inventoryItem', 'branch');

            $destinationInventory = BranchInventory::firstOrCreate(
                [
                    'branch_id' => $destinationBranch->id,
                    'inventory_item_id' => $sourceInventory->inventory_item_id,
                ],
                [
                    'company_id' => $sourceInventory->company_id,
                    'low_stock_threshold' => $sourceInventory->low_stock_threshold,
                ]
            );

            $transfer = BranchTransfer::create([
                'company_id' => $sourceInventory->company_id,
                'source_branch_id' => $sourceInventory->branch_id,
                'destination_branch_id' => $destinationBranch->id,
                'inventory_item_id' => $sourceInventory->inventory_item_id,
                'source_branch_inventory_id' => $sourceInventory->id,
                'destination_branch_inventory_id' => $destinationInventory->id,
                'quantity' => $quantity,
                'internal_unit_price' => $internalUnitPrice,
                'internal_total' => $internalUnitPrice !== null ? round($quantity * (float) $internalUnitPrice, 2) : null,
                'transfer_type' => $transferType,
                'status' => BranchTransfer::STATUS_PENDING,
                'transferred_at' => now(),
                'transferred_by' => $user->id,
                'notes' => $notes,
            ]);

            ActivityLog::record(
                'inventory_transfer_created',
                $transfer,
                "Created {$transferType} request for {$quantity} {$sourceInventory->inventoryItem->unit} of {$sourceInventory->inventoryItem->name} from {$sourceInventory->branch->name} to {$destinationBranch->name}.",
                [],
                [
                    'source_branch_id' => $sourceInventory->branch_id,
                    'destination_branch_id' => $destinationBranch->id,
                    'inventory_item_id' => $sourceInventory->inventory_item_id,
                    'quantity' => $quantity,
                    'transfer_type' => $transferType,
                ]
            );

            return $transfer->load('inventoryItem', 'sourceBranch', 'destinationBranch');
        });
    }

    public function approveTransfer(User $user, BranchTransfer $transfer, ?string $notes = null): BranchTransfer
    {
        return DB::transaction(function () use ($user, $transfer, $notes) {
            if (!$transfer->canBeApproved()) {
                throw ValidationException::withMessages([
                    'transfer' => 'Only pending transfers can be approved.',
                ]);
            }

            $transfer->update([
                'status' => BranchTransfer::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $user->id,
                'notes' => $notes ?: $transfer->notes,
            ]);

            ActivityLog::record(
                'inventory_transfer_approved',
                $transfer,
                "Approved inventory transfer #{$transfer->id}.",
                ['status' => BranchTransfer::STATUS_PENDING],
                ['status' => BranchTransfer::STATUS_APPROVED]
            );

            return $transfer->fresh();
        });
    }

    public function sendTransfer(User $user, BranchTransfer $transfer, ?string $notes = null): BranchTransfer
    {
        return DB::transaction(function () use ($user, $transfer, $notes) {
            $transfer->loadMissing('sourceBranchInventory.inventoryItem', 'sourceBranchInventory.branch');

            if (!$transfer->canBeSent()) {
                throw ValidationException::withMessages([
                    'transfer' => 'Only pending or approved transfers can be sent.',
                ]);
            }

            $existingTransferOut = $transfer->inventoryMovements()
                ->where('movement_type', InventoryMovement::TYPE_TRANSFER_OUT)
                ->exists();

            if (!$existingTransferOut) {
                $this->deductStock(
                    $user,
                    $transfer->sourceBranchInventory,
                    (int) $transfer->quantity,
                    InventoryMovement::TYPE_TRANSFER_OUT,
                    $notes ?: $transfer->notes,
                    $transfer
                );
            }

            $previousStatus = $transfer->status;

            $transfer->update([
                'status' => BranchTransfer::STATUS_SENT,
                'sent_at' => now(),
                'sent_by' => $user->id,
                'notes' => $notes ?: $transfer->notes,
            ]);

            ActivityLog::record(
                'inventory_transfer_sent',
                $transfer,
                "Sent inventory transfer #{$transfer->id}.",
                ['status' => $previousStatus],
                ['status' => BranchTransfer::STATUS_SENT]
            );

            return $transfer->fresh(['inventoryItem', 'sourceBranch', 'destinationBranch']);
        });
    }

    public function receiveTransfer(User $user, BranchTransfer $transfer, ?string $notes = null): BranchTransfer
    {
        return DB::transaction(function () use ($user, $transfer, $notes) {
            $transfer->loadMissing('destinationBranchInventory', 'sourceBranchInventory.branch', 'inventoryItem');

            if (!$transfer->canBeReceived()) {
                throw ValidationException::withMessages([
                    'transfer' => 'Only sent transfers can be received.',
                ]);
            }

            $destinationInventory = $transfer->destinationBranchInventory;
            $transferOutMovements = $transfer->inventoryMovements()
                ->where('movement_type', InventoryMovement::TYPE_TRANSFER_OUT)
                ->get();

            foreach ($transferOutMovements as $movement) {
                $meta = $movement->meta ?? [];
                $quantity = abs((int) $movement->quantity_change);

                $destinationBatch = InventoryBatch::query()
                    ->where('branch_inventory_id', $destinationInventory->id)
                    ->when(
                        data_get($meta, 'batch_number'),
                        fn($query, $batchNumber) => $query->where('batch_number', $batchNumber),
                        fn($query) => $query->whereNull('batch_number')
                    )
                    ->when(
                        data_get($meta, 'expires_on'),
                        fn($query, $expiresOn) => $query->whereDate('expires_on', $expiresOn),
                        fn($query) => $query->whereNull('expires_on')
                    )
                    ->when(
                        data_get($meta, 'received_on'),
                        fn($query, $receivedOn) => $query->whereDate('received_on', $receivedOn),
                        fn($query) => $query->whereDate('received_on', now()->toDateString())
                    )
                    ->lockForUpdate()
                    ->first();

                if ($destinationBatch) {
                    $before = (int) $destinationBatch->quantity_remaining;
                    $destinationBatch->update([
                        'quantity_received' => (int) $destinationBatch->quantity_received + $quantity,
                        'quantity_remaining' => $before + $quantity,
                        'unit_cost' => $destinationBatch->unit_cost ?? data_get($meta, 'unit_cost') ?? $transfer->internal_unit_price,
                    ]);
                    $after = (int) $destinationBatch->quantity_remaining;
                } else {
                    $destinationBatch = InventoryBatch::create([
                        'company_id' => $destinationInventory->company_id,
                        'branch_inventory_id' => $destinationInventory->id,
                        'batch_number' => data_get($meta, 'batch_number'),
                        'expires_on' => data_get($meta, 'expires_on'),
                        'received_on' => data_get($meta, 'received_on', now()->toDateString()),
                        'quantity_received' => $quantity,
                        'quantity_remaining' => $quantity,
                        'unit_cost' => data_get($meta, 'unit_cost') ?? $transfer->internal_unit_price,
                        'notes' => $notes ?: $transfer->notes ?: "Transferred from {$transfer->sourceBranchInventory->branch->name}",
                    ]);
                    $before = 0;
                    $after = $quantity;
                }

                InventoryMovement::create([
                    'company_id' => $destinationInventory->company_id,
                    'branch_id' => $destinationInventory->branch_id,
                    'inventory_item_id' => $destinationInventory->inventory_item_id,
                    'branch_inventory_id' => $destinationInventory->id,
                    'inventory_batch_id' => $destinationBatch->id,
                    'branch_transfer_id' => $transfer->id,
                    'movement_type' => InventoryMovement::TYPE_TRANSFER_IN,
                    'quantity_change' => $quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'occurred_at' => now(),
                    'performed_by' => $user->id,
                    'notes' => $notes ?: $transfer->notes,
                    'meta' => [
                        'batch_number' => $destinationBatch->batch_number,
                        'expires_on' => $destinationBatch->expires_on?->toDateString(),
                        'source_branch_id' => $transfer->source_branch_id,
                        'received_on' => $destinationBatch->received_on?->toDateString(),
                        'unit_cost' => $destinationBatch->unit_cost,
                    ],
                ]);
            }

            $transfer->update([
                'status' => BranchTransfer::STATUS_RECEIVED,
                'received_at' => now(),
                'received_by' => $user->id,
                'notes' => $notes ?: $transfer->notes,
            ]);

            ActivityLog::record(
                'inventory_transfer_received',
                $transfer,
                "Received inventory transfer #{$transfer->id}.",
                ['status' => BranchTransfer::STATUS_SENT],
                ['status' => BranchTransfer::STATUS_RECEIVED]
            );

            return $transfer->fresh(['inventoryItem', 'sourceBranch', 'destinationBranch']);
        });
    }

    public function cancelTransfer(User $user, BranchTransfer $transfer, ?string $notes = null): BranchTransfer
    {
        return DB::transaction(function () use ($user, $transfer, $notes) {
            if (!$transfer->canBeCancelled()) {
                throw ValidationException::withMessages([
                    'transfer' => 'Only pending or approved transfers can be cancelled.',
                ]);
            }

            $previousStatus = $transfer->status;

            $transfer->update([
                'status' => BranchTransfer::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'notes' => $notes ?: $transfer->notes,
            ]);

            ActivityLog::record(
                'inventory_transfer_cancelled',
                $transfer,
                "Cancelled inventory transfer #{$transfer->id}.",
                ['status' => $previousStatus],
                ['status' => BranchTransfer::STATUS_CANCELLED]
            );

            return $transfer->fresh();
        });
    }
}
