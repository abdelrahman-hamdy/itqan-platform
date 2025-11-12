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
        // Remove session_duration_minutes from quran_circles table if it exists
        if (Schema::hasTable('quran_circles') && Schema::hasColumn('quran_circles', 'session_duration_minutes')) {
            Schema::table('quran_circles', function (Blueprint $table) {
                $table->dropColumn('session_duration_minutes');
            });
        }

        // Remove any meeting-related settings from quran_circles if they exist
        $meetingColumns = [
            'meeting_preparation_minutes',
            'meeting_buffer_minutes',
            'late_tolerance_minutes'
        ];

        foreach ($meetingColumns as $column) {
            if (Schema::hasTable('quran_circles') && Schema::hasColumn('quran_circles', $column)) {
                Schema::table('quran_circles', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the columns if needed (for rollback)
        Schema::table('quran_circles', function (Blueprint $table) {
            if (!Schema::hasColumn('quran_circles', 'session_duration_minutes')) {
                $table->integer('session_duration_minutes')->default(60)->after('monthly_sessions_count');
            }
        });
    }
};