<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#3B82F6');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // clinic-type specific: { laser_type, wavelength, area }
            $table->timestamps();
            $table->softDeletes();
        });

        // Which services are available at which branches
        Schema::create('branch_services', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->decimal('price_override', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->primary(['branch_id', 'service_id']);
        });

        Schema::create('appointment_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#6B7280');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('appointment_reasons');
        Schema::dropIfExists('branch_services');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
    }
};
