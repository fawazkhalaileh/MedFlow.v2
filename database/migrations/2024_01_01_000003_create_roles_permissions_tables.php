<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles are scoped per company and configurable
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');                        // slug: branch_manager, secretary
            $table->string('display_name');               // Branch Manager
            $table->string('color')->default('#6B7280');  // UI badge color
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // system roles cannot be deleted
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        // Granular permissions: module + action pairs
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module');  // customers, appointments, employees, reports, settings, billing, branches
            $table->string('action');  // view, create, edit, delete, approve, export, manage
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['module', 'action']);
        });

        // Role to permission assignments
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
