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
            // Add new meeting platform fields
            $table->string('meeting_platform')->default('jitsi')->after('meeting_source'); // jitsi, whereby, custom
            $table->json('meeting_data')->nullable()->after('meeting_platform'); // Platform-specific meeting data
            $table->string('meeting_room_name')->nullable()->after('meeting_data'); // Human-readable room name
            $table->boolean('meeting_auto_generated')->default(true)->after('meeting_room_name');
            $table->timestamp('meeting_expires_at')->nullable()->after('meeting_auto_generated');
            
            // Update existing meeting_source enum to include new options
            $table->enum('meeting_source', ['jitsi', 'whereby', 'custom', 'google', 'platform', 'manual'])->default('jitsi')->change();
            
            // Add indexes for performance
            $table->index(['meeting_platform', 'meeting_source']);
            $table->index(['meeting_expires_at']);
        });

        // Also add to academic sessions if the table exists
        if (Schema::hasTable('academic_sessions')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                $table->string('meeting_platform')->default('jitsi')->after('meeting_url'); 
                $table->json('meeting_data')->nullable()->after('meeting_platform');
                $table->string('meeting_room_name')->nullable()->after('meeting_data');
                $table->boolean('meeting_auto_generated')->default(true)->after('meeting_room_name');
                $table->timestamp('meeting_expires_at')->nullable()->after('meeting_auto_generated');
                
                $table->index(['meeting_platform']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'meeting_platform',
                'meeting_data',
                'meeting_room_name', 
                'meeting_auto_generated',
                'meeting_expires_at',
            ]);
            
            $table->dropIndex(['meeting_platform', 'meeting_source']);
            $table->dropIndex(['meeting_expires_at']);
            
            // Restore original meeting_source enum
            $table->enum('meeting_source', ['google', 'platform', 'manual'])->default('platform')->change();
        });

        if (Schema::hasTable('academic_sessions')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                $table->dropColumn([
                    'meeting_platform',
                    'meeting_data',
                    'meeting_room_name',
                    'meeting_auto_generated',
                    'meeting_expires_at',
                ]);
                
                $table->dropIndex(['meeting_platform']);
            });
        }
    }
};