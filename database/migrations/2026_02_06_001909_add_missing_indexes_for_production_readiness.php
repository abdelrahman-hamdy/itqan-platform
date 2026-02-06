<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing indexes on foreign key columns for production performance.
     * These columns end with _id but lack database indexes.
     */
    public function up(): void
    {
        // High-priority: frequently queried foreign keys
        $indexes = [
            'student_profiles' => ['parent_id'],
            'academic_subscriptions' => ['subject_id', 'grade_level_id'],
            'courses' => ['grade_level_id', 'subject_id'],
            'lessons' => ['quiz_id'],
            'payments' => ['invoice_id'],
            'meeting_attendance_events' => ['leave_event_id'],
            'student_progress' => ['course_section_id'],
        ];

        foreach ($indexes as $table => $columns) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($columns) {
                    foreach ($columns as $column) {
                        if (Schema::hasColumn($table->getTable(), $column)) {
                            $table->index($column);
                        }
                    }
                });
            }
        }
    }

    public function down(): void
    {
        $indexes = [
            'student_profiles' => ['parent_id'],
            'academic_subscriptions' => ['subject_id', 'grade_level_id'],
            'courses' => ['grade_level_id', 'subject_id'],
            'lessons' => ['quiz_id'],
            'payments' => ['invoice_id'],
            'meeting_attendance_events' => ['leave_event_id'],
            'student_progress' => ['course_section_id'],
        ];

        foreach ($indexes as $table => $columns) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($columns) {
                    foreach ($columns as $column) {
                        $table->dropIndex([$column]);
                    }
                });
            }
        }
    }
};
