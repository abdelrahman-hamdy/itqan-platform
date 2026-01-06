<?php

namespace App\Contracts;

use App\Models\Academy;
use App\Models\User;
use App\Services\LiveKit\LiveKitRecordingManager;
use App\Services\LiveKit\LiveKitRoomManager;
use App\Services\LiveKit\LiveKitTokenGenerator;
use App\Services\LiveKit\LiveKitWebhookHandler;
use Carbon\Carbon;

/**
 * LiveKitServiceInterface
 *
 * Interface for the LiveKit service coordinator.
 * This service coordinates various LiveKit components including token generation,
 * room management, webhook handling, and recording operations.
 */
interface LiveKitServiceInterface
{
    /**
     * Check if LiveKit is properly configured.
     *
     * @return bool True if all required configuration is present
     */
    public function isConfigured(): bool;

    /**
     * Create a meeting room and return comprehensive meeting data.
     *
     * @param  Academy  $academy  The academy hosting the meeting
     * @param  string  $sessionType  The type of session (quran_session, academic_session, etc.)
     * @param  int  $sessionId  The session ID
     * @param  Carbon  $startTime  The scheduled start time
     * @param  array  $options  Additional options (max_participants, recording_enabled, auto_record, etc.)
     * @return array Meeting data including room_name, server_url, settings, and features
     */
    public function createMeeting(
        Academy $academy,
        string $sessionType,
        int $sessionId,
        Carbon $startTime,
        array $options = []
    ): array;

    /**
     * Generate access token for participant to join room.
     *
     * @param  string  $roomName  The room name
     * @param  User  $user  The user joining the room
     * @param  array  $permissions  Permission settings for the participant
     * @return string JWT access token
     */
    public function generateParticipantToken(
        string $roomName,
        User $user,
        array $permissions = []
    ): string;

    /**
     * Start recording for a room using LiveKit Egress.
     *
     * @param  string  $roomName  The room to record
     * @param  array  $options  Recording options
     * @return array Recording details including egress_id
     */
    public function startRecording(string $roomName, array $options = []): array;

    /**
     * Stop an active recording.
     *
     * @param  string  $egressId  The egress ID of the recording to stop
     * @return bool True if recording was stopped successfully
     */
    public function stopRecording(string $egressId): bool;

    /**
     * Get room information and current participants.
     *
     * @param  string  $roomName  The room name
     * @return array|null Room information or null if room doesn't exist
     */
    public function getRoomInfo(string $roomName): ?array;

    /**
     * End meeting and clean up room.
     *
     * @param  string  $roomName  The room to end
     * @return bool True if meeting was ended successfully
     */
    public function endMeeting(string $roomName): bool;

    /**
     * Control meeting duration by setting room timeout.
     *
     * @param  string  $roomName  The room name
     * @param  int  $durationMinutes  The duration in minutes
     * @return bool True if duration was set successfully
     */
    public function setMeetingDuration(string $roomName, int $durationMinutes): bool;

    /**
     * Handle webhooks from LiveKit server.
     *
     * @param  array  $webhookData  The webhook payload
     */
    public function handleWebhook(array $webhookData): void;

    /**
     * Check if user is currently in LiveKit room by querying the API directly.
     *
     * @param  string  $roomName  The room name
     * @param  string  $userIdentity  The user identity
     * @return bool True if user is in the room
     */
    public function isUserInRoom(string $roomName, string $userIdentity): bool;

    /**
     * Get the token generator instance.
     *
     * Provides direct access to advanced token generation features.
     */
    public function tokenGenerator(): LiveKitTokenGenerator;

    /**
     * Get the room manager instance.
     *
     * Provides direct access to advanced room management features.
     */
    public function roomManager(): LiveKitRoomManager;

    /**
     * Get the webhook handler instance.
     *
     * Provides direct access to webhook verification and handling.
     */
    public function webhookHandler(): LiveKitWebhookHandler;

    /**
     * Get the recording manager instance.
     *
     * Provides direct access to advanced recording features.
     */
    public function recordingManager(): LiveKitRecordingManager;
}
