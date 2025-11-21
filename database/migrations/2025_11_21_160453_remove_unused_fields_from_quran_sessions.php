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
     * Removes 31 unused fields from quran_sessions table:
     *
     * Session Configuration (5): location_type, location_details, lesson_objectives, recording_url, recording_enabled
     * Progress Tracking (11): current_face, page_covered_*, face_covered_*, papers_*, quality metrics
     * Session Management (8): areas_for_improvement, next_session_plan, technical_issues, materials_used, etc.
     * Follow-up (3): follow_up_required, follow_up_notes, teacher_scheduled_at
     * Deprecated (4): attendance_log, notification_log, meeting_creation_error, retry_count
     *
     * Also updates session_type enum to remove 'makeup' and 'assessment' types.
     */
    public function up(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Session Configuration (5 fields)
            if (Schema::hasColumn('quran_sessions', 'location_type')) {
                $table->dropColumn('location_type');
            }
            if (Schema::hasColumn('quran_sessions', 'location_details')) {
                $table->dropColumn('location_details');
            }
            if (Schema::hasColumn('quran_sessions', 'lesson_objectives')) {
                $table->dropColumn('lesson_objectives');
            }
            if (Schema::hasColumn('quran_sessions', 'recording_url')) {
                $table->dropColumn('recording_url');
            }
            if (Schema::hasColumn('quran_sessions', 'recording_enabled')) {
                $table->dropColumn('recording_enabled');
            }

            // Progress Tracking - Basic (6 fields)
            if (Schema::hasColumn('quran_sessions', 'current_face')) {
                $table->dropColumn('current_face');
            }
            if (Schema::hasColumn('quran_sessions', 'page_covered_start')) {
                $table->dropColumn('page_covered_start');
            }
            if (Schema::hasColumn('quran_sessions', 'face_covered_start')) {
                $table->dropColumn('face_covered_start');
            }
            if (Schema::hasColumn('quran_sessions', 'page_covered_end')) {
                $table->dropColumn('page_covered_end');
            }
            if (Schema::hasColumn('quran_sessions', 'face_covered_end')) {
                $table->dropColumn('face_covered_end');
            }
            if (Schema::hasColumn('quran_sessions', 'common_mistakes')) {
                $table->dropColumn('common_mistakes');
            }

            // Progress Tracking - Quality Metrics (5 fields - DELETE PER USER REQUEST)
            // User confirmed: Not in UI, covered by homework grading
            if (Schema::hasColumn('quran_sessions', 'papers_memorized_today')) {
                $table->dropColumn('papers_memorized_today');
            }
            if (Schema::hasColumn('quran_sessions', 'papers_covered_today')) {
                $table->dropColumn('papers_covered_today');
            }
            if (Schema::hasColumn('quran_sessions', 'recitation_quality')) {
                $table->dropColumn('recitation_quality');
            }
            if (Schema::hasColumn('quran_sessions', 'tajweed_accuracy')) {
                $table->dropColumn('tajweed_accuracy');
            }
            if (Schema::hasColumn('quran_sessions', 'mistakes_count')) {
                $table->dropColumn('mistakes_count');
            }

            // Session Management (8 fields)
            if (Schema::hasColumn('quran_sessions', 'areas_for_improvement')) {
                $table->dropColumn('areas_for_improvement');
            }
            if (Schema::hasColumn('quran_sessions', 'next_session_plan')) {
                $table->dropColumn('next_session_plan');
            }
            if (Schema::hasColumn('quran_sessions', 'technical_issues')) {
                $table->dropColumn('technical_issues');
            }
            if (Schema::hasColumn('quran_sessions', 'materials_used')) {
                $table->dropColumn('materials_used');
            }
            if (Schema::hasColumn('quran_sessions', 'learning_outcomes')) {
                $table->dropColumn('learning_outcomes');
            }
            if (Schema::hasColumn('quran_sessions', 'assessment_results')) {
                $table->dropColumn('assessment_results');
            }
            if (Schema::hasColumn('quran_sessions', 'is_makeup_session')) {
                $table->dropColumn('is_makeup_session');
            }

            // Drop makeup_session_for and its foreign key if exists
            if (Schema::hasColumn('quran_sessions', 'makeup_session_for')) {
                // Check if foreign key exists before trying to drop it
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'quran_sessions'
                    AND COLUMN_NAME = 'makeup_session_for'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");

                if (!empty($foreignKeys)) {
                    $table->dropForeign(['makeup_session_for']);
                }

                $table->dropColumn('makeup_session_for');
            }

            // Follow-up (3 fields)
            if (Schema::hasColumn('quran_sessions', 'follow_up_required')) {
                $table->dropColumn('follow_up_required');
            }
            if (Schema::hasColumn('quran_sessions', 'follow_up_notes')) {
                $table->dropColumn('follow_up_notes');
            }
            if (Schema::hasColumn('quran_sessions', 'teacher_scheduled_at')) {
                $table->dropColumn('teacher_scheduled_at');
            }

            // Deprecated (4 fields)
            if (Schema::hasColumn('quran_sessions', 'attendance_log')) {
                $table->dropColumn('attendance_log');
            }
            if (Schema::hasColumn('quran_sessions', 'notification_log')) {
                $table->dropColumn('notification_log');
            }
            if (Schema::hasColumn('quran_sessions', 'meeting_creation_error')) {
                $table->dropColumn('meeting_creation_error');
            }
            if (Schema::hasColumn('quran_sessions', 'retry_count')) {
                $table->dropColumn('retry_count');
            }
        });

        // Update session_type enum - Remove 'makeup' and 'assessment'
        DB::statement("ALTER TABLE quran_sessions MODIFY COLUMN session_type ENUM('individual', 'group', 'trial') NOT NULL DEFAULT 'individual'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Restore Session Configuration
            $table->enum('location_type', ['online', 'physical', 'hybrid'])->nullable();
            $table->text('location_details')->nullable();
            $table->json('lesson_objectives')->nullable();
            $table->string('recording_url', 255)->nullable();
            $table->boolean('recording_enabled')->default(false);

            // Restore Progress Tracking
            $table->integer('current_face')->nullable();
            $table->integer('page_covered_start')->nullable();
            $table->integer('face_covered_start')->nullable();
            $table->integer('page_covered_end')->nullable();
            $table->integer('face_covered_end')->nullable();
            $table->decimal('papers_memorized_today', 5, 2)->nullable();
            $table->decimal('papers_covered_today', 5, 2)->nullable();
            $table->decimal('recitation_quality', 3, 1)->nullable();
            $table->decimal('tajweed_accuracy', 3, 1)->nullable();
            $table->integer('mistakes_count')->nullable();
            $table->json('common_mistakes')->nullable();

            // Restore Session Management
            $table->json('areas_for_improvement')->nullable();
            $table->text('next_session_plan')->nullable();
            $table->text('technical_issues')->nullable();
            $table->json('materials_used')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->json('assessment_results')->nullable();
            $table->boolean('is_makeup_session')->default(false);
            $table->bigInteger('makeup_session_for')->unsigned()->nullable();

            // Restore Follow-up
            $table->boolean('follow_up_required')->default(false);
            $table->text('follow_up_notes')->nullable();
            $table->datetime('teacher_scheduled_at')->nullable();

            // Restore Deprecated
            $table->json('attendance_log')->nullable();
            $table->json('notification_log')->nullable();
            $table->text('meeting_creation_error')->nullable();
            $table->integer('retry_count')->default(0);
        });

        // Restore original enum
        DB::statement("ALTER TABLE quran_sessions MODIFY COLUMN session_type ENUM('individual', 'group', 'makeup', 'trial', 'assessment') NOT NULL DEFAULT 'individual'");
    }
};
