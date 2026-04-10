<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reason_id')->nullable()->constrained('appointment_reasons')->nullOnDelete();
            $table->unsignedBigInteger('assigned_staff_id')->nullable(); // technician/doctor
            $table->unsignedBigInteger('booked_by')->nullable();         // secretary user_id
            $table->string('appointment_type')->default('booked');       // booked, walk_in
            $table->datetime('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->string('status')->default('scheduled');
            // scheduled, confirmed, arrived, in_progress, completed, cancelled, no_show, rescheduled
            $table->integer('session_number')->nullable();  // which session in the plan (1, 2, 3...)
            $table->text('reason_notes')->nullable();       // free-text reason if not from list
            $table->text('outcome_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('rescheduled_from')->nullable(); // original appointment_id
            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'scheduled_at']);
            $table->index(['customer_id', 'status']);
            $table->index(['assigned_staff_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
