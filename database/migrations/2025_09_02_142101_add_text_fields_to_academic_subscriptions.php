<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->string('subject_name')->nullable()->after('subject_id')->comment('اسم المادة الدراسية');
            $table->string('grade_level_name')->nullable()->after('grade_level_id')->comment('اسم المرحلة الدراسية');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['subject_name', 'grade_level_name']);
        });
    }
};