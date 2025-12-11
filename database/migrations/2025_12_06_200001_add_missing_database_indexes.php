<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds missing indexes for performance optimization on frequently queried columns.
     */
    public function up(): void
    {
        // Add index to academic_sessions for subscription and status lookup
        if (Schema::hasTable('academic_sessions')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                // Check if index exists before adding
                $indexExists = collect(DB::select("SHOW INDEX FROM academic_sessions WHERE Key_name = 'academic_sessions_sub_status_idx'"))->isNotEmpty();
                if (!$indexExists && Schema::hasColumn('academic_sessions', 'academic_subscription_id') && Schema::hasColumn('academic_sessions', 'status')) {
                    $table->index(['academic_subscription_id', 'status'], 'academic_sessions_sub_status_idx');
                }
            });
        }

        // Add index to payment_audit_logs for user history lookup
        if (Schema::hasTable('payment_audit_logs')) {
            Schema::table('payment_audit_logs', function (Blueprint $table) {
                $indexExists = collect(DB::select("SHOW INDEX FROM payment_audit_logs WHERE Key_name = 'payment_audit_logs_user_created_idx'"))->isNotEmpty();
                if (!$indexExists && Schema::hasColumn('payment_audit_logs', 'user_id') && Schema::hasColumn('payment_audit_logs', 'created_at')) {
                    $table->index(['user_id', 'created_at'], 'payment_audit_logs_user_created_idx');
                }
            });
        }

        // Add index to quran_sessions for subscription lookup
        if (Schema::hasTable('quran_sessions')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $indexExists = collect(DB::select("SHOW INDEX FROM quran_sessions WHERE Key_name = 'quran_sessions_sub_status_idx'"))->isNotEmpty();
                if (!$indexExists && Schema::hasColumn('quran_sessions', 'quran_subscription_id') && Schema::hasColumn('quran_sessions', 'status')) {
                    $table->index(['quran_subscription_id', 'status'], 'quran_sessions_sub_status_idx');
                }
            });
        }

        // Add index to interactive_course_sessions for course lookup
        if (Schema::hasTable('interactive_course_sessions')) {
            Schema::table('interactive_course_sessions', function (Blueprint $table) {
                $indexExists = collect(DB::select("SHOW INDEX FROM interactive_course_sessions WHERE Key_name = 'interactive_sessions_course_status_idx'"))->isNotEmpty();
                if (!$indexExists && Schema::hasColumn('interactive_course_sessions', 'interactive_course_id') && Schema::hasColumn('interactive_course_sessions', 'status')) {
                    $table->index(['interactive_course_id', 'status'], 'interactive_sessions_course_status_idx');
                }
            });
        }

        // Add index to homework_submissions for student lookup
        if (Schema::hasTable('homework_submissions')) {
            Schema::table('homework_submissions', function (Blueprint $table) {
                $indexExists = collect(DB::select("SHOW INDEX FROM homework_submissions WHERE Key_name = 'homework_submissions_student_status_idx'"))->isNotEmpty();
                if (!$indexExists && Schema::hasColumn('homework_submissions', 'student_id') && Schema::hasColumn('homework_submissions', 'status')) {
                    $table->index(['student_id', 'status'], 'homework_submissions_student_status_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'academic_sessions' => 'academic_sessions_sub_status_idx',
            'payment_audit_logs' => 'payment_audit_logs_user_created_idx',
            'quran_sessions' => 'quran_sessions_sub_status_idx',
            'interactive_course_sessions' => 'interactive_sessions_course_status_idx',
            'homework_submissions' => 'homework_submissions_student_status_idx',
        ];

        foreach ($tables as $tableName => $indexName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    try {
                        $table->dropIndex($indexName);
                    } catch (\Exception $e) {
                        // Index may not exist, ignore
                    }
                });
            }
        }
    }
};
