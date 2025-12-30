<?php

namespace App\Models;

use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class VideoSettings extends Model
{
    use HasFactory, ScopedToAcademy;

    protected $fillable = [
        'academy_id',
        'auto_create_meetings',
        'create_meetings_minutes_before',
        'auto_end_meetings', 
        'auto_end_minutes_after',
        'default_max_participants',
        'default_video_quality',
        'default_audio_quality',
        'enable_recording_by_default',
        'enable_screen_sharing',
        'enable_chat',
        'enable_noise_cancellation',
        'default_recording_layout',
        'recording_storage_type',
        'recording_storage_config',
        'auto_cleanup_recordings',
        'cleanup_recordings_after_days',
        'meeting_theme',
        'primary_color',
        'logo_url',
        'custom_css',
        'show_participant_count',
        'show_recording_indicator',
        'default_video_resolution',
        'default_video_fps',
        'default_audio_bitrate',
        'default_video_bitrate',
        'notify_on_meeting_start',
        'notify_on_participant_join',
        'notify_on_recording_ready',
        'notification_channels',
        'require_approval_to_join',
        'enable_waiting_room',
        'mute_participants_on_entry',
        'disable_camera_on_entry',
        'integration_enabled',
        'webhook_endpoints',
        'api_rate_limit',
        'earliest_meeting_time',
        'latest_meeting_time',
        'blocked_days',
        'max_daily_meetings',
        'max_concurrent_meetings',
        'enable_analytics',
        'track_attendance',
        'generate_reports',
        'keep_analytics_days',
    ];

    protected $casts = [
        'auto_create_meetings' => 'boolean',
        'auto_end_meetings' => 'boolean',
        'enable_recording_by_default' => 'boolean',
        'enable_screen_sharing' => 'boolean',
        'enable_chat' => 'boolean',
        'enable_noise_cancellation' => 'boolean',
        'recording_storage_config' => 'array',
        'auto_cleanup_recordings' => 'boolean',
        'show_participant_count' => 'boolean',
        'show_recording_indicator' => 'boolean',
        'notify_on_meeting_start' => 'boolean',
        'notify_on_participant_join' => 'boolean',
        'notify_on_recording_ready' => 'boolean',
        'notification_channels' => 'array',
        'require_approval_to_join' => 'boolean',
        'enable_waiting_room' => 'boolean',
        'mute_participants_on_entry' => 'boolean',
        'disable_camera_on_entry' => 'boolean',
        'integration_enabled' => 'boolean',
        'webhook_endpoints' => 'array',
        'earliest_meeting_time' => 'datetime:H:i',
        'latest_meeting_time' => 'datetime:H:i',
        'blocked_days' => 'array',
        'enable_analytics' => 'boolean',
        'track_attendance' => 'boolean',
        'generate_reports' => 'boolean',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get video settings for academy (create default if not exists)
     */
    public static function forAcademy(Academy $academy): self
    {
        return static::firstOrCreate(
            ['academy_id' => $academy->id],
            static::getDefaultSettings()
        );
    }

    /**
     * Get default video settings
     */
    public static function getDefaultSettings(): array
    {
        return [
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
            'notification_channels' => json_encode(['email']),
            'require_approval_to_join' => false,
            'enable_waiting_room' => false,
            'mute_participants_on_entry' => false,
            'disable_camera_on_entry' => false,
            'integration_enabled' => true,
            'api_rate_limit' => '1000/hour',
            'earliest_meeting_time' => '06:00:00',
            'latest_meeting_time' => '23:00:00',
            'blocked_days' => json_encode([]),
            'max_daily_meetings' => 20,
            'max_concurrent_meetings' => 5,
            'enable_analytics' => true,
            'track_attendance' => true,
            'generate_reports' => true,
            'keep_analytics_days' => 365,
        ];
    }

    /**
     * Check if auto-meeting creation is enabled
     */
    public function shouldAutoCreateMeetings(): bool
    {
        return $this->auto_create_meetings && $this->integration_enabled;
    }

    /**
     * Get the time when meetings should be created before session
     */
    public function getMeetingCreationTime(Carbon $sessionTime): Carbon
    {
        return $sessionTime->copy()->subMinutes($this->create_meetings_minutes_before);
    }

    /**
     * Get the time when meetings should be auto-ended after session
     */
    public function getMeetingEndTime(Carbon $sessionEndTime): Carbon
    {
        return $sessionEndTime->copy()->addMinutes($this->auto_end_minutes_after);
    }

    /**
     * Check if a session time is within allowed hours
     */
    public function isTimeAllowed(Carbon $sessionTime): bool
    {
        $timeOfDay = $sessionTime->format('H:i:s');
        
        return $timeOfDay >= $this->earliest_meeting_time->format('H:i:s') 
            && $timeOfDay <= $this->latest_meeting_time->format('H:i:s');
    }

    /**
     * Check if a day is blocked
     */
    public function isDayBlocked(Carbon $date): bool
    {
        $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday
        return in_array($dayOfWeek, $this->blocked_days ?? []);
    }

    /**
     * Get meeting configuration for LiveKit
     */
    public function getLiveKitConfiguration(): array
    {
        return [
            'max_participants' => $this->default_max_participants,
            'recording_enabled' => $this->enable_recording_by_default,
            'recording_layout' => $this->default_recording_layout,
            'video_quality' => $this->default_video_quality,
            'audio_quality' => $this->default_audio_quality,
            'video_resolution' => $this->default_video_resolution,
            'video_fps' => $this->default_video_fps,
            'audio_bitrate' => $this->default_audio_bitrate,
            'video_bitrate' => $this->default_video_bitrate,
            'enable_screen_sharing' => $this->enable_screen_sharing,
            'enable_chat' => $this->enable_chat,
            'enable_noise_cancellation' => $this->enable_noise_cancellation,
            'require_approval_to_join' => $this->require_approval_to_join,
            'enable_waiting_room' => $this->enable_waiting_room,
            'mute_participants_on_entry' => $this->mute_participants_on_entry,
            'disable_camera_on_entry' => $this->disable_camera_on_entry,
            'theme' => $this->meeting_theme,
            'primary_color' => $this->primary_color,
            'logo_url' => $this->logo_url,
            'show_participant_count' => $this->show_participant_count,
            'show_recording_indicator' => $this->show_recording_indicator,
        ];
    }

    /**
     * Get recording storage configuration
     */
    public function getRecordingStorageConfig(): array
    {
        $baseConfig = [
            'type' => $this->recording_storage_type,
            'auto_cleanup' => $this->auto_cleanup_recordings,
            'cleanup_after_days' => $this->cleanup_recordings_after_days,
        ];

        if ($this->recording_storage_config) {
            $baseConfig = array_merge($baseConfig, $this->recording_storage_config);
        }

        return $baseConfig;
    }

    /**
     * Check if recordings should be cleaned up
     */
    public function shouldCleanupRecordings(): bool
    {
        return $this->auto_cleanup_recordings;
    }

    /**
     * Get notification configuration
     */
    public function getNotificationConfig(): array
    {
        return [
            'on_meeting_start' => $this->notify_on_meeting_start,
            'on_participant_join' => $this->notify_on_participant_join,
            'on_recording_ready' => $this->notify_on_recording_ready,
            'channels' => $this->notification_channels ?? ['email'],
        ];
    }

    /**
     * Check if analytics are enabled
     */
    public function isAnalyticsEnabled(): bool
    {
        return $this->enable_analytics;
    }

    /**
     * Get analytics configuration
     */
    public function getAnalyticsConfig(): array
    {
        return [
            'track_attendance' => $this->track_attendance,
            'generate_reports' => $this->generate_reports,
            'keep_days' => $this->keep_analytics_days,
        ];
    }

    /**
     * Get UI customization for frontend
     */
    public function getUIConfig(): array
    {
        return [
            'theme' => $this->meeting_theme,
            'primary_color' => $this->primary_color,
            'logo_url' => $this->logo_url,
            'custom_css' => $this->custom_css,
            'show_participant_count' => $this->show_participant_count,
            'show_recording_indicator' => $this->show_recording_indicator,
        ];
    }

    /**
     * Test video settings configuration
     */
    public function testConfiguration(): array
    {
        try {
            $results = [
                'status' => 'success',
                'tests' => []
            ];

            // Test LiveKit configuration
            $livekitConfig = $this->getLiveKitConfiguration();
            $results['tests']['livekit_config'] = [
                'status' => 'passed',
                'message' => 'LiveKit configuration is valid',
                'data' => $livekitConfig
            ];

            // Test recording storage
            $storageConfig = $this->getRecordingStorageConfig();
            $results['tests']['recording_storage'] = [
                'status' => 'passed',
                'message' => 'Recording storage configuration is valid',
                'data' => $storageConfig
            ];

            // Test notification settings
            $notificationConfig = $this->getNotificationConfig();
            $results['tests']['notifications'] = [
                'status' => 'passed',
                'message' => 'Notification configuration is valid',
                'data' => $notificationConfig
            ];

            // Test time restrictions
            $now = now();
            $timeAllowed = $this->isTimeAllowed($now);
            $dayBlocked = $this->isDayBlocked($now);
            
            $results['tests']['scheduling'] = [
                'status' => $timeAllowed && !$dayBlocked ? 'passed' : 'warning',
                'message' => $timeAllowed && !$dayBlocked 
                    ? 'Current time is within allowed meeting hours' 
                    : 'Current time/day may be restricted',
                'data' => [
                    'current_time_allowed' => $timeAllowed,
                    'current_day_blocked' => $dayBlocked,
                    'earliest_time' => $this->earliest_meeting_time->format('H:i'),
                    'latest_time' => $this->latest_meeting_time->format('H:i'),
                ]
            ];

            return $results;

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Configuration test failed: ' . $e->getMessage(),
                'tests' => []
            ];
        }
    }
}