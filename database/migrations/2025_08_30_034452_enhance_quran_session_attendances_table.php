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
        Schema::table('quran_session_attendances', function (Blueprint $table) {
            // Auto-tracking fields
            $table->timestamp('auto_join_time')->nullable()->after('join_time');
            $table->timestamp('auto_leave_time')->nullable()->after('leave_time');
            $table->integer('auto_duration_minutes')->nullable()->after('auto_leave_time');
            $table->boolean('auto_tracked')->default(false)->after('attendance_status');

            // Manual override fields
            $table->boolean('manually_overridden')->default(false)->after('auto_tracked');
            $table->foreignId('overridden_by')->nullable()->constrained('users')->onDelete('set null')->after('manually_overridden');
            $table->timestamp('overridden_at')->nullable()->after('overridden_by');
            $table->text('override_reason')->nullable()->after('overridden_at');

            // Enhanced tracking
            $table->json('meeting_events')->nullable()->after('notes'); // JSON log of join/leave events
            $table->integer('connection_quality_score')->nullable()->after('meeting_events'); // 1-10

            // Homework completion link (pages-based)
            $table->decimal('pages_memorized_today', 4, 2)->nullable()->after('verses_memorized_today');
            $table->decimal('pages_reviewed_today', 4, 2)->nullable()->after('pages_memorized_today');

            // Add indexes for performance
            $table->index(['auto_tracked', 'manually_overridden']);
            $table->index(['session_id', 'auto_tracked']);
            $table->index('overridden_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_session_attendances', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['auto_tracked', 'manually_overridden']);
            $table->dropIndex(['session_id', 'auto_tracked']);
            $table->dropIndex(['overridden_by']);

            // Drop foreign key
            $table->dropForeign(['overridden_by']);

            // Drop columns
            $table->dropColumn([
                'auto_join_time',
                'auto_leave_time',
                'auto_duration_minutes',
                'auto_tracked',
                'manually_overridden',
                'overridden_by',
                'overridden_at',
                'override_reason',
                'meeting_events',
                'connection_quality_score',
                'pages_memorized_today',
                'pages_reviewed_today',
            ]);
        });
    }
};
