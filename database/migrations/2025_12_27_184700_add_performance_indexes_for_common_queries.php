<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
     * Check if an index exists on a table (MySQL compatible).
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $database = config('database.connections.mysql.database');
        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$database, $table, $indexName]
        );

        return $result[0]->count > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index for subscription queries (student_id + academy_id is a common filter)
        if (Schema::hasTable('quran_subscriptions')) {
            Schema::table('quran_subscriptions', function (Blueprint $table) {
                if (! $this->indexExists('quran_subscriptions', 'quran_subscriptions_student_academy_idx')) {
                    $table->index(['student_id', 'academy_id'], 'quran_subscriptions_student_academy_idx');
                }

                if (! $this->indexExists('quran_subscriptions', 'quran_subscriptions_teacher_idx')) {
                    $table->index('quran_teacher_id', 'quran_subscriptions_teacher_idx');
                }
            });
        }

        if (Schema::hasTable('academic_subscriptions')) {
            Schema::table('academic_subscriptions', function (Blueprint $table) {
                if (! $this->indexExists('academic_subscriptions', 'academic_subscriptions_student_academy_idx')) {
                    $table->index(['student_id', 'academy_id'], 'academic_subscriptions_student_academy_idx');
                }

                if (! $this->indexExists('academic_subscriptions', 'academic_subscriptions_teacher_idx')) {
                    $table->index('teacher_id', 'academic_subscriptions_teacher_idx');
                }
            });
        }

        // Index for session queries (teacher + month + status is common for dashboard stats)
        if (Schema::hasTable('quran_sessions')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                if (! $this->indexExists('quran_sessions', 'quran_sessions_teacher_month_status_idx')) {
                    $table->index(['quran_teacher_id', 'session_month', 'status'], 'quran_sessions_teacher_month_status_idx');
                }
            });
        }

        if (Schema::hasTable('academic_sessions')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                if (! $this->indexExists('academic_sessions', 'academic_sessions_teacher_status_idx')) {
                    $table->index(['academic_teacher_id', 'status'], 'academic_sessions_teacher_status_idx');
                }
            });
        }

        // Index for earnings queries (academy + month is common for payout calculations)
        if (Schema::hasTable('teacher_earnings')) {
            Schema::table('teacher_earnings', function (Blueprint $table) {
                if (! $this->indexExists('teacher_earnings', 'teacher_earnings_academy_month_idx')) {
                    $table->index(['academy_id', 'earning_month'], 'teacher_earnings_academy_month_idx');
                }

                if (! $this->indexExists('teacher_earnings', 'teacher_earnings_payout_idx')) {
                    $table->index('payout_id', 'teacher_earnings_payout_idx');
                }
            });
        }

        // Index for payments table (user_id + academy_id is common for parent/student payment queries)
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! $this->indexExists('payments', 'payments_user_academy_idx')) {
                    $table->index(['user_id', 'academy_id'], 'payments_user_academy_idx');
                }

                if (! $this->indexExists('payments', 'payments_status_idx')) {
                    $table->index('status', 'payments_status_idx');
                }
            });
        }

        // Index for chat groups (circle_id for unique constraint checking)
        if (Schema::hasTable('chat_groups')) {
            Schema::table('chat_groups', function (Blueprint $table) {
                if (! $this->indexExists('chat_groups', 'chat_groups_quran_circle_unique')) {
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
                if ($this->indexExists('quran_subscriptions', 'quran_subscriptions_student_academy_idx')) {
                    $table->dropIndex('quran_subscriptions_student_academy_idx');
                }
                if ($this->indexExists('quran_subscriptions', 'quran_subscriptions_teacher_idx')) {
                    $table->dropIndex('quran_subscriptions_teacher_idx');
                }
            });
        }

        if (Schema::hasTable('academic_subscriptions')) {
            Schema::table('academic_subscriptions', function (Blueprint $table) {
                if ($this->indexExists('academic_subscriptions', 'academic_subscriptions_student_academy_idx')) {
                    $table->dropIndex('academic_subscriptions_student_academy_idx');
                }
                if ($this->indexExists('academic_subscriptions', 'academic_subscriptions_teacher_idx')) {
                    $table->dropIndex('academic_subscriptions_teacher_idx');
                }
            });
        }

        if (Schema::hasTable('quran_sessions')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                if ($this->indexExists('quran_sessions', 'quran_sessions_teacher_month_status_idx')) {
                    $table->dropIndex('quran_sessions_teacher_month_status_idx');
                }
            });
        }

        if (Schema::hasTable('academic_sessions')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                if ($this->indexExists('academic_sessions', 'academic_sessions_teacher_status_idx')) {
                    $table->dropIndex('academic_sessions_teacher_status_idx');
                }
            });
        }

        if (Schema::hasTable('teacher_earnings')) {
            Schema::table('teacher_earnings', function (Blueprint $table) {
                if ($this->indexExists('teacher_earnings', 'teacher_earnings_academy_month_idx')) {
                    $table->dropIndex('teacher_earnings_academy_month_idx');
                }
                if ($this->indexExists('teacher_earnings', 'teacher_earnings_payout_idx')) {
                    $table->dropIndex('teacher_earnings_payout_idx');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if ($this->indexExists('payments', 'payments_user_academy_idx')) {
                    $table->dropIndex('payments_user_academy_idx');
                }
                if ($this->indexExists('payments', 'payments_status_idx')) {
                    $table->dropIndex('payments_status_idx');
                }
            });
        }

        if (Schema::hasTable('chat_groups')) {
            Schema::table('chat_groups', function (Blueprint $table) {
                if ($this->indexExists('chat_groups', 'chat_groups_quran_circle_unique')) {
                    $table->dropUnique('chat_groups_quran_circle_unique');
                }
            });
        }
    }
};
