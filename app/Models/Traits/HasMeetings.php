<?php

namespace App\Models\Traits;

use App\Exceptions\MeetingException;
use App\Models\Academy;
use App\Models\User;
use App\Services\LiveKitService;
use Illuminate\Support\Facades\Log;

/**
 * Trait for models that support LiveKit meetings
 *
 * Provides common meeting functionality that can be shared across
 * different session types (QuranSession, AcademicSession, etc.)
 */
trait HasMeetings
{
    /**
     * Ensure meeting exists for ready sessions (automatic fallback)
     */
    public function ensureMeetingExists(): bool
    {
        // Only create meeting for ready/ongoing sessions that don't have one
        if (! in_array($this->status->value, ['ready', 'ongoing']) || $this->meeting_room_name) {
            return true; // Already has meeting or doesn't need one yet
        }

        try {
            Log::info('Auto-creating missing meeting for ready session', [
                'session_id' => $this->id,
                'session_type' => $this->getMeetingSessionType(),
                'status' => $this->status->value,
            ]);

            $meetingConfig = $this->getMeetingConfiguration();
            $this->generateMeetingLink([
                'max_participants' => $meetingConfig['max_participants'],
                'recording_enabled' => $meetingConfig['recording_enabled'],
            ]);

            Log::info('Meeting auto-created successfully', [
                'session_id' => $this->id,
                'room_name' => $this->meeting_room_name,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to auto-create meeting for ready session', [
                'session_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate a meeting link for this session
     */
    public function generateMeetingLink(array $options = []): string
    {
        try {
            $liveKitService = app(LiveKitService::class);
            $academy = $this->getAcademy();

            if (! $academy) {
                throw MeetingException::academyRequired();
            }

            // Create meeting through LiveKit service
            $meetingData = $liveKitService->createMeeting(
                $academy,
                $this->getMeetingSessionType(),
                $this->id,
                $this->getMeetingStartTime(),
                array_merge([
                    'max_participants' => 50,
                    'recording_enabled' => false,
                    'max_duration' => $this->getMeetingDurationMinutes(),
                ], $options)
            );

            // Update session with meeting details
            $this->update([
                'meeting_room_name' => $meetingData['room_name'],
                'meeting_link' => $meetingData['meeting_url'],
                'meeting_id' => $meetingData['meeting_id'],
                'meeting_created_at' => now(),
            ]);

            // 🔥 FIX: Create student reports when meeting room is created
            if (method_exists($this, 'initializeStudentReports')) {
                $this->initializeStudentReports();
                Log::info('Student reports initialized for session', [
                    'session_type' => $this->getMeetingSessionType(),
                    'session_id' => $this->id,
                ]);
            }

            Log::info('Meeting created successfully', [
                'session_type' => $this->getMeetingSessionType(),
                'session_id' => $this->id,
                'room_name' => $meetingData['room_name'],
                'academy_id' => $academy->id,
            ]);

            return $meetingData['meeting_url'];

        } catch (\Exception $e) {
            Log::error('Failed to generate meeting link', [
                'session_type' => $this->getMeetingSessionType(),
                'session_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            throw MeetingException::creationFailed($e->getMessage());
        }
    }

    /**
     * Generate a participant access token for joining the meeting
     */
    public function generateParticipantToken(User $user, array $permissions = []): string
    {
        try {
            if (! $this->meeting_room_name) {
                throw MeetingException::roomNotCreated();
            }

            $liveKitService = app(LiveKitService::class);

            // Set default permissions based on user type
            $defaultPermissions = $this->getDefaultUserPermissions($user);
            $finalPermissions = array_merge($defaultPermissions, $permissions);

            $token = $liveKitService->generateParticipantToken(
                $this->meeting_room_name,
                $user,
                $finalPermissions
            );

            Log::info('Participant token generated', [
                'session_type' => $this->getMeetingSessionType(),
                'session_id' => $this->id,
                'user_id' => $user->id,
                'user_type' => $user->user_type,
                'permissions' => $finalPermissions,
            ]);

            return $token;

        } catch (\Exception $e) {
            Log::error('Failed to generate participant token', [
                'session_type' => $this->getMeetingSessionType(),
                'session_id' => $this->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw MeetingException::tokenGenerationFailed($e->getMessage());
        }
    }

    /**
     * Get the meeting room name for this session
     */
    public function getMeetingRoomName(): ?string
    {
        return $this->meeting_room_name;
    }

    /**
     * Get the meeting ID for this session
     */
    public function getMeetingId(): ?string
    {
        return $this->meeting_id;
    }

    /**
     * Get the meeting link for this session
     */
    public function getMeetingLink(): ?string
    {
        return $this->meeting_link;
    }

    /**
     * Check if the meeting is currently active
     */
    public function isMeetingActive(): bool
    {
        if (! $this->meeting_room_name) {
            return false;
        }

        // Check with LiveKit service if room has active participants
        try {
            $liveKitService = app(LiveKitService::class);
            $roomInfo = $liveKitService->getRoomInfo($this->meeting_room_name);

            return $roomInfo && $roomInfo['is_active'];
        } catch (\Exception $e) {
            Log::warning('Failed to check meeting status', [
                'session_type' => $this->getMeetingSessionType(),
                'session_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get meeting configuration specific to this session type
     */
    public function getMeetingConfiguration(): array
    {
        $baseConfig = [
            'session_id' => $this->id,
            'session_type' => $this->getMeetingSessionType(),
            'academy_id' => $this->getAcademy()?->id,
            'meeting_room_name' => $this->meeting_room_name,
            'meeting_link' => $this->meeting_link,
            'scheduled_at' => $this->getMeetingStartTime()?->toISOString(),
            'duration_minutes' => $this->getMeetingDurationMinutes(),
            'max_participants' => 50,
            'recording_enabled' => false,
        ];

        // Allow child classes to extend configuration
        if (method_exists($this, 'getExtendedMeetingConfiguration')) {
            return array_merge($baseConfig, $this->getExtendedMeetingConfiguration());
        }

        return $baseConfig;
    }

    /**
     * Get default permissions for a user based on their role
     */
    protected function getDefaultUserPermissions(User $user): array
    {
        // Super admin and academy admin get full permissions
        if (in_array($user->user_type, ['super_admin', 'admin'])) {
            return [
                'can_publish' => true,
                'can_subscribe' => true,
                'can_publish_data' => true,
                'can_update_metadata' => true,
            ];
        }

        // Teachers get publishing permissions
        if (in_array($user->user_type, ['quran_teacher', 'academic_teacher'])) {
            return [
                'can_publish' => true,
                'can_subscribe' => true,
                'can_publish_data' => true,
                'can_update_metadata' => false,
            ];
        }

        // Students and parents get basic permissions
        return [
            'can_publish' => true,
            'can_subscribe' => true,
            'can_publish_data' => false,
            'can_update_metadata' => false,
        ];
    }

    /**
     * Check if user can join based on session timing and status
     */
    protected function canJoinBasedOnTiming(User $user): bool
    {
        $now = now();
        $startTime = $this->getMeetingStartTime();

        if (! $startTime) {
            return false;
        }

        // Get timing configuration
        $config = $this->getMeetingConfiguration();
        $preparationMinutes = $config['preparation_minutes'] ?? 15;
        $endingBufferMinutes = $config['ending_buffer_minutes'] ?? 5;

        $preparationStart = $startTime->copy()->subMinutes($preparationMinutes);
        $sessionEnd = $startTime->copy()->addMinutes($this->getMeetingDurationMinutes() + $endingBufferMinutes);

        // Teachers can join during preparation period
        if (in_array($user->user_type, ['quran_teacher', 'academic_teacher', 'admin', 'super_admin'])) {
            return $now->gte($preparationStart) && $now->lt($sessionEnd);
        }

        // Students can join from session start time
        return $now->gte($startTime) && $now->lt($sessionEnd);
    }

    /**
     * Relationship with meeting attendances
     */
    public function meetingAttendances()
    {
        return $this->hasMany(\App\Models\MeetingAttendance::class, 'session_id')
            ->where('session_type', $this->getMeetingSessionType());
    }

    /**
     * Get current meeting participants count
     */
    public function getCurrentParticipantsCount(): int
    {
        try {
            if (! $this->meeting_room_name) {
                return 0;
            }

            $liveKitService = app(LiveKitService::class);
            $roomInfo = $liveKitService->getRoomInfo($this->meeting_room_name);

            return $roomInfo ? $roomInfo['participant_count'] : 0;
        } catch (\Exception $e) {
            Log::warning('Failed to get participants count', [
                'session_type' => $this->getMeetingSessionType(),
                'session_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * End the meeting and cleanup resources
     */
    public function endMeeting(): bool
    {
        try {
            if (! $this->meeting_room_name) {
                return true; // No meeting to end
            }

            $liveKitService = app(LiveKitService::class);
            $success = $liveKitService->endMeeting($this->meeting_room_name);

            if ($success) {
                Log::info('Meeting ended successfully', [
                    'session_type' => $this->getMeetingSessionType(),
                    'session_id' => $this->id,
                    'room_name' => $this->meeting_room_name,
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to end meeting', [
                'session_type' => $this->getMeetingSessionType(),
                'session_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
