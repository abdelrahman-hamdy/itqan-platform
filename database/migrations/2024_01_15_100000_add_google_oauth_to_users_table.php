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
        Schema::table('users', function (Blueprint $table) {
            // Google OAuth integration fields
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'google_email')) {
                $table->string('google_email')->nullable()->after('google_id');
            }
            if (!Schema::hasColumn('users', 'google_connected_at')) {
                $table->timestamp('google_connected_at')->nullable()->after('google_email');
            }
            if (!Schema::hasColumn('users', 'google_disconnected_at')) {
                $table->timestamp('google_disconnected_at')->nullable()->after('google_connected_at');
            }
            if (!Schema::hasColumn('users', 'google_calendar_enabled')) {
                $table->boolean('google_calendar_enabled')->default(false)->after('google_disconnected_at');
            }
            if (!Schema::hasColumn('users', 'google_permissions')) {
                $table->json('google_permissions')->nullable()->after('google_calendar_enabled');
            }
            
            // Meeting preferences
            if (!Schema::hasColumn('users', 'meeting_preferences')) {
                $table->json('meeting_preferences')->nullable()->after('google_permissions');
            }
            if (!Schema::hasColumn('users', 'auto_create_meetings')) {
                $table->boolean('auto_create_meetings')->default(true)->after('meeting_preferences');
            }
            if (!Schema::hasColumn('users', 'meeting_prep_minutes')) {
                $table->integer('meeting_prep_minutes')->default(60)->after('auto_create_meetings'); // 1 hour before
            }
            
            // Notification preferences
            if (!Schema::hasColumn('users', 'notify_on_google_disconnect')) {
                $table->boolean('notify_on_google_disconnect')->default(true)->after('meeting_prep_minutes');
            }
            if (!Schema::hasColumn('users', 'notify_admin_on_disconnect')) {
                $table->boolean('notify_admin_on_disconnect')->default(true)->after('notify_on_google_disconnect');
            }
            
            // Skip indexes - they might already exist or can be added manually later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_email', 
                'google_connected_at',
                'google_disconnected_at',
                'google_calendar_enabled',
                'google_permissions',
                'meeting_preferences',
                'auto_create_meetings',
                'meeting_prep_minutes',
                'notify_on_google_disconnect',
                'notify_admin_on_disconnect'
            ]);
            // Keep google_id since it might already exist
        });
    }
};