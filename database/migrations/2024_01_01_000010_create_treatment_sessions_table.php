<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->integer('session_number');
            $table->datetime('started_at')->nullable();
            $table->datetime('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('status')->default('completed'); // completed, incomplete, skipped
            // Laser-specific fields (used if clinic type is laser; ignored otherwise)
            $table->string('device_used')->nullable();
            $table->json('laser_settings')->nullable(); // { fluence, pulse_width, spot_size, frequency }
            $table->json('treatment_areas')->nullable(); // areas treated this session
            // Observations
            $table->text('observations_before')->nullable();
            $table->text('observations_after')->nullable();
            $table->string('skin_reaction')->nullable();  // none, mild, moderate, severe
            $table->text('outcome')->nullable();
            $table->text('next_session_notes')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_sessions');
    }
};
