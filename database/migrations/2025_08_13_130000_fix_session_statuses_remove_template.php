<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update all sessions with 'template' status to 'unscheduled'
        DB::table('quran_sessions')
            ->where('status', 'template')
            ->update(['status' => 'unscheduled']);

        // Update the enum to remove 'template' and add new statuses
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->enum('status', [
                'unscheduled',         // Created but not scheduled
                'scheduled',           // Teacher has set date/time
                'ready',               // Meeting created, ready to start
                'ongoing',             // Currently happening
                'completed',           // Finished successfully
                'cancelled',           // Cancelled by teacher/admin
                'absent',              // Student didn't attend (individual only)
                'missed',              // System marked as missed due to time
                'rescheduled',         // Moved to different time
            ])->default('unscheduled')->change();
        });

        // Update any sessions that might have invalid statuses
        DB::table('quran_sessions')
            ->where('status', 'pending')
            ->update(['status' => 'scheduled']);
            
        // Set proper status for sessions that have scheduled_at but are marked as unscheduled
        DB::statement("
            UPDATE quran_sessions 
            SET status = 'scheduled' 
            WHERE status = 'unscheduled' 
            AND scheduled_at IS NOT NULL 
            AND scheduled_at > NOW()
        ");

        // Set sessions as completed if they have ended_at
        DB::statement("
            UPDATE quran_sessions 
            SET status = 'completed' 
            WHERE ended_at IS NOT NULL 
            AND status IN ('unscheduled', 'scheduled')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->enum('status', [
                'template',            // Created but not scheduled (old approach)
                'unscheduled',         // Created but not scheduled (new approach)
                'scheduled',           // Teacher has set date/time
                'ongoing',             // Currently happening
                'completed',           // Finished
                'cancelled',           // Cancelled
                'missed',              // Student didn't attend
                'rescheduled',         // Moved to different time
                'pending'              // Awaiting confirmation
            ])->default('template')->change();
        });
    }
};
