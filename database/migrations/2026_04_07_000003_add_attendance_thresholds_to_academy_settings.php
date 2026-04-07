<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add dynamic attendance threshold settings to academy_settings.
 *
 * Previously hardcoded in config/business.php, now configurable per academy:
 * - Student: minimum_presence_percent (50%), left_threshold_percent (30%)
 * - Teacher: full_attendance_percent (90%), partial_attendance_percent (50%)
 *
 * Existing dynamic settings (already in academy_settings):
 * - default_late_tolerance_minutes (grace period, default 15)
 * - default_attendance_threshold_percentage (student full attendance, default 80)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academy_settings', function (Blueprint $table) {
            // Student thresholds (make existing hardcoded config values dynamic)
            $table->decimal('student_minimum_presence_percent', 5, 2)
                ->default(50.00)
                ->after('default_attendance_threshold_percentage')
                ->comment('Min % of session time for student to not be ABSENT');

            $table->decimal('student_left_threshold_percent', 5, 2)
                ->default(30.00)
                ->after('student_minimum_presence_percent')
                ->comment('Min % of session time for LEFT vs ABSENT');

            // Teacher thresholds (new, different from student)
            $table->decimal('teacher_full_attendance_percent', 5, 2)
                ->default(90.00)
                ->after('student_left_threshold_percent')
                ->comment('Min % of session time for teacher ATTENDED status');

            $table->decimal('teacher_partial_attendance_percent', 5, 2)
                ->default(50.00)
                ->after('teacher_full_attendance_percent')
                ->comment('Min % of session time for teacher PARTIALLY_ATTENDED status');
        });
    }

    public function down(): void
    {
        Schema::table('academy_settings', function (Blueprint $table) {
            $table->dropColumn([
                'student_minimum_presence_percent',
                'student_left_threshold_percent',
                'teacher_full_attendance_percent',
                'teacher_partial_attendance_percent',
            ]);
        });
    }
};
