<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\BranchTransfer;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class InventoryReportService
{
    public function __construct(private readonly ReportService $reportService)
    {
    }

    public function build(User $user, array $filters = []): array
    {
        [$startDate, $endDate] = $this->reportService->resolveDateRange($filters);
        $branchId = $this->reportService->allowedBranchId($user) ?: (!empty($filters['branch_id']) ? (int) $filters['branch_id'] : null);

        $inventories = BranchInventory::query()
            ->with(['branch', 'inventoryItem', 'batches' => fn ($query) => $query->orderBy('expires_on')])
            ->where('company_id', $user->company_id)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->orderBy('branch_id')
            ->get();

        $movements = InventoryMovement::query()
            ->with(['branch', 'inventoryItem', 'patient', 'performedBy', 'service', 'appointment', 'treatmentSession'])
            ->where('company_id', $user->company_id)
            ->whereBetween('occurred_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->orderByDesc('occurred_at')
            ->get();

        $expiryAlerts = InventoryBatch::query()
            ->with(['branchInventory.branch', 'branchInventory.inventoryItem'])
            ->where('company_id', $user->company_id)
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', '<=', now()->addDays(30)->toDateString())
            ->when($branchId, function (Builder $query) use ($branchId) {
                $query->whereHas('branchInventory', fn (Builder $inventoryQuery) => $inventoryQuery->where('branch_id', $branchId));
            })
            ->orderBy('expires_on')
            ->get();

        $transfers = BranchTransfer::query()
            ->with(['inventoryItem', 'sourceBranch', 'destinationBranch', 'transferredBy'])
            ->where('company_id', $user->company_id)
            ->whereBetween('transferred_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->when($branchId, function (Builder $query) use ($branchId) {
                $query->where(function (Builder $innerQuery) use ($branchId) {
                    $innerQuery->where('source_branch_id', $branchId)
                        ->orWhere('destination_branch_id', $branchId);
                });
            })
            ->orderByDesc('transferred_at')
            ->get();

        $usageByPeriod = $movements
            ->whereIn('movement_type', [InventoryMovement::TYPE_USAGE, InventoryMovement::TYPE_WASTE])
            ->groupBy(fn (InventoryMovement $movement) => Carbon::parse($movement->occurred_at)->format('Y-m-d'))
            ->map(fn ($group, string $date) => [
                'date' => $date,
                'used' => abs(round((float) $group->where('movement_type', InventoryMovement::TYPE_USAGE)->sum('quantity_change'), 2)),
                'wasted' => abs(round((float) $group->where('movement_type', InventoryMovement::TYPE_WASTE)->sum('quantity_change'), 2)),
            ])
            ->values();

        $deductedBySession = $movements
            ->where('movement_type', InventoryMovement::TYPE_USAGE)
            ->groupBy(function (InventoryMovement $movement) {
                if ($movement->treatment_session_id) {
                    return 'session:' . $movement->treatment_session_id;
                }

                if ($movement->service_id) {
                    return 'service:' . $movement->service_id;
                }

                return 'manual';
            })
            ->map(function ($group, string $key) {
                $first = $group->first();

                return [
                    'bucket' => $key,
                    'service' => $first?->service?->name,
                    'session_id' => $first?->treatment_session_id,
                    'appointment_id' => $first?->appointment_id,
                    'quantity' => abs(round((float) $group->sum('quantity_change'), 2)),
                ];
            })
            ->values();

        $branchSummary = Branch::query()
            ->where('company_id', $user->company_id)
            ->when($this->reportService->allowedBranchId($user), fn (Builder $query, int $scopeId) => $query->where('id', $scopeId))
            ->when($branchId, fn (Builder $query) => $query->where('id', $branchId))
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($inventories, $movements) {
                return [
                    'branch' => $branch->name,
                    'items' => $inventories->where('branch_id', $branch->id)->count(),
                    'stock' => round((float) $inventories->where('branch_id', $branch->id)->sum('current_stock'), 2),
                    'movements' => $movements->where('branch_id', $branch->id)->count(),
                ];
            });

        return [
            'filters' => [
                'period' => $filters['period'] ?? 'month',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'branch_id' => $branchId,
            ],
            'stats' => [
                'branch_items' => $inventories->count(),
                'low_stock_count' => $inventories->filter(fn (BranchInventory $inventory) => $inventory->low_stock)->count(),
                'expiring_count' => $expiryAlerts->count(),
                'movement_count' => $movements->count(),
            ],
            'current_stock' => $inventories,
            'movement_history' => $movements,
            'usage_by_period' => $usageByPeriod,
            'deducted_by_session' => $deductedBySession,
            'expiry_alerts' => $expiryAlerts,
            'low_stock_alerts' => $inventories->filter(fn (BranchInventory $inventory) => $inventory->low_stock)->values(),
            'branch_summary' => $branchSummary,
            'transfer_history' => $transfers,
        ];
    }
}
