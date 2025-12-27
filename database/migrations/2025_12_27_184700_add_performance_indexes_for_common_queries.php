<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes for frequently queried columns.
 *
 * These indexes were identified through codebase analysis to improve
 * query performance for common operations.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index for subscription queries (student_id + academy_id is a common filter)
        if (Schema::hasTable('quran_subscriptions')) {
            Schema::table('quran_subscriptions', function (Blueprint $table) {
                // Check if index exists before adding
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('quran_subscriptions');

                if (!isset($indexes['quran_subscriptions_student_academy_idx'])) {
                    $table->index(['student_id', 'academy_id'], 'quran_subscriptions_student_academy_idx');
                }

                if (!isset($indexes['quran_subscriptions_teacher_idx'])) {
                    $table->index('quran_teacher_id', 'quran_subscriptions_teacher_idx');
                }
            });
        }

        if (Schema::hasTable('academic_subscriptions')) {
            Schema::table('academic_subscriptions', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('academic_subscriptions');

                if (!isset($indexes['academic_subscriptions_student_academy_idx'])) {
                    $table->index(['student_id', 'academy_id'], 'academic_subscriptions_student_academy_idx');
                }

                if (!isset($indexes['academic_subscriptions_teacher_idx'])) {
                    $table->index('academic_teacher_id', 'academic_subscriptions_teacher_idx');
                }
            });
        }

        // Index for session queries (teacher + month + status is common for dashboard stats)
        if (Schema::hasTable('quran_sessions')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('quran_sessions');

                if (!isset($indexes['quran_sessions_teacher_month_status_idx'])) {
                    $table->index(['quran_teacher_id', 'session_month', 'status'], 'quran_sessions_teacher_month_status_idx');
                }
            });
        }

        if (Schema::hasTable('academic_sessions')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('academic_sessions');

                if (!isset($indexes['academic_sessions_teacher_status_idx'])) {
                    $table->index(['academic_teacher_id', 'status'], 'academic_sessions_teacher_status_idx');
                }
            });
        }

        // Index for earnings queries (academy + month is common for payout calculations)
        if (Schema::hasTable('teacher_earnings')) {
            Schema::table('teacher_earnings', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('teacher_earnings');

                if (!isset($indexes['teacher_earnings_academy_month_idx'])) {
                    $table->index(['academy_id', 'earning_month'], 'teacher_earnings_academy_month_idx');
                }

                if (!isset($indexes['teacher_earnings_payout_idx'])) {
                    $table->index('payout_id', 'teacher_earnings_payout_idx');
                }
            });
        }

        // Index for payments table (user_id + academy_id is common for parent/student payment queries)
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('payments');

                if (!isset($indexes['payments_user_academy_idx'])) {
                    $table->index(['user_id', 'academy_id'], 'payments_user_academy_idx');
                }

                if (!isset($indexes['payments_status_idx'])) {
                    $table->index('status', 'payments_status_idx');
                }
            });
        }

        // Index for chat groups (circle_id for unique constraint checking)
        if (Schema::hasTable('chat_groups')) {
            Schema::table('chat_groups', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('chat_groups');

                if (!isset($indexes['chat_groups_quran_circle_unique'])) {
                    // Use unique index for idempotency
                    $table->unique('quran_circle_id', 'chat_groups_quran_circle_unique');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quran_subscriptions')) {
            Schema::table('quran_subscriptions', function (Blueprint $table) {
                $table->dropIndex('quran_subscriptions_student_academy_idx');
                $table->dropIndex('quran_subscriptions_teacher_idx');
            });
        }

        if (Schema::hasTable('academic_subscriptions')) {
            Schema::table('academic_subscriptions', function (Blueprint $table) {
                $table->dropIndex('academic_subscriptions_student_academy_idx');
                $table->dropIndex('academic_subscriptions_teacher_idx');
            });
        }

        if (Schema::hasTable('quran_sessions')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->dropIndex('quran_sessions_teacher_month_status_idx');
            });
        }

        if (Schema::hasTable('academic_sessions')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                $table->dropIndex('academic_sessions_teacher_status_idx');
            });
        }

        if (Schema::hasTable('teacher_earnings')) {
            Schema::table('teacher_earnings', function (Blueprint $table) {
                $table->dropIndex('teacher_earnings_academy_month_idx');
                $table->dropIndex('teacher_earnings_payout_idx');
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('payments_user_academy_idx');
                $table->dropIndex('payments_status_idx');
            });
        }

        if (Schema::hasTable('chat_groups')) {
            Schema::table('chat_groups', function (Blueprint $table) {
                $table->dropUnique('chat_groups_quran_circle_unique');
            });
        }
    }
};
