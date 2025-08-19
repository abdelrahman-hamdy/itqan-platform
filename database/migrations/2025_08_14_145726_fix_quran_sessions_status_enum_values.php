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
        // First, update any existing 'template' statuses to 'unscheduled'
        DB::table('quran_sessions')
            ->where('status', 'template')
            ->update(['status' => 'pending']); // Temporarily set to 'pending' which exists
            
        // Drop the existing enum and recreate with correct values
        DB::statement("ALTER TABLE quran_sessions MODIFY COLUMN status ENUM('unscheduled','scheduled','ready','ongoing','completed','cancelled','absent','missed','rescheduled') NOT NULL DEFAULT 'unscheduled'");
        
        // Now update the 'pending' records to 'unscheduled'
        DB::table('quran_sessions')
            ->where('status', 'pending')
            ->update(['status' => 'unscheduled']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to old enum values
        DB::table('quran_sessions')
            ->where('status', 'unscheduled')
            ->update(['status' => 'pending']); // Temporarily set to 'pending'
            
        DB::table('quran_sessions')
            ->where('status', 'ready')
            ->update(['status' => 'scheduled']); // Convert 'ready' back to 'scheduled'
            
        DB::table('quran_sessions')
            ->where('status', 'absent')
            ->update(['status' => 'cancelled']); // Convert 'absent' back to 'cancelled'
            
        // Restore old enum
        DB::statement("ALTER TABLE quran_sessions MODIFY COLUMN status ENUM('template','scheduled','ongoing','completed','cancelled','missed','rescheduled','pending') NOT NULL DEFAULT 'template'");
        
        // Convert 'pending' back to 'template'
        DB::table('quran_sessions')
            ->where('status', 'pending')
            ->update(['status' => 'template']);
    }
};