<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('doctor_visit_outcome', 80)->nullable()->after('assessment');
            $table->text('checkout_summary')->nullable()->after('doctor_recommendations');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'doctor_visit_outcome',
                'checkout_summary',
            ]);
        });
    }
};
