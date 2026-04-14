<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('unit', 30)->default('unit');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('branch_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('low_stock_threshold')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'inventory_item_id']);
            $table->index(['branch_id', 'low_stock_threshold']);
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_inventory_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('expires_on')->nullable();
            $table->date('received_on');
            $table->unsignedInteger('quantity_received');
            $table->unsignedInteger('quantity_remaining');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_inventory_id', 'expires_on']);
            $table->index(['branch_inventory_id', 'quantity_remaining']);
        });

        Schema::create('branch_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('destination_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_branch_inventory_id')->constrained('branch_inventories')->cascadeOnDelete();
            $table->foreignId('destination_branch_inventory_id')->constrained('branch_inventories')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('transfer_type')->default('transfer');
            $table->string('status')->default('completed');
            $table->timestamp('transferred_at');
            $table->foreignId('transferred_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['source_branch_id', 'transferred_at']);
            $table->index(['destination_branch_id', 'transferred_at']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_transfer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type');
            $table->integer('quantity_change');
            $table->unsignedInteger('quantity_before')->nullable();
            $table->unsignedInteger('quantity_after')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'occurred_at']);
            $table->index(['inventory_item_id', 'movement_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('branch_transfers');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('branch_inventories');
        Schema::dropIfExists('inventory_items');
    }
};
