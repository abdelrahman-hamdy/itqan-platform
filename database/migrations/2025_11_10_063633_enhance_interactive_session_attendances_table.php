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
        Schema::table('interactive_session_attendances', function (Blueprint $table) {
            // Auto-tracking fields
            $table->timestamp('auto_join_time')->nullable()->after('leave_time');
            $table->timestamp('auto_leave_time')->nullable()->after('auto_join_time');
            $table->integer('auto_duration_minutes')->nullable()->after('auto_leave_time');
            $table->boolean('auto_tracked')->default(false)->after('attendance_status');

            // Manual override fields
            $table->boolean('manually_overridden')->default(false)->after('auto_tracked');
            $table->foreignId('overridden_by')->nullable()->constrained('users')->onDelete('set null')->after('manually_overridden');
            $table->timestamp('overridden_at')->nullable()->after('overridden_by');
            $table->text('override_reason')->nullable()->after('overridden_at');

            // Enhanced tracking
            $table->json('meeting_events')->nullable()->after('notes')->comment('JSON log of join/leave events');
            $table->integer('connection_quality_score')->nullable()->after('meeting_events')->comment('1-10 scale');

            // Add indexes for performance (with custom names to avoid length issues)
            $table->index(['auto_tracked', 'manually_overridden'], 'idx_int_tracking');
            $table->index(['session_id', 'auto_tracked'], 'idx_int_session_tracking');
            $table->index('overridden_by', 'idx_int_overridden_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interactive_session_attendances', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_int_tracking');
            $table->dropIndex('idx_int_session_tracking');
            $table->dropIndex('idx_int_overridden_by');

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
            ]);
        });
    }
};
