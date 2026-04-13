<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add orchestration columns to session_recordings
        Schema::table('session_recordings', function (Blueprint $table) {
            $table->boolean('auto_managed')->default(false)->after('metadata');
            $table->timestamp('queued_at')->nullable()->after('auto_managed');
            $table->string('skipped_reason')->nullable()->after('queued_at');
        });

        // Add recording permission columns to supervisor_profiles
        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->boolean('can_manage_recording')->default(false)->after('can_monitor_sessions');
            $table->json('recording_session_types')->nullable()->after('can_manage_recording');
        });

        // Migrate existing can_monitor_sessions data to can_manage_recording
        DB::table('supervisor_profiles')
            ->where('can_monitor_sessions', true)
            ->update([
                'can_manage_recording' => true,
                'recording_session_types' => json_encode([
                    'quran_individual',
                    'quran_group',
                    'academic_lesson',
                    'interactive_course',
                    'trial',
                ]),
            ]);
    }

    public function down(): void
    {
        Schema::table('session_recordings', function (Blueprint $table) {
            $table->dropColumn(['auto_managed', 'queued_at', 'skipped_reason']);
        });

        Schema::table('supervisor_profiles', function (Blueprint $table) {
            $table->dropColumn(['can_manage_recording', 'recording_session_types']);
        });
    }
};
