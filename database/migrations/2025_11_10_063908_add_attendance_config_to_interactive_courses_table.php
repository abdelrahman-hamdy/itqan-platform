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
        Schema::table('interactive_courses', function (Blueprint $table) {
            // Attendance configuration fields (nullable - falls back to academy defaults)
            $table->integer('preparation_minutes')->nullable()->after('status')
                ->comment('Minutes before session start when students can join');
            $table->integer('buffer_minutes')->nullable()->after('preparation_minutes')
                ->comment('Buffer time after session ends');
            $table->integer('late_tolerance_minutes')->nullable()->after('buffer_minutes')
                ->comment('How many minutes late before marked as late');
            $table->decimal('attendance_threshold_percentage', 5, 2)->nullable()->after('late_tolerance_minutes')
                ->comment('Minimum attendance percentage required (e.g., 80.00)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interactive_courses', function (Blueprint $table) {
            $table->dropColumn([
                'preparation_minutes',
                'buffer_minutes',
                'late_tolerance_minutes',
                'attendance_threshold_percentage',
            ]);
        });
    }
};
