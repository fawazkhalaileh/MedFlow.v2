<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                         // e.g. "Full Leg Laser Hair Removal - 6 Sessions"
            $table->integer('total_sessions');
            $table->integer('completed_sessions')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');    // active, completed, paused, cancelled
            $table->decimal('total_price', 10, 2)->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('treatment_areas')->nullable();    // e.g. ["upper_lip", "chin", "full_legs"]
            $table->json('session_settings')->nullable();   // default settings for laser sessions
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plans');
    }
};
