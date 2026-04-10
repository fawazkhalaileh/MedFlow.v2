<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('employee_id')->nullable()->after('company_id')->unique(); // EMP-001
            $table->string('first_name')->nullable()->after('employee_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('gender')->nullable();          // male, female, other
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('employee_type')->nullable();   // branch_manager, secretary, technician, doctor, nurse, finance, system_admin
            $table->string('employment_status')->default('active'); // active, inactive, on_leave, terminated
            $table->date('hire_date')->nullable();
            $table->string('profile_photo')->nullable();
            $table->text('employee_notes')->nullable();
            $table->json('certifications')->nullable();    // [{ name, issued_by, date, expiry }]
            $table->json('specialties')->nullable();       // laser types, skin types, etc.
            $table->foreignId('primary_branch_id')->nullable()->constrained('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_id', 'employee_id', 'first_name', 'last_name',
                'phone', 'gender', 'date_of_birth', 'address',
                'employee_type', 'employment_status', 'hire_date',
                'profile_photo', 'employee_notes', 'certifications',
                'specialties', 'primary_branch_id',
            ]);
        });
    }
};
