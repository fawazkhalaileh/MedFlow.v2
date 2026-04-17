<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_compensation_profiles'))
        Schema::create('employee_compensation_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->string('compensation_type', 30)->default('salary_plus_commission');
            $table->decimal('fixed_salary', 10, 2)->default(0);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'employee_id', 'is_active']);
            $table->index(['branch_id', 'effective_from', 'effective_to']);
        });

        if (! Schema::hasTable('employee_commission_rules'))
        Schema::create('employee_commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rule_scope', 30)->default('global');
            $table->string('source_type', 40);
            $table->string('calculation_type', 40);
            $table->decimal('rate', 8, 2)->nullable();
            $table->decimal('flat_amount', 10, 2)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'source_type', 'is_active']);
            $table->index(['employee_id', 'branch_id', 'priority']);
        });

        if (! Schema::hasTable('work_attributions'))
        Schema::create('work_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('treatment_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_usage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type', 40);
            $table->string('attributable_type');
            $table->unsignedBigInteger('attributable_id');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('revenue_amount', 10, 2)->default(0);
            $table->timestamp('occurred_at');
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['employee_id', 'attributable_type', 'attributable_id', 'source_type'], 'work_attribution_unique');
            $table->index(['company_id', 'branch_id', 'occurred_at']);
            $table->index(['employee_id', 'source_type', 'occurred_at']);
        });

        if (! Schema::hasTable('compensation_snapshots'))
        Schema::create('compensation_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('fixed_salary', 10, 2)->default(0);
            $table->decimal('commission_total', 10, 2)->default(0);
            $table->decimal('total_due', 10, 2)->default(0);
            $table->json('breakdown')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'period_start', 'period_end']);
            $table->index(['employee_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compensation_snapshots');
        Schema::dropIfExists('work_attributions');
        Schema::dropIfExists('employee_commission_rules');
        Schema::dropIfExists('employee_compensation_profiles');
    }
};
