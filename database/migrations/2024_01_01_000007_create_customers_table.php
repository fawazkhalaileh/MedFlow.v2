<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('patient_code')->unique(); // MF-00001
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('phone_alt')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable(); // male, female, other
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('nationality')->nullable();
            $table->string('id_number')->nullable();          // national ID / passport
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            $table->unsignedBigInteger('assigned_staff_id')->nullable(); // primary technician/doctor
            $table->string('source')->default('walk_in');     // walk_in, phone, referral, online, social
            $table->string('referral_source')->nullable();    // who referred them
            $table->string('status')->default('active');      // lead, active, inactive, completed, blacklisted
            $table->date('registration_date');
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamp('next_appointment_at')->nullable();
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['branch_id', 'status']);
        });

        Schema::create('patient_medical_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained('patients')->cascadeOnDelete();
            // Physical
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->string('skin_type')->nullable();  // fitzpatrick: I, II, III, IV, V, VI
            $table->string('skin_tone')->nullable();  // fair, medium, olive, dark
            // Medical
            $table->text('medical_history')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('allergies')->nullable();
            $table->text('contraindications')->nullable();   // conditions that restrict treatments
            $table->boolean('is_pregnant')->default(false);
            $table->boolean('has_pacemaker')->default(false);
            $table->boolean('has_metal_implants')->default(false);
            $table->text('other_conditions')->nullable();
            // Insurance
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_number')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->string('insurance_plan')->nullable();
            // Audit
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_medical_info');
        Schema::dropIfExists('patients');
    }
};
