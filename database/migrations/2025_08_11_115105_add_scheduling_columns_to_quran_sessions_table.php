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
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('quran_sessions', 'is_template')) {
                $table->boolean('is_template')->default(false)->after('status');
            }
            if (!Schema::hasColumn('quran_sessions', 'is_scheduled')) {
                $table->boolean('is_scheduled')->default(false)->after('is_template');
            }
            if (!Schema::hasColumn('quran_sessions', 'teacher_scheduled_at')) {
                $table->datetime('teacher_scheduled_at')->nullable()->after('is_scheduled');
            }
            if (!Schema::hasColumn('quran_sessions', 'scheduled_by')) {
                $table->foreignId('scheduled_by')->nullable()->constrained('users')->onDelete('set null')->after('teacher_scheduled_at');
            }
            if (!Schema::hasColumn('quran_sessions', 'session_sequence')) {
                $table->integer('session_sequence')->nullable()->after('scheduled_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('quran_sessions', 'scheduled_by')) {
                $table->dropForeign(['scheduled_by']);
                $table->dropColumn('scheduled_by');
            }
            if (Schema::hasColumn('quran_sessions', 'teacher_scheduled_at')) {
                $table->dropColumn('teacher_scheduled_at');
            }
            if (Schema::hasColumn('quran_sessions', 'is_scheduled')) {
                $table->dropColumn('is_scheduled');
            }
            if (Schema::hasColumn('quran_sessions', 'is_template')) {
                $table->dropColumn('is_template');
            }
            if (Schema::hasColumn('quran_sessions', 'session_sequence')) {
                $table->dropColumn('session_sequence');
            }
        });
    }
};