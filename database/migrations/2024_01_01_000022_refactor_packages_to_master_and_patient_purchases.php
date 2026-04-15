<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sessions_purchased');
            $table->unsignedInteger('sessions_used')->default(0);
            $table->decimal('final_price', 10, 2);
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('purchased_at');
            $table->foreignId('purchased_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['patient_id', 'package_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('patient_package_id')->nullable()->after('treatment_plan_id')->constrained('patient_packages')->nullOnDelete();
        });

        Schema::table('package_usages', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->renameColumn('package_id', 'patient_package_id');
        });

        Schema::table('package_usages', function (Blueprint $table) {
            $table->foreign('patient_package_id')->references('id')->on('patient_packages')->cascadeOnDelete();
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropIndex(['patient_id', 'service_id']);
            $table->dropColumn(['patient_id', 'sessions_used']);
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->after('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sessions_used')->default(0)->after('sessions_purchased');
        });

        Schema::table('package_usages', function (Blueprint $table) {
            $table->dropForeign(['patient_package_id']);
            $table->renameColumn('patient_package_id', 'package_id');
            $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['patient_package_id']);
            $table->dropColumn('patient_package_id');
        });

        Schema::dropIfExists('patient_packages');
    }
};
