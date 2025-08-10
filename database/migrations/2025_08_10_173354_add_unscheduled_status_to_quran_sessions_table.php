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
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Update status enum to include 'unscheduled'
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
            ])->default('unscheduled')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Restore original status enum
            $table->enum('status', [
                'template',            // Created but not scheduled (individual circles)
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