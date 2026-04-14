<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing performance indexes for columns frequently used in WHERE/JOIN clauses.
     * All indexes are non-unique — MySQL 8 online DDL (ALGORITHM=INPLACE), no table locks.
     */
    public function up(): void
    {
        // meeting_attendances — used by attendance queries and cleanup logic
        $this->safeAddIndex('meeting_attendances', ['session_id', 'user_id'], 'ma_session_user_idx');
        $this->safeAddIndex('meeting_attendances', ['total_duration_minutes'], 'ma_duration_idx');

        // Attendance tables — counts_for_subscription and subscription_counted_at
        // (added in 2026_04_07_000002 without indexes)
        foreach (['quran_session_attendances', 'academic_session_attendances', 'interactive_session_attendances'] as $table) {
            $this->safeAddIndex($table, ['counts_for_subscription'], 'att_counts_sub_idx');
            $this->safeAddIndex($table, ['subscription_counted_at'], 'att_sub_counted_at_idx');
        }

        // Session tables — counts_for_teacher (added in 2026_04_07_000001 without index)
        foreach (['quran_sessions', 'academic_sessions', 'interactive_course_sessions'] as $table) {
            $this->safeAddIndex($table, ['counts_for_teacher'], 'sess_counts_teacher_idx');
        }

        // activity_log — used by audit log queries filtering by causer/subject
        $this->safeAddIndex('activity_log', ['causer_type', 'causer_id'], 'actlog_causer_idx');
        $this->safeAddIndex('activity_log', ['subject_type', 'subject_id'], 'actlog_subject_idx');
    }

    public function down(): void
    {
        $this->safeDropIndex('meeting_attendances', 'ma_session_user_idx');
        $this->safeDropIndex('meeting_attendances', 'ma_duration_idx');

        foreach (['quran_session_attendances', 'academic_session_attendances', 'interactive_session_attendances'] as $table) {
            $this->safeDropIndex($table, 'att_counts_sub_idx');
            $this->safeDropIndex($table, 'att_sub_counted_at_idx');
        }

        foreach (['quran_sessions', 'academic_sessions', 'interactive_course_sessions'] as $table) {
            $this->safeDropIndex($table, 'sess_counts_teacher_idx');
        }

        $this->safeDropIndex('activity_log', 'actlog_causer_idx');
        $this->safeDropIndex('activity_log', 'actlog_subject_idx');
    }

    private function safeAddIndex(string $table, array $columns, string $indexName): void
    {
        try {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $indexName));
        } catch (\Throwable) {
            // Table/column doesn't exist or index already exists — safe to ignore
        }
    }

    private function safeDropIndex(string $table, string $indexName): void
    {
        try {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($indexName));
        } catch (\Throwable) {
            // Table or index doesn't exist — safe to ignore
        }
    }
};
