<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_register_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->decimal('opening_balance', 10, 2)->default(0);
            $table->decimal('closing_balance', 10, 2)->nullable();
            $table->decimal('cash_sales_total', 10, 2)->default(0);
            $table->decimal('cash_received_total', 10, 2)->default(0);
            $table->decimal('change_returned_total', 10, 2)->default(0);
            $table->decimal('expected_closing_balance', 10, 2)->nullable();
            $table->decimal('variance', 10, 2)->nullable();
            $table->string('status')->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('opened_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_sessions');
    }
};
