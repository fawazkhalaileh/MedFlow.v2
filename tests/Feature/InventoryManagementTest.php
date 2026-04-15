<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\BranchTransfer;
use App\Models\Company;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_scoped_inventory_access_and_admin_all_branch_visibility(): void
    {
        [$company, $branchOne, $branchTwo, $branchOneUser] = $this->makeInventoryUsers();
        $adminUser = User::factory()->create([
            'company_id' => $company->id,
            'employee_type' => 'system_admin',
            'role' => 'admin',
            'primary_branch_id' => $branchOne->id,
            'first_name' => 'System',
            'last_name' => 'Admin',
        ]);

        $itemOne = InventoryItem::create([
            'company_id' => $company->id,
            'name' => 'Branch One Serum',
            'sku' => 'SERUM-001',
            'unit' => 'box',
        ]);

        $itemTwo = InventoryItem::create([
            'company_id' => $company->id,
            'name' => 'Branch Two Serum',
            'sku' => 'SERUM-002',
            'unit' => 'box',
        ]);

        $this->makeInventoryWithBatch($company, $branchOne, $itemOne, 8, 2, '2026-06-01');
        $this->makeInventoryWithBatch($company, $branchTwo, $itemTwo, 5, 2, '2026-07-01');

        $branchResponse = $this->actingAs($branchOneUser)->get(route('inventory.index'));

        $branchResponse->assertOk();
        $branchResponse->assertSee('Branch One Serum');
        $branchResponse->assertSee('1 branch item');

        $adminResponse = $this->actingAs($adminUser)->get(route('inventory.index'));

        $adminResponse->assertOk();
        $adminResponse->assertSee('Branch One Serum');
        $adminResponse->assertSee('Branch Two Serum');
        $adminResponse->assertSee('2 branch items');
    }

    public function test_finance_user_can_create_inventory_item_and_add_stock_batch_with_expiry(): void
    {
        [$company, $branchOne, , $branchUser] = $this->makeInventoryUsers();

        $itemResponse = $this->actingAs($branchUser)->post(route('inventory.items.store'), [
            'name' => 'Cooling Gel',
            'sku' => 'COOL-001',
            'unit' => 'bottle',
            'description' => 'Post-treatment cooling gel',
        ]);

        $itemResponse->assertRedirect(route('inventory.index'));

        $item = InventoryItem::query()->where('sku', 'COOL-001')->firstOrFail();

        $stockResponse = $this->actingAs($branchUser)->post(route('inventory.stock.store'), [
            'inventory_item_id' => $item->id,
            'quantity' => 12,
            'batch_number' => 'BATCH-GEL-01',
            'expires_on' => '2026-10-01',
            'received_on' => '2026-04-14',
            'low_stock_threshold' => 4,
            'notes' => 'Initial branch stock',
        ]);

        $stockResponse->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('branch_inventories', [
            'company_id' => $company->id,
            'branch_id' => $branchOne->id,
            'inventory_item_id' => $item->id,
            'low_stock_threshold' => 4,
        ]);

        $branchInventory = BranchInventory::query()->where('branch_id', $branchOne->id)->where('inventory_item_id', $item->id)->firstOrFail();

        $this->assertDatabaseHas('inventory_batches', [
            'branch_inventory_id' => $branchInventory->id,
            'batch_number' => 'BATCH-GEL-01',
            'expires_on' => '2026-10-01 00:00:00',
            'quantity_received' => 12,
            'quantity_remaining' => 12,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'branch_id' => $branchOne->id,
            'inventory_item_id' => $item->id,
            'movement_type' => InventoryMovement::TYPE_STOCK_IN,
            'quantity_change' => 12,
        ]);
    }

    public function test_usage_deduction_uses_fefo_batches(): void
    {
        [$company, $branchOne, , $branchUser] = $this->makeInventoryUsers();
        $item = InventoryItem::create([
            'company_id' => $company->id,
            'name' => 'Disposable Gloves',
            'sku' => 'GLV-001',
            'unit' => 'box',
        ]);

        $branchInventory = BranchInventory::create([
            'company_id' => $company->id,
            'branch_id' => $branchOne->id,
            'inventory_item_id' => $item->id,
            'low_stock_threshold' => 2,
        ]);

        $earlyBatch = InventoryBatch::create([
            'company_id' => $company->id,
            'branch_inventory_id' => $branchInventory->id,
            'batch_number' => 'EARLY',
            'expires_on' => '2026-05-01',
            'received_on' => '2026-04-01',
            'quantity_received' => 3,
            'quantity_remaining' => 3,
        ]);

        $laterBatch = InventoryBatch::create([
            'company_id' => $company->id,
            'branch_inventory_id' => $branchInventory->id,
            'batch_number' => 'LATE',
            'expires_on' => '2026-09-01',
            'received_on' => '2026-04-05',
            'quantity_received' => 5,
            'quantity_remaining' => 5,
        ]);

        $response = $this->actingAs($branchUser)->post(route('inventory.usage.store'), [
            'branch_inventory_id' => $branchInventory->id,
            'quantity' => 4,
            'notes' => 'Used during daily treatment prep',
        ]);

        $response->assertRedirect(route('inventory.index'));

        $this->assertSame(0, $earlyBatch->fresh()->quantity_remaining);
        $this->assertSame(4, $laterBatch->fresh()->quantity_remaining);
        $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_batch_id' => $earlyBatch->id,
            'movement_type' => InventoryMovement::TYPE_USAGE,
            'quantity_change' => -3,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_batch_id' => $laterBatch->id,
            'movement_type' => InventoryMovement::TYPE_USAGE,
            'quantity_change' => -1,
        ]);
    }

    public function test_low_stock_and_expiry_states_are_visible_in_inventory_workspace(): void
    {
        [$company, $branchOne, , $branchUser] = $this->makeInventoryUsers();

        $item = InventoryItem::create([
            'company_id' => $company->id,
            'name' => 'Expiry Test Item',
            'sku' => 'EXP-001',
            'unit' => 'unit',
        ]);

        $branchInventory = $this->makeInventoryWithBatch($company, $branchOne, $item, 2, 5, now()->addDays(7)->toDateString());

        InventoryBatch::create([
            'company_id' => $company->id,
            'branch_inventory_id' => $branchInventory->id,
            'batch_number' => 'EXPIRED-01',
            'expires_on' => now()->subDay()->toDateString(),
            'received_on' => now()->subDays(5)->toDateString(),
            'quantity_received' => 1,
            'quantity_remaining' => 1,
        ]);

        $response = $this->actingAs($branchUser)->get(route('inventory.index'));

        $response->assertOk();
        $response->assertSee('Low Stock Alerts');
        $response->assertSee('Low stock');
        $response->assertSee('Near expiry');
        $response->assertSee('Expired');
    }

    public function test_branch_transfer_flow_supports_pending_send_and_receive_with_internal_price(): void
    {
        [$company, $branchOne, $branchTwo, $branchOneUser] = $this->makeInventoryUsers();
        $branchTwoUser = $this->makeBranchFinanceUser($company, $branchTwo, 'Finance', 'Branch Two');

        $item = InventoryItem::create([
            'company_id' => $company->id,
            'name' => 'Treatment Gel',
            'sku' => 'TRG-001',
            'unit' => 'tube',
        ]);

        $sourceInventory = $this->makeInventoryWithBatch($company, $branchOne, $item, 10, 2, '2026-08-01', 'TUBE-001');

        $createResponse = $this->actingAs($branchOneUser)->post(route('inventory.transfers.store'), [
            'branch_inventory_id' => $sourceInventory->id,
            'destination_branch_id' => $branchTwo->id,
            'quantity' => 6,
            'transfer_type' => BranchTransfer::TYPE_INTERNAL_SALE,
            'internal_unit_price' => '3.50',
            'notes' => 'Support another branch',
        ]);

        $createResponse->assertRedirect(route('inventory.index'));

        $transfer = BranchTransfer::query()->latest('id')->firstOrFail();

        $this->assertSame(BranchTransfer::STATUS_PENDING, $transfer->status);
        $this->assertSame('3.50', $transfer->internal_unit_price);
        $this->assertSame('21.00', $transfer->internal_total);
        $this->assertSame(10, $sourceInventory->batches()->first()->fresh()->quantity_remaining);
        $this->assertDatabaseMissing('inventory_movements', [
            'branch_transfer_id' => $transfer->id,
            'movement_type' => InventoryMovement::TYPE_TRANSFER_OUT,
        ]);

        $this->actingAs($branchOneUser)->post(route('inventory.transfers.approve', $transfer))
            ->assertRedirect(route('inventory.index'));

        $this->assertSame(BranchTransfer::STATUS_APPROVED, $transfer->fresh()->status);

        $this->actingAs($branchOneUser)->post(route('inventory.transfers.send', $transfer))
            ->assertRedirect(route('inventory.index'));

        $this->assertSame(BranchTransfer::STATUS_SENT, $transfer->fresh()->status);
        $this->assertSame(4, $sourceInventory->batches()->first()->fresh()->quantity_remaining);
        $this->assertDatabaseHas('inventory_movements', [
            'branch_transfer_id' => $transfer->id,
            'branch_id' => $branchOne->id,
            'movement_type' => InventoryMovement::TYPE_TRANSFER_OUT,
            'quantity_change' => -6,
        ]);

        $destinationInventory = BranchInventory::query()
            ->where('branch_id', $branchTwo->id)
            ->where('inventory_item_id', $item->id)
            ->firstOrFail();

        $this->assertSame(0, $destinationInventory->current_stock);

        $this->actingAs($branchTwoUser)->post(route('inventory.transfers.receive', $transfer))
            ->assertRedirect(route('inventory.index'));

        $transfer = $transfer->fresh();
        $destinationInventory = $destinationInventory->fresh();
        $destinationBatch = InventoryBatch::query()
            ->where('branch_inventory_id', $destinationInventory->id)
            ->firstOrFail();

        $this->assertSame(BranchTransfer::STATUS_RECEIVED, $transfer->status);
        $this->assertSame(6, $destinationInventory->current_stock);
        $this->assertSame('2026-08-01', $destinationBatch->expires_on?->toDateString());
        $this->assertSame('3.50', $destinationBatch->unit_cost);
        $this->assertDatabaseHas('inventory_movements', [
            'branch_transfer_id' => $transfer->id,
            'branch_id' => $branchTwo->id,
            'movement_type' => InventoryMovement::TYPE_TRANSFER_IN,
            'quantity_change' => 6,
        ]);
    }

    public function test_cross_branch_actions_are_denied_for_inventory_and_transfer_steps(): void
    {
        [$company, $branchOne, $branchTwo, $branchOneUser] = $this->makeInventoryUsers();
        $branchTwoUser = $this->makeBranchFinanceUser($company, $branchTwo, 'Finance', 'Branch Two');

        $item = InventoryItem::create([
            'company_id' => $company->id,
            'name' => 'Cross Branch Stock',
            'sku' => 'CB-001',
            'unit' => 'unit',
        ]);

        $branchTwoInventory = $this->makeInventoryWithBatch($company, $branchTwo, $item, 9, 3, '2026-11-01');
        $branchOneInventory = $this->makeInventoryWithBatch($company, $branchOne, $item, 7, 3, '2026-12-01', 'SRC-001');

        $usageResponse = $this->actingAs($branchOneUser)->post(route('inventory.usage.store'), [
            'branch_inventory_id' => $branchTwoInventory->id,
            'quantity' => 1,
        ]);

        $usageResponse->assertNotFound();

        $transferCreate = $this->actingAs($branchOneUser)->post(route('inventory.transfers.store'), [
            'branch_inventory_id' => $branchOneInventory->id,
            'destination_branch_id' => $branchTwo->id,
            'quantity' => 2,
            'transfer_type' => BranchTransfer::TYPE_TRANSFER,
        ]);

        $transferCreate->assertRedirect(route('inventory.index'));
        $transfer = BranchTransfer::query()->latest('id')->firstOrFail();

        $this->actingAs($branchTwoUser)->post(route('inventory.transfers.approve', $transfer))
            ->assertNotFound();

        $this->actingAs($branchOneUser)->post(route('inventory.transfers.send', $transfer))
            ->assertRedirect(route('inventory.index'));

        $this->actingAs($branchOneUser)->post(route('inventory.transfers.receive', $transfer))
            ->assertNotFound();
    }

    public function test_inventory_workspace_supports_arabic_labels(): void
    {
        [$company, $branchOne, $branchTwo, $branchOneUser] = $this->makeInventoryUsers();

        $item = InventoryItem::create([
            'company_id' => $company->id,
            'name' => 'Arabic Inventory Item',
            'sku' => 'AR-001',
            'unit' => 'unit',
        ]);

        $inventory = $this->makeInventoryWithBatch($company, $branchOne, $item, 2, 5, now()->addDays(3)->toDateString());

        $this->actingAs($branchOneUser)->post(route('inventory.transfers.store'), [
            'branch_inventory_id' => $inventory->id,
            'destination_branch_id' => $branchTwo->id,
            'quantity' => 1,
            'transfer_type' => BranchTransfer::TYPE_TRANSFER,
        ]);

        $response = $this->actingAs($branchOneUser)
            ->withSession(['locale' => 'ar'])
            ->get(route('inventory.index'));

        $response->assertOk();
        $response->assertSee('مساحة المخزون');
        $response->assertSee('مخزون منخفض');
        $response->assertSee('مراقبة الانتهاء');
        $response->assertSee('قيد الانتظار');
    }

    private function makeInventoryUsers(): array
    {
        $company = Company::create([
            'name' => 'MedFlow Test Clinic',
            'slug' => 'medflow-test-clinic',
            'currency' => 'JOD',
        ]);

        $branchOne = Branch::create([
            'company_id' => $company->id,
            'name' => 'Marina Branch',
            'code' => 'BR-001',
            'status' => 'active',
        ]);

        $branchTwo = Branch::create([
            'company_id' => $company->id,
            'name' => 'Jabal Branch',
            'code' => 'BR-002',
            'status' => 'active',
        ]);

        $branchUser = $this->makeBranchFinanceUser($company, $branchOne, 'Finance', 'Branch One');

        return [$company, $branchOne, $branchTwo, $branchUser];
    }

    private function makeBranchFinanceUser(Company $company, Branch $branch, string $firstName, string $lastName): User
    {
        return User::factory()->create([
            'company_id' => $company->id,
            'employee_type' => 'finance',
            'role' => 'finance',
            'primary_branch_id' => $branch->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function makeInventoryWithBatch(
        Company $company,
        Branch $branch,
        InventoryItem $item,
        int $quantity,
        int $lowStockThreshold,
        string $expiresOn,
        ?string $batchNumber = 'BATCH-001'
    ): BranchInventory {
        $branchInventory = BranchInventory::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inventory_item_id' => $item->id,
            'low_stock_threshold' => $lowStockThreshold,
        ]);

        InventoryBatch::create([
            'company_id' => $company->id,
            'branch_inventory_id' => $branchInventory->id,
            'batch_number' => $batchNumber,
            'expires_on' => $expiresOn,
            'received_on' => '2026-04-14',
            'quantity_received' => $quantity,
            'quantity_remaining' => $quantity,
        ]);

        return $branchInventory;
    }
}
