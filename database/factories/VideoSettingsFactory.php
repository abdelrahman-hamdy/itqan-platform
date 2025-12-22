<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\VideoSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VideoSettings>
 */
class VideoSettingsFactory extends Factory
{
    protected $model = VideoSettings::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'auto_create_meetings' => true,
            'create_meetings_minutes_before' => 15,
            'auto_end_meetings' => true,
            'auto_end_minutes_after' => 15,
            'default_max_participants' => 50,
            'default_video_quality' => 'high',
            'default_audio_quality' => 'high',
            'enable_recording_by_default' => false,
            'enable_screen_sharing' => true,
            'enable_chat' => true,
            'enable_noise_cancellation' => true,
            'default_recording_layout' => 'grid',
            'recording_storage_type' => 'local',
            'auto_cleanup_recordings' => false,
            'cleanup_recordings_after_days' => 30,
            'meeting_theme' => 'light',
            'primary_color' => '#3B82F6',
            'show_participant_count' => true,
            'show_recording_indicator' => true,
            'default_video_resolution' => '720p',
            'default_video_fps' => 30,
            'default_audio_bitrate' => 64,
            'default_video_bitrate' => 1500,
            'notify_on_meeting_start' => true,
            'notify_on_participant_join' => false,
            'notify_on_recording_ready' => true,
            'notification_channels' => ['email'],
            'require_approval_to_join' => false,
            'enable_waiting_room' => false,
            'mute_participants_on_entry' => false,
            'disable_camera_on_entry' => false,
            'integration_enabled' => true,
            'api_rate_limit' => '1000/hour',
            'earliest_meeting_time' => '06:00:00',
            'latest_meeting_time' => '23:00:00',
            'blocked_days' => [],
            'max_daily_meetings' => 20,
            'max_concurrent_meetings' => 5,
            'enable_analytics' => true,
            'track_attendance' => true,
            'generate_reports' => true,
            'keep_analytics_days' => 365,
        ];
    }

    /**
     * Set auto creation disabled
     */
    public function autoCreationDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_create_meetings' => false,
        ]);
    }

    /**
     * Set auto end disabled
     */
    public function autoEndDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_end_meetings' => false,
        ]);
    }

    /**
     * Set integration disabled
     */
    public function integrationDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'integration_enabled' => false,
        ]);
    }

    /**
     * Set recording enabled by default
     */
    public function recordingEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enable_recording_by_default' => true,
        ]);
    }

    /**
     * Set high quality settings
     */
    public function highQuality(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_video_quality' => 'high',
            'default_audio_quality' => 'high',
            'default_video_resolution' => '1080p',
            'default_video_fps' => 60,
        ]);
    }

    /**
     * Set low quality settings
     */
    public function lowQuality(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_video_quality' => 'low',
            'default_audio_quality' => 'low',
            'default_video_resolution' => '480p',
            'default_video_fps' => 15,
        ]);
    }

    /**
     * Set custom meeting creation time
     */
    public function createMinutesBefore(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'create_meetings_minutes_before' => $minutes,
        ]);
    }

    /**
     * Set custom auto end time
     */
    public function endMinutesAfter(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_end_minutes_after' => $minutes,
        ]);
    }

    /**
     * Block specific days
     */
    public function blockDays(array $days): static
    {
        return $this->state(fn (array $attributes) => [
            'blocked_days' => $days,
        ]);
    }

    /**
     * Set time restrictions
     */
    public function timeRestricted(string $earliest, string $latest): static
    {
        return $this->state(fn (array $attributes) => [
            'earliest_meeting_time' => $earliest,
            'latest_meeting_time' => $latest,
        ]);
    }

    /**
     * Enable waiting room
     */
    public function withWaitingRoom(): static
    {
        return $this->state(fn (array $attributes) => [
            'enable_waiting_room' => true,
            'require_approval_to_join' => true,
        ]);
    }

    /**
     * Mute participants on entry
     */
    public function muteOnEntry(): static
    {
        return $this->state(fn (array $attributes) => [
            'mute_participants_on_entry' => true,
            'disable_camera_on_entry' => true,
        ]);
    }
}
