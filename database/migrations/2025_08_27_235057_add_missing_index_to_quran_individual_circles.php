<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the index already exists before creating it
        if (! $this->indexExists('quran_individual_circles', 'qi_circles_meeting_config_idx')) {
            Schema::table('quran_individual_circles', function (Blueprint $table) {
                // Add the missing index with a shorter name
                $table->index(['preparation_minutes', 'ending_buffer_minutes'], 'qi_circles_meeting_config_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the index exists before trying to drop it
        if ($this->indexExists('quran_individual_circles', 'qi_circles_meeting_config_idx')) {
            Schema::table('quran_individual_circles', function (Blueprint $table) {
                $table->dropIndex('qi_circles_meeting_config_idx');
            });
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        $indexes = DB::select('
            SELECT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = ?
        ', [$tableName, $indexName]);

        return count($indexes) > 0;
    }
};
