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
        Schema::table('quran_circles', function (Blueprint $table) {
            // Add learning_objectives column without specifying 'after' since admin_notes might not exist
            if (! Schema::hasColumn('quran_circles', 'learning_objectives')) {
                $table->json('learning_objectives')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_circles', function (Blueprint $table) {
            if (Schema::hasColumn('quran_circles', 'learning_objectives')) {
                $table->dropColumn('learning_objectives');
            }
        });
    }
};
