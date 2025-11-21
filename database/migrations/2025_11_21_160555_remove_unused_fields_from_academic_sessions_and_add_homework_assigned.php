<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes 11 unused fields from academic_sessions:
     * - location_type, location_details (all sessions online)
     * - lesson_objectives (not used)
     * - session_topics_covered (user requested deletion)
     * - learning_outcomes, materials_used, assessment_results (not used)
     * - technical_issues (not used)
     * - makeup_session_for, is_makeup_session (not used)
     * - follow_up_required, follow_up_notes (not used)
     *
     * Adds 1 new field:
     * - homework_assigned (boolean) - Like InteractiveCourseSession
     *
     * KEEPS:
     * - lesson_content (user confirmed - useful for documenting lessons)
     * - homework_description, homework_file (active features)
     */
    public function up(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            // Session Configuration (3 fields)
            if (Schema::hasColumn('academic_sessions', 'location_type')) {
                $table->dropColumn('location_type');
            }
            if (Schema::hasColumn('academic_sessions', 'location_details')) {
                $table->dropColumn('location_details');
            }
            if (Schema::hasColumn('academic_sessions', 'lesson_objectives')) {
                $table->dropColumn('lesson_objectives');
            }

            // Content (3 fields - keep lesson_content)
            if (Schema::hasColumn('academic_sessions', 'session_topics_covered')) {
                $table->dropColumn('session_topics_covered');
            }
            if (Schema::hasColumn('academic_sessions', 'learning_outcomes')) {
                $table->dropColumn('learning_outcomes');
            }
            if (Schema::hasColumn('academic_sessions', 'materials_used')) {
                $table->dropColumn('materials_used');
            }
            if (Schema::hasColumn('academic_sessions', 'assessment_results')) {
                $table->dropColumn('assessment_results');
            }

            // Session Management (4 fields)
            if (Schema::hasColumn('academic_sessions', 'technical_issues')) {
                $table->dropColumn('technical_issues');
            }

            // Drop makeup_session_for and its foreign key if exists
            if (Schema::hasColumn('academic_sessions', 'makeup_session_for')) {
                // Check if foreign key exists before trying to drop it
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'academic_sessions'
                    AND COLUMN_NAME = 'makeup_session_for'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");

                if (!empty($foreignKeys)) {
                    $table->dropForeign(['makeup_session_for']);
                }

                $table->dropColumn('makeup_session_for');
            }
            if (Schema::hasColumn('academic_sessions', 'is_makeup_session')) {
                $table->dropColumn('is_makeup_session');
            }
            if (Schema::hasColumn('academic_sessions', 'follow_up_required')) {
                $table->dropColumn('follow_up_required');
            }
            if (Schema::hasColumn('academic_sessions', 'follow_up_notes')) {
                $table->dropColumn('follow_up_notes');
            }

            // Add homework_assigned field (like InteractiveCourseSession)
            if (!Schema::hasColumn('academic_sessions', 'homework_assigned')) {
                $table->boolean('homework_assigned')->default(false)->after('homework_file');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            // Restore Session Configuration
            $table->enum('location_type', ['online', 'physical', 'hybrid'])->nullable();
            $table->text('location_details')->nullable();
            $table->json('lesson_objectives')->nullable();

            // Restore Content
            $table->text('session_topics_covered')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->json('materials_used')->nullable();
            $table->json('assessment_results')->nullable();

            // Restore Session Management
            $table->text('technical_issues')->nullable();
            $table->bigInteger('makeup_session_for')->unsigned()->nullable();
            $table->boolean('is_makeup_session')->default(false);
            $table->boolean('follow_up_required')->default(false);
            $table->text('follow_up_notes')->nullable();

            // Remove homework_assigned
            if (Schema::hasColumn('academic_sessions', 'homework_assigned')) {
                $table->dropColumn('homework_assigned');
            }
        });
    }
};
