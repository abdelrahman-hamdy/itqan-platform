<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove unused columns from meeting_attendances table after
     * migrating to pure webhook-based attendance tracking.
     */
    public function up(): void
    {
        Schema::table('meeting_attendances', function (Blueprint $table) {
            // Remove heartbeat column - unused in webhook-based system
            $table->dropColumn('last_heartbeat_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_attendances', function (Blueprint $table) {
            // Restore heartbeat column if rollback needed
            $table->timestamp('last_heartbeat_at')->nullable()->after('last_leave_time');
        });
    }
};
