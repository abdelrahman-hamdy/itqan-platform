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
        Schema::table('quran_teachers', function (Blueprint $table) {
            // Drop the old unique constraint that referenced user_id
            $table->dropUnique(['academy_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot recreate the constraint since user_id column no longer exists
        // This migration is irreversible
    }
};
