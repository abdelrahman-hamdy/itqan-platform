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
        Schema::table('quran_session_homeworks', function (Blueprint $table) {
            $table->dropColumn(['new_memorization_notes', 'review_notes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_session_homeworks', function (Blueprint $table) {
            $table->text('new_memorization_notes')->nullable();
            $table->text('review_notes')->nullable();
        });
    }
};
