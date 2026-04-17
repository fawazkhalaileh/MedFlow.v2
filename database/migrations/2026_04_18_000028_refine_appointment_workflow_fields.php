<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('visit_type')->default('technician')->after('appointment_type');
            $table->text('front_desk_note')->nullable()->after('reason_notes');
            $table->text('chief_complaint')->nullable()->after('front_desk_note');
            $table->text('clinical_notes')->nullable()->after('chief_complaint');
            $table->text('assessment')->nullable()->after('clinical_notes');
            $table->text('treatment_summary')->nullable()->after('assessment');
            $table->text('doctor_recommendations')->nullable()->after('treatment_summary');
            $table->boolean('follow_up_required')->default(false)->after('doctor_recommendations');
            $table->timestamp('provider_started_at')->nullable()->after('arrived_at');
            $table->timestamp('checked_out_at')->nullable()->after('completed_at');

            $table->index(['branch_id', 'visit_type', 'status'], 'appointments_branch_visit_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_branch_visit_status_idx');
            $table->dropColumn([
                'visit_type',
                'front_desk_note',
                'chief_complaint',
                'clinical_notes',
                'assessment',
                'treatment_summary',
                'doctor_recommendations',
                'follow_up_required',
                'provider_started_at',
                'checked_out_at',
            ]);
        });
    }
};
