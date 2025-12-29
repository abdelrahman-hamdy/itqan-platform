<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes for frequently queried patterns.
 *
 * These indexes were identified through query analysis to improve:
 * - CircleEnrollmentService subscription queries
 * - SessionManagementService session queries
 * - PayoutService earnings queries
 * - Calendar queries
 * - Attendance queries
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Quran Subscriptions - commonly queried by student + academy
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            if (!$this->hasIndex('quran_subscriptions', 'idx_quran_subs_student_academy')) {
                $table->index(['student_id', 'academy_id'], 'idx_quran_subs_student_academy');
            }
            if (!$this->hasIndex('quran_subscriptions', 'idx_quran_subs_teacher')) {
                $table->index('quran_teacher_id', 'idx_quran_subs_teacher');
            }
            if (!$this->hasIndex('quran_subscriptions', 'idx_quran_subs_status')) {
                $table->index('status', 'idx_quran_subs_status');
            }
        });

        // Academic Subscriptions - commonly queried by student + academy
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            if (!$this->hasIndex('academic_subscriptions', 'idx_academic_subs_student_academy')) {
                $table->index(['student_id', 'academy_id'], 'idx_academic_subs_student_academy');
            }
            if (!$this->hasIndex('academic_subscriptions', 'idx_academic_subs_teacher')) {
                $table->index('teacher_id', 'idx_academic_subs_teacher');
            }
            if (!$this->hasIndex('academic_subscriptions', 'idx_academic_subs_status')) {
                $table->index('status', 'idx_academic_subs_status');
            }
        });

        // Quran Sessions - frequently filtered by teacher + month + status
        Schema::table('quran_sessions', function (Blueprint $table) {
            if (!$this->hasIndex('quran_sessions', 'idx_quran_sessions_teacher_month')) {
                $table->index(['quran_teacher_id', 'session_month'], 'idx_quran_sessions_teacher_month');
            }
            if (!$this->hasIndex('quran_sessions', 'idx_quran_sessions_status_scheduled')) {
                $table->index(['status', 'scheduled_at'], 'idx_quran_sessions_status_scheduled');
            }
            if (!$this->hasIndex('quran_sessions', 'idx_quran_sessions_student')) {
                $table->index('student_id', 'idx_quran_sessions_student');
            }
        });

        // Academic Sessions - frequently filtered by teacher + status
        Schema::table('academic_sessions', function (Blueprint $table) {
            if (!$this->hasIndex('academic_sessions', 'idx_academic_sessions_teacher_status')) {
                $table->index(['academic_teacher_id', 'status'], 'idx_academic_sessions_teacher_status');
            }
            if (!$this->hasIndex('academic_sessions', 'idx_academic_sessions_status_scheduled')) {
                $table->index(['status', 'scheduled_at'], 'idx_academic_sessions_status_scheduled');
            }
            if (!$this->hasIndex('academic_sessions', 'idx_academic_sessions_student')) {
                $table->index('student_id', 'idx_academic_sessions_student');
            }
        });

        // Interactive Course Sessions - frequently filtered by course + status
        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            if (!$this->hasIndex('interactive_course_sessions', 'idx_ics_course_status')) {
                $table->index(['course_id', 'status'], 'idx_ics_course_status');
            }
            if (!$this->hasIndex('interactive_course_sessions', 'idx_ics_scheduled')) {
                $table->index('scheduled_at', 'idx_ics_scheduled');
            }
        });

        // Teacher Earnings - commonly queried by academy + month for payouts
        Schema::table('teacher_earnings', function (Blueprint $table) {
            if (!$this->hasIndex('teacher_earnings', 'idx_earnings_academy_month')) {
                $table->index(['academy_id', 'earning_month'], 'idx_earnings_academy_month');
            }
            if (!$this->hasIndex('teacher_earnings', 'idx_earnings_teacher_finalized')) {
                $table->index(['teacher_id', 'is_finalized'], 'idx_earnings_teacher_finalized');
            }
        });

        // Meeting Attendance - commonly queried for session attendance
        Schema::table('meeting_attendances', function (Blueprint $table) {
            if (!$this->hasIndex('meeting_attendances', 'idx_attendance_session')) {
                $table->index(['session_type', 'session_id'], 'idx_attendance_session');
            }
            if (!$this->hasIndex('meeting_attendances', 'idx_attendance_user')) {
                $table->index('user_id', 'idx_attendance_user');
            }
        });

        // Meeting Attendance Events - for detailed tracking
        if (Schema::hasTable('meeting_attendance_events')) {
            Schema::table('meeting_attendance_events', function (Blueprint $table) {
                if (!$this->hasIndex('meeting_attendance_events', 'idx_mae_session_user')) {
                    $table->index(['session_id', 'user_id'], 'idx_mae_session_user');
                }
                if (!$this->hasIndex('meeting_attendance_events', 'idx_mae_event_type')) {
                    $table->index('event_type', 'idx_mae_event_type');
                }
            });
        }

        // Quran Circles - commonly queried by teacher + status
        Schema::table('quran_circles', function (Blueprint $table) {
            if (!$this->hasIndex('quran_circles', 'idx_circles_teacher_status')) {
                $table->index(['quran_teacher_id', 'status'], 'idx_circles_teacher_status');
            }
            if (!$this->hasIndex('quran_circles', 'idx_circles_academy')) {
                $table->index('academy_id', 'idx_circles_academy');
            }
        });

        // Payments - commonly queried by user + status
        Schema::table('payments', function (Blueprint $table) {
            if (!$this->hasIndex('payments', 'idx_payments_user_status')) {
                $table->index(['user_id', 'status'], 'idx_payments_user_status');
            }
            if (!$this->hasIndex('payments', 'idx_payments_academy')) {
                $table->index('academy_id', 'idx_payments_academy');
            }
        });

        // Notifications - commonly queried by user + read status
        Schema::table('notifications', function (Blueprint $table) {
            if (!$this->hasIndex('notifications', 'idx_notifications_notifiable_read')) {
                $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'idx_notifications_notifiable_read');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_quran_subs_student_academy');
            $table->dropIndexIfExists('idx_quran_subs_teacher');
            $table->dropIndexIfExists('idx_quran_subs_status');
        });

        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_academic_subs_student_academy');
            $table->dropIndexIfExists('idx_academic_subs_teacher');
            $table->dropIndexIfExists('idx_academic_subs_status');
        });

        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_quran_sessions_teacher_month');
            $table->dropIndexIfExists('idx_quran_sessions_status_scheduled');
            $table->dropIndexIfExists('idx_quran_sessions_student');
        });

        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_academic_sessions_teacher_status');
            $table->dropIndexIfExists('idx_academic_sessions_status_scheduled');
            $table->dropIndexIfExists('idx_academic_sessions_student');
        });

        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_ics_course_status');
            $table->dropIndexIfExists('idx_ics_scheduled');
        });

        Schema::table('teacher_earnings', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_earnings_academy_month');
            $table->dropIndexIfExists('idx_earnings_teacher_finalized');
        });

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_attendance_session');
            $table->dropIndexIfExists('idx_attendance_user');
        });

        if (Schema::hasTable('meeting_attendance_events')) {
            Schema::table('meeting_attendance_events', function (Blueprint $table) {
                $table->dropIndexIfExists('idx_mae_session_user');
                $table->dropIndexIfExists('idx_mae_event_type');
            });
        }

        Schema::table('quran_circles', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_circles_teacher_status');
            $table->dropIndexIfExists('idx_circles_academy');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_payments_user_status');
            $table->dropIndexIfExists('idx_payments_academy');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_notifications_notifiable_read');
        });
    }

    /**
     * Check if an index exists on a table.
     * Laravel 11 compatible version using raw SQL.
     *
     * Also checks for alternative naming patterns from earlier migrations
     * to avoid creating duplicate indexes on the same columns.
     */
    private function hasIndex(string $table, string $index): bool
    {
        $database = config('database.connections.mysql.database');

        // Map of current index names to older naming patterns
        // (from 2025_12_27_184700_add_performance_indexes_for_common_queries.php)
        $alternativeNames = [
            'idx_quran_subs_student_academy' => 'quran_subscriptions_student_academy_idx',
            'idx_quran_subs_teacher' => 'quran_subscriptions_teacher_idx',
            'idx_academic_subs_student_academy' => 'academic_subscriptions_student_academy_idx',
            'idx_academic_subs_teacher' => 'academic_subscriptions_teacher_idx',
            'idx_quran_sessions_teacher_month' => 'quran_sessions_teacher_month_status_idx',
            'idx_academic_sessions_teacher_status' => 'academic_sessions_teacher_status_idx',
            'idx_earnings_academy_month' => 'teacher_earnings_academy_month_idx',
            'idx_payments_user_status' => 'payments_user_academy_idx',
            'idx_payments_academy' => 'payments_status_idx',
        ];

        // Check both the requested index and any alternative name
        $indexesToCheck = [$index];
        if (isset($alternativeNames[$index])) {
            $indexesToCheck[] = $alternativeNames[$index];
        }

        $placeholders = implode(',', array_fill(0, count($indexesToCheck), '?'));

        $result = \DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = ?
            AND table_name = ?
            AND index_name IN ({$placeholders})
        ", array_merge([$database, $table], $indexesToCheck));

        return $result[0]->count > 0;
    }
};
