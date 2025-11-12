<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove Google OAuth and integration fields from users table.
     * Google authentication/services not implemented and not planned.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'google_email',
                'google_connected_at',
                'google_disconnected_at',
                'google_calendar_enabled',
                'google_permissions',
                'notify_on_google_disconnect',
                'notify_admin_on_disconnect',
                'sync_to_google_calendar',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('email');
            $table->string('google_email')->nullable()->after('google_id');
            $table->timestamp('google_connected_at')->nullable()->after('google_email');
            $table->timestamp('google_disconnected_at')->nullable()->after('google_connected_at');
            $table->boolean('google_calendar_enabled')->default(false)->after('google_disconnected_at');
            $table->json('google_permissions')->nullable()->after('notification_method');
            $table->boolean('notify_on_google_disconnect')->default(true)->after('meeting_prep_minutes');
            $table->boolean('notify_admin_on_disconnect')->default(true)->after('notify_on_google_disconnect');
            $table->boolean('sync_to_google_calendar')->default(true)->after('teacher_reminder_times');
        });
    }
};
