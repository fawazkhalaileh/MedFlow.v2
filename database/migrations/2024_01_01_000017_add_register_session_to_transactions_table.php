<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('cash_register_session_id')
                ->nullable()
                ->after('appointment_id')
                ->constrained('cash_register_sessions')
                ->nullOnDelete();

            $table->string('transaction_type')
                ->default('payment')
                ->after('change_returned');

            $table->index(['transaction_type', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_register_session_id');
            $table->dropIndex(['transaction_type', 'received_at']);
            $table->dropColumn('transaction_type');
        });
    }
};
