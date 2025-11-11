<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Interface for models that can host LiveKit meetings
 *
 * This interface ensures consistent meeting functionality across different session types
 * (QuranSession, AcademicSession, InteractiveCourseSession, etc.)
 */
interface MeetingCapable
{
    /**
     * Generate a meeting link for this session
     *
     * @param  array  $options  Meeting configuration options
     * @return string The meeting URL
     */
    public function generateMeetingLink(array $options = []): string;

    /**
     * Generate a participant access token for joining the meeting
     *
     * @param  User  $user  The user requesting access
     * @param  array  $permissions  Permissions for the user (can_publish, can_subscribe, etc.)
     * @return string LiveKit access token
     */
    public function generateParticipantToken(User $user, array $permissions = []): string;

    /**
     * Get the meeting room name for this session
     *
     * @return string|null The LiveKit room name
     */
    public function getMeetingRoomName(): ?string;

    /**
     * Get the meeting ID for this session
     *
     * @return string|null The meeting identifier
     */
    public function getMeetingId(): ?string;

    /**
     * Get the meeting link for this session
     *
     * @return string|null The full meeting URL
     */
    public function getMeetingLink(): ?string;

    /**
     * Check if a user can join this meeting
     *
     * @param  User  $user  The user to check
     * @return bool Whether the user can join
     */
    public function canUserJoinMeeting(User $user): bool;

    /**
     * Check if a user can manage this meeting (create, end, control participants)
     *
     * @param  User  $user  The user to check
     * @return bool Whether the user can manage the meeting
     */
    public function canUserManageMeeting(User $user): bool;

    /**
     * Get the session type identifier for meeting purposes
     *
     * @return string Session type (e.g., 'quran', 'academic', 'interactive')
     */
    public function getMeetingSessionType(): string;

    /**
     * Get the academy this session belongs to
     */
    public function getAcademy(): ?\App\Models\Academy;

    /**
     * Get meeting configuration specific to this session type
     *
     * @return array Configuration options for the meeting
     */
    public function getMeetingConfiguration(): array;

    /**
     * Get the scheduled start time for the meeting
     */
    public function getMeetingStartTime(): ?\Carbon\Carbon;

    /**
     * Get the expected duration of the meeting in minutes
     *
     * @return int Duration in minutes
     */
    public function getMeetingDurationMinutes(): int;

    /**
     * Check if the meeting is currently active
     *
     * @return bool Whether the meeting is ongoing
     */
    public function isMeetingActive(): bool;

    /**
     * Get all participants who should have access to this meeting
     *
     * @return \Illuminate\Database\Eloquent\Collection Collection of User models
     */
    public function getMeetingParticipants(): \Illuminate\Database\Eloquent\Collection;
}
