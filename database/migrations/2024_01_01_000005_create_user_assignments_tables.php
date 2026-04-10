<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User can be assigned to multiple branches with a specific role per branch
        Schema::create('user_branch_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('assigned_at')->useCurrent();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'branch_id', 'role_id']);
        });

        // Work schedules per employee per branch
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_of_week'); // 0=Sunday ... 6=Saturday
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_day_off')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'branch_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');
        Schema::dropIfExists('user_branch_roles');
    }
};
