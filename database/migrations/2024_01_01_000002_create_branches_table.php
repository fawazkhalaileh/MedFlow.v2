<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();           // e.g. BR-001
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->json('working_hours')->nullable();    // { mon: {open:'09:00', close:'18:00'}, ... }
            $table->json('services_offered')->nullable(); // array of service_id
            $table->unsignedBigInteger('manager_id')->nullable(); // FK to users
            $table->string('status')->default('active'); // active, inactive, under_renovation
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();         // branch-level config overrides
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
