<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add supervisor_notes column to all session and related tables.
     * This field is for supervisor-specific notes separate from teacher's session_notes.
     */
    public function up(): void
    {
        // Add to quran_sessions (if not already exists - may have partially run before)
        if (! Schema::hasColumn('quran_sessions', 'supervisor_notes')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->text('supervisor_notes')->nullable()->after('session_notes');
            });
        }

        // Add to academic_sessions (no session_notes column, add after recording_enabled)
        if (! Schema::hasColumn('academic_sessions', 'supervisor_notes')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                $table->text('supervisor_notes')->nullable()->after('recording_enabled');
            });
        }

        // Add to interactive_course_sessions
        if (! Schema::hasColumn('interactive_course_sessions', 'supervisor_notes')) {
            Schema::table('interactive_course_sessions', function (Blueprint $table) {
                $table->text('supervisor_notes')->nullable()->after('session_notes');
            });
        }

        // Add to quran_circles (after learning_objectives - no notes column exists)
        if (! Schema::hasColumn('quran_circles', 'supervisor_notes')) {
            Schema::table('quran_circles', function (Blueprint $table) {
                $table->text('supervisor_notes')->nullable()->after('learning_objectives');
            });
        }

        // Add to quran_individual_circles (after teacher_notes)
        if (! Schema::hasColumn('quran_individual_circles', 'supervisor_notes')) {
            Schema::table('quran_individual_circles', function (Blueprint $table) {
                $table->text('supervisor_notes')->nullable()->after('teacher_notes');
            });
        }

        // Add to academic_individual_lessons (after teacher_notes)
        if (! Schema::hasColumn('academic_individual_lessons', 'supervisor_notes')) {
            Schema::table('academic_individual_lessons', function (Blueprint $table) {
                $table->text('supervisor_notes')->nullable()->after('teacher_notes');
            });
        }

        // Add to interactive_courses (after recording_enabled)
        if (! Schema::hasColumn('interactive_courses', 'supervisor_notes')) {
            Schema::table('interactive_courses', function (Blueprint $table) {
                $table->text('supervisor_notes')->nullable()->after('recording_enabled');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'quran_sessions',
            'academic_sessions',
            'interactive_course_sessions',
            'quran_circles',
            'quran_individual_circles',
            'academic_individual_lessons',
            'interactive_courses',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasColumn($tableName, 'supervisor_notes')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('supervisor_notes');
                });
            }
        }
    }
};
