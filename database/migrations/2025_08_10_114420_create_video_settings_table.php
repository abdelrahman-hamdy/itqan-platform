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
        Schema::create('video_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained('academies')->onDelete('cascade');
            
            // Auto-meeting creation settings
            $table->boolean('auto_create_meetings')->default(true);
            $table->integer('create_meetings_minutes_before')->default(30); // Create meetings 30 minutes before session
            $table->boolean('auto_end_meetings')->default(true);
            $table->integer('auto_end_minutes_after')->default(15); // End meetings 15 minutes after scheduled end
            
            // Default meeting settings
            $table->integer('default_max_participants')->default(50);
            $table->enum('default_video_quality', ['low', 'medium', 'high'])->default('high');
            $table->enum('default_audio_quality', ['low', 'medium', 'high'])->default('high');
            $table->boolean('enable_recording_by_default')->default(false);
            $table->boolean('enable_screen_sharing')->default(true);
            $table->boolean('enable_chat')->default(true);
            $table->boolean('enable_noise_cancellation')->default(true);
            
            // Recording settings
            $table->enum('default_recording_layout', ['grid', 'speaker', 'custom'])->default('grid');
            $table->string('recording_storage_type')->default('local'); // local, s3, gcs
            $table->json('recording_storage_config')->nullable(); // Storage-specific configuration
            $table->boolean('auto_cleanup_recordings')->default(false);
            $table->integer('cleanup_recordings_after_days')->default(30);
            
            // UI customization
            $table->string('meeting_theme')->default('light'); // light, dark, custom
            $table->string('primary_color')->default('#3B82F6');
            $table->string('logo_url')->nullable();
            $table->text('custom_css')->nullable();
            $table->boolean('show_participant_count')->default(true);
            $table->boolean('show_recording_indicator')->default(true);
            
            // Performance settings
            $table->enum('default_video_resolution', ['480p', '720p', '1080p'])->default('720p');
            $table->integer('default_video_fps')->default(30);
            $table->integer('default_audio_bitrate')->default(64); // kbps
            $table->integer('default_video_bitrate')->default(1500); // kbps
            
            // Notification settings
            $table->boolean('notify_on_meeting_start')->default(true);
            $table->boolean('notify_on_participant_join')->default(false);
            $table->boolean('notify_on_recording_ready')->default(true);
            $table->json('notification_channels')->nullable(); // email, sms, push
            
            // Access control
            $table->boolean('require_approval_to_join')->default(false);
            $table->boolean('enable_waiting_room')->default(false);
            $table->boolean('mute_participants_on_entry')->default(false);
            $table->boolean('disable_camera_on_entry')->default(false);
            
            // Integration settings
            $table->boolean('integration_enabled')->default(true);
            $table->json('webhook_endpoints')->nullable(); // Custom webhook URLs
            $table->string('api_rate_limit')->default('1000/hour');
            
            // Scheduling constraints
            $table->time('earliest_meeting_time')->default('06:00:00');
            $table->time('latest_meeting_time')->default('23:00:00');
            $table->json('blocked_days')->nullable(); // Array of blocked weekdays (0-6)
            $table->integer('max_daily_meetings')->default(20);
            $table->integer('max_concurrent_meetings')->default(5);
            
            // Archive and analytics
            $table->boolean('enable_analytics')->default(true);
            $table->boolean('track_attendance')->default(true);
            $table->boolean('generate_reports')->default(true);
            $table->integer('keep_analytics_days')->default(365);
            
            $table->timestamps();
            $table->index(['academy_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_settings');
    }
};