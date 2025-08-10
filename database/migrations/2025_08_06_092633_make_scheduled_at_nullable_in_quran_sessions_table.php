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
            // Make scheduled_at nullable to support template sessions
            $table->timestamp('scheduled_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Revert scheduled_at to not nullable (but this could cause data loss)
            $table->timestamp('scheduled_at')->nullable(false)->change();
        });
    }
};