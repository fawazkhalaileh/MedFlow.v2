<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatment_sessions', function (Blueprint $table) {
            $table->unsignedInteger('shots_count')->nullable()->after('duration_minutes');
            $table->text('recommendations')->nullable()->after('next_session_notes');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable()->after('patient_id')->constrained()->nullOnDelete();
            $table->foreignId('treatment_session_id')->nullable()->after('appointment_id')->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->after('treatment_session_id')->constrained()->nullOnDelete();

            $table->index(['service_id', 'occurred_at']);
            $table->index(['treatment_session_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
            $table->dropConstrainedForeignId('treatment_session_id');
            $table->dropConstrainedForeignId('appointment_id');
        });

        Schema::table('treatment_sessions', function (Blueprint $table) {
            $table->dropColumn(['shots_count', 'recommendations']);
        });
    }
};
