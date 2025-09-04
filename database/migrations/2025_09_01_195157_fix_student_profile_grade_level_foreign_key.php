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
        Schema::table('student_profiles', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['grade_level_id']);

            // Add the new foreign key constraint to academic_grade_levels
            $table->foreign('grade_level_id')
                ->references('id')
                ->on('academic_grade_levels')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['grade_level_id']);

            // Add back the original foreign key constraint
            $table->foreign('grade_level_id')
                ->references('id')
                ->on('grade_levels')
                ->onDelete('set null');
        });
    }
};
