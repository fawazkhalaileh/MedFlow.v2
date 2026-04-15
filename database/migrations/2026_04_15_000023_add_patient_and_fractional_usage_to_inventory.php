<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_inventories', function (Blueprint $table) {
            $table->decimal('low_stock_threshold', 10, 2)->default(0)->change();
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->decimal('quantity_received', 10, 2)->change();
            $table->decimal('quantity_remaining', 10, 2)->change();
        });

        Schema::table('branch_transfers', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->change();
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->after('branch_transfer_id')->constrained()->nullOnDelete();
            $table->decimal('quantity_change', 10, 2)->change();
            $table->decimal('quantity_before', 10, 2)->nullable()->change();
            $table->decimal('quantity_after', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
            $table->integer('quantity_change')->change();
            $table->unsignedInteger('quantity_before')->nullable()->change();
            $table->unsignedInteger('quantity_after')->nullable()->change();
        });

        Schema::table('branch_transfers', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->change();
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->unsignedInteger('quantity_received')->change();
            $table->unsignedInteger('quantity_remaining')->change();
        });

        Schema::table('branch_inventories', function (Blueprint $table) {
            $table->unsignedInteger('low_stock_threshold')->default(0)->change();
        });
    }
};
