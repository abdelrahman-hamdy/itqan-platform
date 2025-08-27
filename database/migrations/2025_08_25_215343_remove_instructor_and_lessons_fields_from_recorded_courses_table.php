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
        Schema::table('recorded_courses', function (Blueprint $table) {
            // Remove instructor_id field as courses should not be linked to specific teacher
            $table->dropForeign(['instructor_id']);
            $table->dropColumn('instructor_id');

            // Remove total_lessons field as it should be calculated dynamically
            $table->dropColumn('total_lessons');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recorded_courses', function (Blueprint $table) {
            // Add back instructor_id field
            $table->foreignId('instructor_id')->nullable()->constrained('users')->onDelete('set null');

            // Add back total_lessons field
            $table->integer('total_lessons')->default(0);
        });
    }
};
