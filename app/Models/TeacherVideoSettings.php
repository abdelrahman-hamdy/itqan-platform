<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TeacherVideoSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'academy_id', 
        'preferred_max_participants',
        'preferred_video_quality',
        'preferred_audio_quality',
        'preferred_video_resolution',
        'auto_start_recording',
        'preferred_recording_layout',
        'mute_participants_on_join',
        'disable_camera_on_join',
        'enable_waiting_room',
        'enable_screen_sharing',
        'enable_chat',
        'preferred_theme',
        'custom_background_url',
        'show_participant_names',
        'show_time_remaining',
        'notify_before_session',
        'notification_minutes_before',
        'notify_on_late_student',
        'notify_on_session_end',
        'notification_methods',
        'preferred_earliest_time',
        'preferred_latest_time',
        'unavailable_days',
        'break_minutes_between_sessions',
        'allow_student_screen_sharing',
        'allow_student_unmute',
        'allow_student_camera',
        'auto_admit_known_students',
        'always_record_sessions',
        'save_recordings_locally',
        'recording_quality_preference',
        'include_chat_in_recording',
        'track_student_attendance',
        'track_session_engagement',
        'generate_session_reports',
        'share_reports_with_parents'
    ];

    protected $casts = [
        'auto_start_recording' => 'boolean',
        'mute_participants_on_join' => 'boolean',
        'disable_camera_on_join' => 'boolean',
        'enable_waiting_room' => 'boolean',
        'enable_screen_sharing' => 'boolean',
        'enable_chat' => 'boolean',
        'show_participant_names' => 'boolean',
        'show_time_remaining' => 'boolean',
        'notify_before_session' => 'boolean',
        'notify_on_late_student' => 'boolean',
        'notify_on_session_end' => 'boolean',
        'notification_methods' => 'array',
        'preferred_earliest_time' => 'datetime:H:i',
        'preferred_latest_time' => 'datetime:H:i',
        'unavailable_days' => 'array',
        'allow_student_screen_sharing' => 'boolean',
        'allow_student_unmute' => 'boolean',
        'allow_student_camera' => 'boolean',
        'auto_admit_known_students' => 'boolean',
        'always_record_sessions' => 'boolean',
        'save_recordings_locally' => 'boolean',
        'include_chat_in_recording' => 'boolean',
        'track_student_attendance' => 'boolean',
        'track_session_engagement' => 'boolean',
        'generate_session_reports' => 'boolean',
        'share_reports_with_parents' => 'boolean'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get teacher settings for user in academy
     */
    public static function forTeacher(User $user, Academy $academy): self
    {
        return static::firstOrCreate([
            'user_id' => $user->id,
            'academy_id' => $academy->id,
        ], [
            'show_participant_names' => true,
            'show_time_remaining' => true,
            'notify_before_session' => true,
            'notification_minutes_before' => 15,
            'notify_on_late_student' => true,
            'notification_methods' => ['email'],
            'break_minutes_between_sessions' => 5,
            'allow_student_unmute' => true,
            'allow_student_camera' => true,
            'auto_admit_known_students' => true,
            'track_student_attendance' => true,
            'generate_session_reports' => true,
        ]);
    }

    /**
     * Get effective setting (teacher preference or academy default)
     */
    public function getEffectiveSetting(string $setting, VideoSettings $academySettings, $default = null)
    {
        if ($this->$setting !== null) {
            return $this->$setting;
        }

        $academyMapping = [
            'preferred_max_participants' => 'default_max_participants',
            'preferred_video_quality' => 'default_video_quality',
            'preferred_audio_quality' => 'default_audio_quality',
            'auto_start_recording' => 'enable_recording_by_default',
            'enable_screen_sharing' => 'enable_screen_sharing',
            'enable_chat' => 'enable_chat',
        ];

        if (isset($academyMapping[$setting])) {
            return $academySettings->{$academyMapping[$setting]} ?? $default;
        }

        return $default;
    }

    /**
     * Get meeting configuration with teacher preferences
     */
    public function getMeetingConfiguration(VideoSettings $academySettings): array
    {
        return [
            'max_participants' => $this->getEffectiveSetting('preferred_max_participants', $academySettings, 50),
            'video_quality' => $this->getEffectiveSetting('preferred_video_quality', $academySettings, 'high'),
            'recording_enabled' => $this->getEffectiveSetting('auto_start_recording', $academySettings, false),
            'enable_screen_sharing' => $this->getEffectiveSetting('enable_screen_sharing', $academySettings, true),
            'enable_chat' => $this->getEffectiveSetting('enable_chat', $academySettings, true),
            'mute_on_join' => $this->mute_participants_on_join ?? false,
            'theme' => $this->preferred_theme ?? $academySettings->meeting_theme,
        ];
    }

    /**
     * Check if teacher is available at given time
     */
    public function isAvailableAt(Carbon $dateTime): bool
    {
        $dayOfWeek = $dateTime->dayOfWeek;
        if (in_array($dayOfWeek, $this->unavailable_days ?? [])) {
            return false;
        }

        $timeOfDay = $dateTime->format('H:i:s');
        
        if ($this->preferred_earliest_time && $timeOfDay < $this->preferred_earliest_time->format('H:i:s')) {
            return false;
        }

        if ($this->preferred_latest_time && $timeOfDay > $this->preferred_latest_time->format('H:i:s')) {
            return false;
        }

        return true;
    }
}