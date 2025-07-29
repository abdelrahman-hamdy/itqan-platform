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
        // Remove age fields from grade_levels table
        Schema::table('grade_levels', function (Blueprint $table) {
            $table->dropColumn(['min_age', 'max_age']);
        });

        // Remove can_create_courses field from academic_teachers table
        Schema::table('academic_teachers', function (Blueprint $table) {
            $table->dropColumn('can_create_courses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back age fields to grade_levels table
        Schema::table('grade_levels', function (Blueprint $table) {
            $table->integer('min_age')->default(6)->after('level');
            $table->integer('max_age')->default(18)->after('min_age');
        });

        // Add back can_create_courses field to academic_teachers table
        Schema::table('academic_teachers', function (Blueprint $table) {
            $table->boolean('can_create_courses')->default(false)->after('is_active');
        });
    }
};
