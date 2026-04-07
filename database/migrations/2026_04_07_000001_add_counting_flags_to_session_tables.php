<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add counting flags and teacher attendance tracking to session tables.
 *
 * New columns:
 * - counts_for_teacher: Admin-overridable flag controlling teacher earnings (null=auto)
 * - counts_for_teacher_set_by: Audit trail for who changed the flag
 * - counts_for_teacher_set_at: When the flag was changed
 * - teacher_attendance_status: Auto-calculated teacher attendance (ATTENDED/LATE/LEFT/ABSENT/PARTIALLY_ATTENDED)
 * - teacher_attendance_calculated_at: When teacher attendance was calculated
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = ['quran_sessions', 'academic_sessions', 'interactive_course_sessions'];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'counts_for_teacher')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                // Counting flag for teacher earnings (null = auto-calculated from attendance)
                $afterCol = Schema::hasColumn($table, 'subscription_counted') ? 'subscription_counted' : 'attendance_status';
                $t->boolean('counts_for_teacher')->nullable()->default(null)->after($afterCol);
                $t->unsignedBigInteger('counts_for_teacher_set_by')->nullable()->after('counts_for_teacher');
                $t->timestamp('counts_for_teacher_set_at')->nullable()->after('counts_for_teacher_set_by');

                // Teacher attendance tracking
                $t->string('teacher_attendance_status', 30)->nullable()->after('attendance_status');
                $t->timestamp('teacher_attendance_calculated_at')->nullable()->after('teacher_attendance_status');

                // Foreign key for audit trail
                $t->foreign('counts_for_teacher_set_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                // Index for filtering by teacher attendance status
                $t->index('teacher_attendance_status');
            });
        }
    }

    public function down(): void
    {
        $tables = ['quran_sessions', 'academic_sessions', 'interactive_course_sessions'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropIndex(["{$table}_teacher_attendance_status_index"]);
                $t->dropForeign(["{$table}_counts_for_teacher_set_by_foreign"]);
                $t->dropColumn([
                    'counts_for_teacher',
                    'counts_for_teacher_set_by',
                    'counts_for_teacher_set_at',
                    'teacher_attendance_status',
                    'teacher_attendance_calculated_at',
                ]);
            });
        }
    }
};
