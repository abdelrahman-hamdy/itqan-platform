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
        Schema::table('meeting_attendances', function (Blueprint $table) {
            // First, modify the session_type enum to include 'academic'
            DB::statement("ALTER TABLE meeting_attendances MODIFY COLUMN session_type ENUM('individual', 'group', 'academic') DEFAULT 'individual'");
            
            // Remove the foreign key constraint that only allows quran_sessions
            $table->dropForeign(['session_id']);
            
            // Add new index for session_id without foreign key constraint
            // This allows academic sessions to use the same table
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_attendances', function (Blueprint $table) {
            // Remove the new index
            $table->dropIndex(['session_id']);
            
            // Restore the original foreign key constraint (only for quran_sessions)
            $table->foreign('session_id')->references('id')->on('quran_sessions')->onDelete('cascade');
            
            // Revert the session_type enum to original values
            DB::statement("ALTER TABLE meeting_attendances MODIFY COLUMN session_type ENUM('individual', 'group') DEFAULT 'individual'");
        });
    }
};