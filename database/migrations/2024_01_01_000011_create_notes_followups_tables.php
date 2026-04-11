<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic notes: attach to Customer, Appointment, TreatmentSession, TreatmentPlan
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('notable');                    // notable_type, notable_id
            $table->string('note_type');
            // reception, clinical, technician, follow_up, internal, alert, session, treatment_plan
            $table->text('content');
            $table->boolean('is_flagged')->default(false); // highlight as important / alert
            $table->boolean('is_private')->default(false); // only visible to managers+
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->unsignedBigInteger('assigned_to')->nullable();   // user_id responsible
            $table->string('type');                                   // call, appointment, check_in, email
            $table->date('due_date');
            $table->string('status')->default('pending');             // pending, completed, overdue, cancelled
            $table->text('notes')->nullable();
            $table->text('outcome')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status', 'due_date']);
            $table->index(['assigned_to', 'status']);
        });

        // Lead / inquiry tracking (before customer registration)
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('service_interest')->nullable();
            $table->string('source')->default('phone'); // phone, walk_in, social, online, referral
            $table->string('status')->default('new');   // new, contacted, appointment_booked, converted, lost
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('converted_to_patient_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('notes');
    }
};
