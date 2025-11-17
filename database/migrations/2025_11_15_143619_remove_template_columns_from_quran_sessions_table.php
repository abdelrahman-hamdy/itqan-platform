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
            // Drop template system columns
            if (Schema::hasColumn('quran_sessions', 'is_template')) {
                $table->dropColumn('is_template');
            }
            if (Schema::hasColumn('quran_sessions', 'is_scheduled')) {
                $table->dropColumn('is_scheduled');
            }
            if (Schema::hasColumn('quran_sessions', 'session_sequence')) {
                $table->dropColumn('session_sequence');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Restore template system columns
            $table->boolean('is_template')->default(false)->after('session_type');
            $table->boolean('is_scheduled')->default(false)->after('is_template');
            $table->integer('session_sequence')->nullable()->after('session_code');
        });
    }
};
