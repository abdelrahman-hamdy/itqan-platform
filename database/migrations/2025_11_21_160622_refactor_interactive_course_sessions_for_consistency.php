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
     * Refactors interactive_course_sessions to match other session types:
     *
     * ADDS (4 fields):
     * - scheduled_at (datetime) - Consolidated from scheduled_date + scheduled_time
     * - meeting_link (varchar) - Renamed from google_meet_link
     * - academy_id (bigint) - Proper foreign key instead of virtual accessor
     * - homework_file (varchar) - Like AcademicSession
     *
     * REMOVES (7 fields):
     * - scheduled_date, scheduled_time (consolidated)
     * - google_meet_link (renamed)
     * - materials_uploaded (not needed)
     * - homework_due_date, homework_max_score, allow_late_submissions (simplified)
     *
     * KEEPS:
     * - homework_assigned (boolean)
     * - homework_description (text)
     * - attendance_count (integer)
     */
    public function up(): void
    {
        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            // Add new columns
            if (!Schema::hasColumn('interactive_course_sessions', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('session_number');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'meeting_link')) {
                $table->string('meeting_link', 255)->nullable()->after('scheduled_at');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'academy_id')) {
                $table->bigInteger('academy_id')->unsigned()->nullable()->after('course_id');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'homework_file')) {
                $table->string('homework_file', 500)->nullable()->after('homework_description');
            }
        });

        // Migrate data from old fields to new fields
        DB::statement("
            UPDATE interactive_course_sessions ics
            SET
                scheduled_at = CASE
                    WHEN scheduled_date IS NOT NULL AND scheduled_time IS NOT NULL
                    THEN TIMESTAMP(scheduled_date, scheduled_time)
                    ELSE NULL
                END,
                meeting_link = google_meet_link,
                academy_id = (SELECT academy_id FROM interactive_courses WHERE id = ics.course_id)
        ");

        // Add foreign key for academy_id
        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('interactive_course_sessions', 'academy_id')) {
                try {
                    $table->foreign('academy_id')
                        ->references('id')
                        ->on('academies')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
        });

        // Drop old columns
        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('interactive_course_sessions', 'scheduled_date')) {
                $table->dropColumn('scheduled_date');
            }
            if (Schema::hasColumn('interactive_course_sessions', 'scheduled_time')) {
                $table->dropColumn('scheduled_time');
            }
            if (Schema::hasColumn('interactive_course_sessions', 'google_meet_link')) {
                $table->dropColumn('google_meet_link');
            }
            if (Schema::hasColumn('interactive_course_sessions', 'materials_uploaded')) {
                $table->dropColumn('materials_uploaded');
            }
            if (Schema::hasColumn('interactive_course_sessions', 'homework_due_date')) {
                $table->dropColumn('homework_due_date');
            }
            if (Schema::hasColumn('interactive_course_sessions', 'homework_max_score')) {
                $table->dropColumn('homework_max_score');
            }
            if (Schema::hasColumn('interactive_course_sessions', 'allow_late_submissions')) {
                $table->dropColumn('allow_late_submissions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add old columns back
        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->text('google_meet_link')->nullable();
            $table->boolean('materials_uploaded')->default(false);
            $table->datetime('homework_due_date')->nullable();
            $table->integer('homework_max_score')->nullable();
            $table->boolean('allow_late_submissions')->default(false);
        });

        // Migrate data back
        DB::statement("
            UPDATE interactive_course_sessions
            SET
                scheduled_date = DATE(scheduled_at),
                scheduled_time = TIME(scheduled_at),
                google_meet_link = meeting_link
            WHERE scheduled_at IS NOT NULL
        ");

        // Drop foreign key and new columns
        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            // Drop foreign key first
            try {
                $table->dropForeign(['academy_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }

            if (Schema::hasColumn('interactive_course_sessions', 'scheduled_at')) {
                $table->dropColumn('scheduled_at');
            }
            if (Schema::hasColumn('interactive_course_sessions', 'meeting_link')) {
                $table->dropColumn('meeting_link');
            }
            if (Schema::hasColumn('interactive_course_sessions', 'academy_id')) {
                $table->dropColumn('academy_id');
            }
            if (Schema::hasColumn('interactive_course_sessions', 'homework_file')) {
                $table->dropColumn('homework_file');
            }
        });
    }
};
