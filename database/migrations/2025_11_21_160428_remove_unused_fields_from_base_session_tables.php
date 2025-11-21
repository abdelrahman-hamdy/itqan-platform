<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes 5 unused fields from BaseSession across all session tables:
     * - meeting_source (consolidated with meeting_platform)
     * - attendance_notes (not needed)
     * - student_feedback (never used)
     * - parent_feedback (never used)
     * - overall_rating (never used)
     *
     * KEEPS:
     * - teacher_feedback (used in homework grading)
     * - cancellation_reason (audit trail)
     * - cancellation_type (smart subscription counting)
     */
    public function up(): void
    {
        // Remove from quran_sessions
        if (Schema::hasTable('quran_sessions')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                if (Schema::hasColumn('quran_sessions', 'meeting_source')) {
                    $table->dropColumn('meeting_source');
                }
                if (Schema::hasColumn('quran_sessions', 'attendance_notes')) {
                    $table->dropColumn('attendance_notes');
                }
                if (Schema::hasColumn('quran_sessions', 'student_feedback')) {
                    $table->dropColumn('student_feedback');
                }
                if (Schema::hasColumn('quran_sessions', 'parent_feedback')) {
                    $table->dropColumn('parent_feedback');
                }
                if (Schema::hasColumn('quran_sessions', 'overall_rating')) {
                    $table->dropColumn('overall_rating');
                }
            });
        }

        // Remove from academic_sessions
        if (Schema::hasTable('academic_sessions')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                if (Schema::hasColumn('academic_sessions', 'meeting_source')) {
                    $table->dropColumn('meeting_source');
                }
                if (Schema::hasColumn('academic_sessions', 'attendance_notes')) {
                    $table->dropColumn('attendance_notes');
                }
                if (Schema::hasColumn('academic_sessions', 'student_feedback')) {
                    $table->dropColumn('student_feedback');
                }
                if (Schema::hasColumn('academic_sessions', 'parent_feedback')) {
                    $table->dropColumn('parent_feedback');
                }
                if (Schema::hasColumn('academic_sessions', 'overall_rating')) {
                    $table->dropColumn('overall_rating');
                }
            });
        }

        // Remove from interactive_course_sessions
        if (Schema::hasTable('interactive_course_sessions')) {
            Schema::table('interactive_course_sessions', function (Blueprint $table) {
                if (Schema::hasColumn('interactive_course_sessions', 'meeting_source')) {
                    $table->dropColumn('meeting_source');
                }
                if (Schema::hasColumn('interactive_course_sessions', 'attendance_notes')) {
                    $table->dropColumn('attendance_notes');
                }
                if (Schema::hasColumn('interactive_course_sessions', 'student_feedback')) {
                    $table->dropColumn('student_feedback');
                }
                if (Schema::hasColumn('interactive_course_sessions', 'parent_feedback')) {
                    $table->dropColumn('parent_feedback');
                }
                if (Schema::hasColumn('interactive_course_sessions', 'overall_rating')) {
                    $table->dropColumn('overall_rating');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore to quran_sessions
        if (Schema::hasTable('quran_sessions')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->enum('meeting_source', ['jitsi', 'whereby', 'custom', 'google', 'platform', 'manual', 'livekit'])->nullable();
                $table->text('attendance_notes')->nullable();
                $table->text('student_feedback')->nullable();
                $table->text('parent_feedback')->nullable();
                $table->integer('overall_rating')->nullable();
            });
        }

        // Restore to academic_sessions
        if (Schema::hasTable('academic_sessions')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                $table->enum('meeting_source', ['jitsi', 'whereby', 'custom', 'google', 'platform', 'manual', 'livekit'])->nullable();
                $table->text('attendance_notes')->nullable();
                $table->text('student_feedback')->nullable();
                $table->text('parent_feedback')->nullable();
                $table->integer('overall_rating')->nullable();
            });
        }

        // Restore to interactive_course_sessions
        if (Schema::hasTable('interactive_course_sessions')) {
            Schema::table('interactive_course_sessions', function (Blueprint $table) {
                $table->enum('meeting_source', ['jitsi', 'whereby', 'custom', 'google', 'platform', 'manual', 'livekit'])->nullable();
                $table->text('attendance_notes')->nullable();
                $table->text('student_feedback')->nullable();
                $table->text('parent_feedback')->nullable();
                $table->integer('overall_rating')->nullable();
            });
        }
    }
};
