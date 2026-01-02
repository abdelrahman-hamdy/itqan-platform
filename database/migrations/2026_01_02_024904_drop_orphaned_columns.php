<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop orphaned database columns that are no longer used in the application.
 *
 * Identified during SuperAdmin Filament dashboard audit:
 * - teacher_monthly_revenue: Added to quran_circles but never implemented/used
 * - messenger_color: Deprecated chat feature field in users table
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop unused teacher_monthly_revenue from quran_circles
        if (Schema::hasColumn('quran_circles', 'teacher_monthly_revenue')) {
            Schema::table('quran_circles', function (Blueprint $table) {
                $table->dropColumn('teacher_monthly_revenue');
            });
        }

        // Drop deprecated messenger_color from users
        if (Schema::hasColumn('users', 'messenger_color')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('messenger_color');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore teacher_monthly_revenue to quran_circles
        if (!Schema::hasColumn('quran_circles', 'teacher_monthly_revenue')) {
            Schema::table('quran_circles', function (Blueprint $table) {
                $table->decimal('teacher_monthly_revenue', 8, 2)->nullable()->after('teacher_percentage');
            });
        }

        // Restore messenger_color to users
        if (!Schema::hasColumn('users', 'messenger_color')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('messenger_color')->nullable()->after('chat_settings');
            });
        }
    }
};
