<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove unused/legacy fields from quran_sessions table.
 *
 * Fields being removed:
 * - google_event_id: Google Calendar integration was never implemented
 * - google_calendar_id: Google Calendar integration was never implemented
 * - attendance_marked_at: Attendance is now tracked via quran_session_attendances table
 * - attendance_marked_by: Attendance is now tracked via quran_session_attendances table
 *
 * Note: These fields were legacy placeholders that were never actively used
 * in production. The model's $fillable array already excludes these fields.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Drop foreign key for attendance_marked_by first (using Laravel's naming convention)
            if (Schema::hasColumn('quran_sessions', 'attendance_marked_by')) {
                $table->dropForeign('quran_sessions_attendance_marked_by_foreign');
            }

            // Drop index for google_event_id if it exists
            if (Schema::hasColumn('quran_sessions', 'google_event_id')) {
                $table->dropIndex('quran_sessions_google_event_id_index');
            }
        });

        Schema::table('quran_sessions', function (Blueprint $table) {
            // Drop the unused columns (check each one exists first)
            $columnsToDrop = [];

            if (Schema::hasColumn('quran_sessions', 'google_event_id')) {
                $columnsToDrop[] = 'google_event_id';
            }
            if (Schema::hasColumn('quran_sessions', 'google_calendar_id')) {
                $columnsToDrop[] = 'google_calendar_id';
            }
            if (Schema::hasColumn('quran_sessions', 'attendance_marked_at')) {
                $columnsToDrop[] = 'attendance_marked_at';
            }
            if (Schema::hasColumn('quran_sessions', 'attendance_marked_by')) {
                $columnsToDrop[] = 'attendance_marked_by';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Restore the columns
            $table->string('google_event_id', 255)->nullable()->after('meeting_password');
            $table->string('google_calendar_id', 255)->nullable()->after('google_event_id');
            $table->timestamp('attendance_marked_at')->nullable()->after('participants_count');
            $table->foreignId('attendance_marked_by')
                ->nullable()
                ->after('attendance_marked_at')
                ->constrained('users')
                ->nullOnDelete();

            // Restore the index
            $table->index('google_event_id');
        });
    }
};
