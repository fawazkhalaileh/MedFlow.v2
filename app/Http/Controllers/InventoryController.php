<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\BranchTransfer;
use App\Models\Company;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $company = $this->currentCompany($user);
        $scopedBranchId = $user->scopedBranchId();
        $selectedBranchId = $scopedBranchId ?: ($request->filled('branch') ? (int) $request->branch : null);

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $branchInventories = BranchInventory::query()
            ->with([
                'branch',
                'inventoryItem',
                'batches' => fn($query) => $query
                    ->where('quantity_remaining', '>', 0)
                    ->orderByRaw('case when expires_on is null then 1 else 0 end')
                    ->orderBy('expires_on')
                    ->orderBy('received_on'),
            ])
            ->where('company_id', $company->id)
            ->when($selectedBranchId, fn($query) => $query->where('branch_id', $selectedBranchId))
            ->orderBy('branch_id')
            ->orderBy('inventory_item_id')
            ->get();

        $inventoryItems = InventoryItem::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $lowStockAlerts = $branchInventories
            ->filter(fn(BranchInventory $inventory) => $inventory->low_stock)
            ->sortBy(fn(BranchInventory $inventory) => $inventory->current_stock)
            ->values();

        $expiringBatches = InventoryBatch::query()
            ->with(['branchInventory.branch', 'branchInventory.inventoryItem'])
            ->where('company_id', $company->id)
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expires_on')
            ->when($selectedBranchId, function ($query) use ($selectedBranchId) {
                $query->whereHas('branchInventory', fn($branchInventoryQuery) => $branchInventoryQuery->where('branch_id', $selectedBranchId));
            })
            ->orderBy('expires_on')
            ->limit(12)
            ->get();

        $recentTransfers = BranchTransfer::query()
            ->with([
                'inventoryItem',
                'sourceBranch',
                'destinationBranch',
                'transferredBy',
                'approvedBy',
                'sentBy',
                'receivedBy',
                'cancelledBy',
            ])
            ->where('company_id', $company->id)
            ->when($selectedBranchId, function ($query) use ($selectedBranchId) {
                $query->where(function ($subQuery) use ($selectedBranchId) {
                    $subQuery->where('source_branch_id', $selectedBranchId)
                        ->orWhere('destination_branch_id', $selectedBranchId);
                });
            })
            ->latest('updated_at')
            ->limit(12)
            ->get();

        $recentMovements = InventoryMovement::query()
            ->with(['branch', 'inventoryItem', 'performedBy'])
            ->where('company_id', $company->id)
            ->when($selectedBranchId, fn($query) => $query->where('branch_id', $selectedBranchId))
            ->latest('occurred_at')
            ->limit(12)
            ->get();

        return view('inventory.index', [
            'branchInventories' => $branchInventories,
            'inventoryItems' => $inventoryItems,
            'branches' => $branches,
            'destinationBranches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'scopedBranchId' => $scopedBranchId,
            'lowStockAlerts' => $lowStockAlerts,
            'expiringBatches' => $expiringBatches,
            'recentTransfers' => $recentTransfers,
            'recentMovements' => $recentMovements,
        ]);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $company = $this->currentCompany($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('inventory_items', 'sku')->where(fn($query) => $query->where('company_id', $company->id)),
            ],
            'unit' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $item = InventoryItem::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?: null,
            'unit' => $validated['unit'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        \App\Models\ActivityLog::record(
            'inventory_item_created',
            $item,
            "Inventory item {$item->name} created.",
            [],
            ['inventory_item_id' => $item->id]
        );

        return redirect()
            ->route('inventory.index')
            ->with('success', __('inventory_ui.messages.item_created', ['name' => $item->name]));
    }

    public function stockIn(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $company = $this->currentCompany($user);

        $validated = $request->validate([
            'inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'batch_number' => ['nullable', 'string', 'max:80'],
            'expires_on' => ['nullable', 'date'],
            'received_on' => ['nullable', 'date'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $inventoryItem = InventoryItem::query()
            ->where('company_id', $company->id)
            ->findOrFail($validated['inventory_item_id']);

        $branch = $this->resolveActionBranch($user, $company, $validated['branch_id'] ?? null);

        $batch = $this->inventoryService->addStock(
            $user,
            $inventoryItem,
            $branch,
            (int) $validated['quantity'],
            $validated['batch_number'] ?? null,
            $validated['expires_on'] ?? null,
            $validated['received_on'] ?? now()->toDateString(),
            array_key_exists('low_stock_threshold', $validated) ? (int) ($validated['low_stock_threshold'] ?? 0) : null,
            $validated['notes'] ?? null,
            isset($validated['unit_cost']) ? (string) $validated['unit_cost'] : null
        );

        return redirect()
            ->route('inventory.index', $this->inventoryRedirectParams($user, $branch))
            ->with('success', __('inventory_ui.messages.stock_added', [
                'quantity' => $batch->quantity_received,
                'unit' => $inventoryItem->unit,
                'name' => $inventoryItem->name,
                'branch' => $branch->name,
            ]));
    }

    public function useStock(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'branch_inventory_id' => ['required', 'integer', 'exists:branch_inventories,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $branchInventory = $this->resolveBranchInventory($user, (int) $validated['branch_inventory_id']);
        $branchInventory->loadMissing('inventoryItem', 'branch');

        $this->inventoryService->deductStock(
            $user,
            $branchInventory,
            (int) $validated['quantity'],
            InventoryMovement::TYPE_USAGE,
            $validated['notes'] ?? null
        );

        return redirect()
            ->route('inventory.index', $this->inventoryRedirectParams($user, $branchInventory->branch))
            ->with('success', __('inventory_ui.messages.usage_recorded', [
                'quantity' => $validated['quantity'],
                'unit' => $branchInventory->inventoryItem->unit,
                'name' => $branchInventory->inventoryItem->name,
            ]));
    }

    public function storeTransfer(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $company = $this->currentCompany($user);

        $validated = $request->validate([
            'branch_inventory_id' => ['required', 'integer', 'exists:branch_inventories,id'],
            'destination_branch_id' => ['required', 'integer', 'exists:branches,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'transfer_type' => ['required', Rule::in(BranchTransfer::transferTypes())],
            'internal_unit_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $sourceInventory = $this->resolveBranchInventory($user, (int) $validated['branch_inventory_id']);
        $sourceInventory->loadMissing('inventoryItem', 'branch');

        $destinationBranch = Branch::query()
            ->where('company_id', $company->id)
            ->findOrFail($validated['destination_branch_id']);

        $transfer = $this->inventoryService->createTransfer(
            $user,
            $sourceInventory,
            $destinationBranch,
            (int) $validated['quantity'],
            $validated['transfer_type'],
            isset($validated['internal_unit_price']) ? (string) $validated['internal_unit_price'] : null,
            $validated['notes'] ?? null
        );

        return redirect()
            ->route('inventory.index', $this->inventoryRedirectParams($user, $sourceInventory->branch))
            ->with('success', __('inventory_ui.messages.transfer_created', [
                'quantity' => $transfer->quantity,
                'unit' => $sourceInventory->inventoryItem->unit,
                'name' => $sourceInventory->inventoryItem->name,
                'branch' => $destinationBranch->name,
            ]));
    }

    public function approveTransfer(Request $request, BranchTransfer $transfer): RedirectResponse
    {
        $user = Auth::user();
        $transfer = $this->resolveTransferForSourceAction($user, $transfer->id);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfer = $this->inventoryService->approveTransfer($user, $transfer, $validated['notes'] ?? null);

        return redirect()
            ->route('inventory.index', $this->inventoryRedirectParams($user, $transfer->sourceBranch))
            ->with('success', __('inventory_ui.messages.transfer_approved', ['id' => $transfer->id]));
    }

    public function sendTransfer(Request $request, BranchTransfer $transfer): RedirectResponse
    {
        $user = Auth::user();
        $transfer = $this->resolveTransferForSourceAction($user, $transfer->id);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfer = $this->inventoryService->sendTransfer($user, $transfer, $validated['notes'] ?? null);

        return redirect()
            ->route('inventory.index', $this->inventoryRedirectParams($user, $transfer->sourceBranch))
            ->with('success', __('inventory_ui.messages.transfer_sent', ['id' => $transfer->id]));
    }

    public function receiveTransfer(Request $request, BranchTransfer $transfer): RedirectResponse
    {
        $user = Auth::user();
        $transfer = $this->resolveTransferForDestinationAction($user, $transfer->id);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfer = $this->inventoryService->receiveTransfer($user, $transfer, $validated['notes'] ?? null);

        return redirect()
            ->route('inventory.index', $this->inventoryRedirectParams($user, $transfer->destinationBranch))
            ->with('success', __('inventory_ui.messages.transfer_received', ['id' => $transfer->id]));
    }

    public function cancelTransfer(Request $request, BranchTransfer $transfer): RedirectResponse
    {
        $user = Auth::user();
        $transfer = $this->resolveTransferForSourceAction($user, $transfer->id);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfer = $this->inventoryService->cancelTransfer($user, $transfer, $validated['notes'] ?? null);

        return redirect()
            ->route('inventory.index', $this->inventoryRedirectParams($user, $transfer->sourceBranch))
            ->with('success', __('inventory_ui.messages.transfer_cancelled', ['id' => $transfer->id]));
    }

    private function currentCompany($user): Company
    {
        return Company::query()->findOrFail($user->company_id ?? Company::query()->value('id'));
    }

    private function resolveActionBranch($user, Company $company, ?int $requestedBranchId): Branch
    {
        $branchId = $user->scopedBranchId() ?? $requestedBranchId;

        abort_unless($branchId, 404);

        return Branch::query()
            ->where('company_id', $company->id)
            ->when($user->scopedBranchId(), fn($query, $scopedBranchId) => $query->where('id', $scopedBranchId))
            ->findOrFail($branchId);
    }

    private function resolveBranchInventory($user, int $branchInventoryId): BranchInventory
    {
        return BranchInventory::query()
            ->where('company_id', $user->company_id)
            ->when($user->scopedBranchId(), fn($query, $scopedBranchId) => $query->where('branch_id', $scopedBranchId))
            ->findOrFail($branchInventoryId);
    }

    private function resolveTransferForSourceAction($user, int $transferId): BranchTransfer
    {
        return BranchTransfer::query()
            ->where('company_id', $user->company_id)
            ->when($user->scopedBranchId(), fn($query, $scopedBranchId) => $query->where('source_branch_id', $scopedBranchId))
            ->findOrFail($transferId);
    }

    private function resolveTransferForDestinationAction($user, int $transferId): BranchTransfer
    {
        return BranchTransfer::query()
            ->where('company_id', $user->company_id)
            ->when($user->scopedBranchId(), fn($query, $scopedBranchId) => $query->where('destination_branch_id', $scopedBranchId))
            ->findOrFail($transferId);
    }

    private function inventoryRedirectParams($user, Branch $branch): array
    {
        return $user->scopedBranchId() ? [] : ['branch' => $branch->id];
    }
}
