<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_flags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('category')->default('general');
            $table->string('color')->default('#dc2626');
            $table->string('icon')->nullable();
            $table->boolean('requires_detail')->default(false);
            $table->string('detail_placeholder')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('patient_clinical_flags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('flag_id');
            $table->string('detail')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamps();
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('flag_id')->references('id')->on('clinical_flags')->onDelete('cascade');
            $table->unique(['patient_id', 'flag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_clinical_flags');
        Schema::dropIfExists('clinical_flags');
    }
};
