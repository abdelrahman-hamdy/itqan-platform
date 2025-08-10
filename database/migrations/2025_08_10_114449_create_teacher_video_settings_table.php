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
        Schema::create('teacher_video_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('academy_id')->constrained('academies')->onDelete('cascade');
            
            // Personal preferences (can override academy defaults)
            $table->integer('preferred_max_participants')->nullable(); // null = use academy default
            $table->enum('preferred_video_quality', ['low', 'medium', 'high'])->nullable();
            $table->enum('preferred_audio_quality', ['low', 'medium', 'high'])->nullable();
            $table->enum('preferred_video_resolution', ['480p', '720p', '1080p'])->nullable();
            
            // Meeting behavior preferences
            $table->boolean('auto_start_recording')->nullable(); // null = use academy default
            $table->enum('preferred_recording_layout', ['grid', 'speaker', 'custom'])->nullable();
            $table->boolean('mute_participants_on_join')->nullable();
            $table->boolean('disable_camera_on_join')->nullable();
            $table->boolean('enable_waiting_room')->nullable();
            $table->boolean('enable_screen_sharing')->nullable();
            $table->boolean('enable_chat')->nullable();
            
            // UI preferences
            $table->string('preferred_theme')->nullable(); // light, dark, custom
            $table->string('custom_background_url')->nullable(); // Teacher's virtual background
            $table->boolean('show_participant_names')->default(true);
            $table->boolean('show_time_remaining')->default(true);
            
            // Notification preferences
            $table->boolean('notify_before_session')->default(true);
            $table->integer('notification_minutes_before')->default(15);
            $table->boolean('notify_on_late_student')->default(true);
            $table->boolean('notify_on_session_end')->default(false);
            $table->json('notification_methods')->nullable(); // email, sms, push
            
            // Personal meeting scheduling
            $table->time('preferred_earliest_time')->nullable(); // Can be more restrictive than academy
            $table->time('preferred_latest_time')->nullable();
            $table->json('unavailable_days')->nullable(); // Additional blocked days
            $table->integer('break_minutes_between_sessions')->default(5);
            
            // Teaching preferences
            $table->boolean('allow_student_screen_sharing')->default(false);
            $table->boolean('allow_student_unmute')->default(true);
            $table->boolean('allow_student_camera')->default(true);
            $table->boolean('auto_admit_known_students')->default(true);
            
            // Recording preferences
            $table->boolean('always_record_sessions')->default(false);
            $table->boolean('save_recordings_locally')->default(false);
            $table->string('recording_quality_preference')->default('standard'); // standard, high, ultra
            $table->boolean('include_chat_in_recording')->default(true);
            
            // Analytics preferences
            $table->boolean('track_student_attendance')->default(true);
            $table->boolean('track_session_engagement')->default(true);
            $table->boolean('generate_session_reports')->default(true);
            $table->boolean('share_reports_with_parents')->default(false);
            
            // Advanced features
            $table->json('custom_meeting_templates')->nullable(); // Pre-saved meeting configurations
            $table->boolean('enable_breakout_rooms')->default(false);
            $table->boolean('enable_whiteboard')->default(false);
            $table->json('keyboard_shortcuts')->nullable(); // Custom shortcuts
            
            // Performance optimizations
            $table->boolean('adaptive_bitrate')->default(true);
            $table->boolean('echo_cancellation')->default(true);
            $table->boolean('noise_suppression')->default(true);
            $table->integer('max_video_participants')->default(4); // How many video feeds to show simultaneously
            
            $table->timestamps();
            $table->unique(['user_id', 'academy_id']);
            $table->index(['user_id', 'academy_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_video_settings');
    }
};