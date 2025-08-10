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
        // First, update any existing 'basic' values to 'elementary'
        DB::table('quran_trial_requests')
            ->where('current_level', 'basic')
            ->update(['current_level' => 'elementary']);

        // Then alter the enum to include the correct values
        DB::statement("ALTER TABLE quran_trial_requests MODIFY COLUMN current_level ENUM('beginner','elementary','intermediate','advanced','expert','hafiz') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the original enum values
        DB::statement("ALTER TABLE quran_trial_requests MODIFY COLUMN current_level ENUM('beginner','basic','intermediate','advanced','expert') NOT NULL");
        
        // Update any 'elementary' values back to 'basic'
        DB::table('quran_trial_requests')
            ->where('current_level', 'elementary')
            ->update(['current_level' => 'basic']);
    }
};