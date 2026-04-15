<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_transfers', function (Blueprint $table) {
            $table->decimal('internal_unit_price', 10, 2)->nullable()->after('quantity');
            $table->decimal('internal_total', 10, 2)->nullable()->after('internal_unit_price');
            $table->timestamp('approved_at')->nullable()->after('transferred_at');
            $table->timestamp('sent_at')->nullable()->after('approved_at');
            $table->timestamp('received_at')->nullable()->after('sent_at');
            $table->timestamp('cancelled_at')->nullable()->after('received_at');
            $table->foreignId('approved_by')->nullable()->after('transferred_by')->constrained('users')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->after('sent_by')->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->after('received_by')->constrained('users')->nullOnDelete();

            $table->index(['destination_branch_id', 'status']);
            $table->index(['source_branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('branch_transfers', function (Blueprint $table) {
            $table->dropIndex(['destination_branch_id', 'status']);
            $table->dropIndex(['source_branch_id', 'status']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('sent_by');
            $table->dropConstrainedForeignId('received_by');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'internal_unit_price',
                'internal_total',
                'approved_at',
                'sent_at',
                'received_at',
                'cancelled_at',
            ]);
        });
    }
};
