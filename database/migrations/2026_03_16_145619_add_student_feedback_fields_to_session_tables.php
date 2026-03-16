<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['quran_sessions', 'academic_sessions', 'interactive_course_sessions'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'student_rating')) {
                    $table->unsignedTinyInteger('student_rating')->nullable();
                }
                if (! Schema::hasColumn($tableName, 'student_feedback')) {
                    $table->text('student_feedback')->nullable();
                }
            });
        }

        // Also add session_notes and teacher_feedback to academic_sessions if missing
        Schema::table('academic_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_sessions', 'session_notes')) {
                $table->text('session_notes')->nullable();
            }
            if (! Schema::hasColumn('academic_sessions', 'teacher_feedback')) {
                $table->text('teacher_feedback')->nullable();
            }
            if (! Schema::hasColumn('academic_sessions', 'topics_covered')) {
                $table->json('topics_covered')->nullable();
            }
        });

        // Add override tracking to meeting_attendances
        Schema::table('meeting_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('meeting_attendances', 'override_reason')) {
                $table->string('override_reason', 500)->nullable();
            }
            if (! Schema::hasColumn('meeting_attendances', 'overridden_by')) {
                $table->unsignedBigInteger('overridden_by')->nullable();
            }
            if (! Schema::hasColumn('meeting_attendances', 'academy_id')) {
                $table->unsignedBigInteger('academy_id')->nullable()->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['quran_sessions', 'academic_sessions', 'interactive_course_sessions'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $columns = [];
                if (Schema::hasColumn($tableName, 'student_rating')) {
                    $columns[] = 'student_rating';
                }
                if (Schema::hasColumn($tableName, 'student_feedback')) {
                    $columns[] = 'student_feedback';
                }
                if ($columns) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::table('academic_sessions', function (Blueprint $table) {
            $columns = [];
            foreach (['session_notes', 'teacher_feedback', 'topics_covered'] as $col) {
                if (Schema::hasColumn('academic_sessions', $col)) {
                    $columns[] = $col;
                }
            }
            if ($columns) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $columns = [];
            foreach (['override_reason', 'overridden_by', 'academy_id'] as $col) {
                if (Schema::hasColumn('meeting_attendances', $col)) {
                    $columns[] = $col;
                }
            }
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
