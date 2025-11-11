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
        Schema::table('recorded_courses', function (Blueprint $table) {
            // Remove the duplicate level field - we'll keep difficulty_level as the primary level indicator
            $table->dropColumn('level');

            // Drop any indexes that might reference the level column
            // The schema shows there's an index on (category, level)
            $table->dropIndex(['category', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recorded_courses', function (Blueprint $table) {
            // Add back the level field
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner')->after('trailer_video_url');

            // Recreate the index
            $table->index(['category', 'level']);
        });
    }
};
