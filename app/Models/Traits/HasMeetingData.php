<?php

namespace App\Models\Traits;

use App\Enums\MeetingStatus;
use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\AcademySettings;
use App\Models\User;
use Carbon\Carbon;

/**
 * Trait HasMeetingData
 *
 * Provides meeting-related functionality for session models:
 * - Meeting link generation and management
 * - Meeting status checks
 * - LiveKit integration
 * - Participant token generation
 * - Room information retrieval
 */
trait HasMeetingData
{
    /**
     * Virtual meeting object accessor
     * Returns an object with meeting properties for API compatibility
     * Since meeting data is stored directly on sessions, not in a separate model
     */
    public function getMeetingAttribute(): ?object
    {
        if (! $this->meeting_room_name) {
            return null;
        }

        $meetingStatus = $this->getMeetingStatus();

        return (object) [
            'id' => $this->meeting_id,
            'room_name' => $this->meeting_room_name,
            'meeting_link' => $this->meeting_link,
            'status' => $meetingStatus->value, // Return string for API compatibility
            'status_enum' => $meetingStatus,   // Return enum for internal use
            'platform' => $this->meeting_platform ?? 'livekit',
            'expires_at' => $this->meeting_expires_at,
            'created_at' => $this->meeting_created_at ?? $this->created_at,
        ];
    }

    /**
     * Get meeting status based on session state
     */
    protected function getMeetingStatus(): MeetingStatus
    {
        if (! $this->meeting_room_name) {
            return MeetingStatus::NOT_CREATED;
        }

        if ($this->meeting_expires_at && $this->meeting_expires_at->isPast()) {
            return MeetingStatus::EXPIRED;
        }

        $sessionStatus = $this->status instanceof SessionStatus
            ? $this->status
            : SessionStatus::tryFrom($this->status);

        if (! $sessionStatus) {
            return MeetingStatus::READY;
        }

        return MeetingStatus::fromSessionStatus(
            $sessionStatus,
            hasRoom: true,
            isExpired: false
        );
    }

    /**
     * Check if meeting link exists
     */
    public function hasMeetingLink(): bool
    {
        return ! empty($this->meeting_link) && $this->isMeetingValid();
    }

    /**
     * Get meeting URL
     */
    public function getMeetingUrl(): ?string
    {
        return $this->getMeetingJoinUrl();
    }

    /**
     * Check if the meeting is currently active
     */
    public function isMeetingActive(): bool
    {
        return in_array($this->status, [SessionStatus::READY, SessionStatus::ONGOING]);
    }

    /**
     * Generate meeting link for this session
     */
    public function generateMeetingLink(array $options = []): string
    {
        // If meeting already exists and is valid, return existing link
        if ($this->meeting_room_name && $this->isMeetingValid()) {
            return $this->meeting_link;
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        // Set default options
        $defaultOptions = [
            'recording_enabled' => $this->getDefaultRecordingEnabled(),
            'max_participants' => $this->getDefaultMaxParticipants(),
            'max_duration' => $this->duration_minutes ?? 120,
            'session_type' => $this->getMeetingType(),
        ];

        $mergedOptions = array_merge($defaultOptions, $options);

        // Generate meeting using LiveKit service
        $meetingInfo = $livekitService->createMeeting(
            $this->academy,
            $this->getMeetingType(),
            $this->id,
            $this->scheduled_at ?? now(),
            $mergedOptions
        );

        // Update session with meeting info
        $this->update([
            'meeting_link' => $meetingInfo['meeting_url'],
            'meeting_id' => $meetingInfo['meeting_id'],
            'meeting_platform' => $meetingInfo['platform'],
            'meeting_source' => $meetingInfo['platform'],
            'meeting_data' => $meetingInfo,
            'meeting_room_name' => $meetingInfo['room_name'],
            'meeting_auto_generated' => true,
            'meeting_expires_at' => $meetingInfo['expires_at'],
        ]);

        return $meetingInfo['meeting_url'];
    }

    /**
     * Get meeting information
     */
    public function getMeetingInfo(): ?array
    {
        if (! $this->meeting_data) {
            return null;
        }

        return $this->meeting_data;
    }

    /**
     * Check if meeting is still valid
     */
    public function isMeetingValid(): bool
    {
        if (! $this->meeting_room_name) {
            return false;
        }

        if ($this->meeting_expires_at && $this->meeting_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get meeting join URL for display
     */
    public function getMeetingJoinUrl(): ?string
    {
        if (! $this->isMeetingValid()) {
            return null;
        }

        return $this->meeting_link;
    }

    /**
     * Generate participant access token for LiveKit room
     */
    public function generateParticipantToken(User $user, array $permissions = []): string
    {
        if (! $this->meeting_room_name) {
            throw new \Exception('Meeting room not created yet');
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        // Set permissions based on user role
        $defaultPermissions = [
            'can_publish' => true,
            'can_subscribe' => true,
            'can_update_metadata' => $this->canUserManageMeeting($user),
        ];

        $mergedPermissions = array_merge($defaultPermissions, $permissions);

        return $livekitService->generateParticipantToken(
            $this->meeting_room_name,
            $user,
            $mergedPermissions
        );
    }

    /**
     * Get room information from LiveKit server
     */
    public function getRoomInfo(): ?array
    {
        if (! $this->meeting_room_name) {
            return null;
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        return $livekitService->getRoomInfo($this->meeting_room_name);
    }

    /**
     * End the meeting and clean up room
     */
    public function endMeeting(): bool
    {
        if (! $this->meeting_room_name) {
            return false;
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        $success = $livekitService->endMeeting($this->meeting_room_name);

        if ($success) {
            $this->update([
                'ended_at' => now(),
                'status' => SessionStatus::COMPLETED,
                'meeting_room_name' => null,
            ]);
        }

        return $success;
    }

    /**
     * Check if user is currently in the meeting room
     */
    public function isUserInMeeting(User $user): bool
    {
        $roomInfo = $this->getRoomInfo();

        if (! $roomInfo || ! isset($roomInfo['participants'])) {
            return false;
        }

        $userIdentity = $user->id.'_'.\Illuminate\Support\Str::slug($user->first_name.'_'.$user->last_name);

        foreach ($roomInfo['participants'] as $participant) {
            if ($participant['id'] === $userIdentity) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get meeting statistics
     */
    public function getMeetingStats(): array
    {
        $roomInfo = $this->getRoomInfo();
        $meetingData = $this->meeting_data ?? [];

        return [
            'is_active' => $roomInfo ? ($roomInfo['is_active'] ?? false) : false,
            'participant_count' => $roomInfo ? ($roomInfo['participant_count'] ?? 0) : 0,
            'participants' => $roomInfo ? ($roomInfo['participants'] ?? []) : [],
            'duration_so_far' => $this->started_at ? now()->diffInMinutes($this->started_at) : 0,
            'scheduled_duration' => $this->duration_minutes,
            'room_created_at' => $roomInfo ? ($roomInfo['created_at'] ?? null) : null,
        ];
    }

    /**
     * Get the meeting start time (MeetingCapable interface)
     */
    public function getMeetingStartTime(): ?Carbon
    {
        return $this->scheduled_at;
    }

    /**
     * Get the meeting end time (MeetingCapable interface)
     */
    public function getMeetingEndTime(): ?Carbon
    {
        if ($this->scheduled_at && $this->duration_minutes) {
            return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
        }

        return $this->ended_at;
    }

    /**
     * Get the expected duration of the meeting in minutes (MeetingCapable interface)
     */
    public function getMeetingDurationMinutes(): int
    {
        return $this->duration_minutes ?? 60;
    }

    /**
     * Get the academy this session belongs to (MeetingCapable interface)
     */
    public function getAcademy(): ?Academy
    {
        return $this->academy;
    }

    /**
     * Get the meeting type identifier (e.g., 'quran', 'academic', 'interactive')
     * Must be implemented by each child class
     */
    abstract public function getMeetingType(): string;

    /**
     * Get the session type identifier for meeting purposes (MeetingCapable interface)
     */
    public function getMeetingSessionType(): string
    {
        return $this->getMeetingType();
    }

    /**
     * Get meeting-specific configuration
     * Must be implemented by each child class or provided by HasMeetings trait
     */
    abstract public function getMeetingConfiguration(): array;

    /**
     * Check if a user can manage this meeting (create, end, control participants)
     * Must be implemented by each child class
     */
    abstract public function canUserManageMeeting(User $user): bool;

    /**
     * Get default recording enabled setting
     * Can be overridden by child classes
     */
    protected function getDefaultRecordingEnabled(): bool
    {
        // Get from academy settings
        $academySettings = AcademySettings::where('academy_id', $this->academy_id)->first();
        $settingsJson = $academySettings?->settings ?? [];

        return $settingsJson['meeting_recording_enabled'] ?? false;
    }

    /**
     * Get default max participants
     * Can be overridden by child classes
     */
    protected function getDefaultMaxParticipants(): int
    {
        // Get from academy settings
        $academySettings = AcademySettings::where('academy_id', $this->academy_id)->first();
        $settingsJson = $academySettings?->settings ?? [];

        return $settingsJson['meeting_max_participants'] ?? 10;
    }
}
