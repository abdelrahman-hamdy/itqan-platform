<?php

namespace App\Contracts;

use App\DTOs\MeetingData;
use App\Models\User;

/**
 * Interface for video meeting providers.
 *
 * This interface defines the contract for video conferencing providers
 * (LiveKit, Zoom, etc.) to enable easy swapping or multi-provider support.
 */
interface MeetingProviderInterface
{
    /**
     * Get the provider name.
     *
     * @return string The provider identifier ('livekit', 'zoom', etc.)
     */
    public function getProviderName(): string;

    /**
     * Create a meeting room.
     *
     * @param  string  $roomName  The unique room name
     * @param  array  $options  Room creation options
     * @return MeetingData The created meeting data
     */
    public function createRoom(string $roomName, array $options = []): MeetingData;

    /**
     * Get room information.
     *
     * @param  string  $roomName  The room name
     * @return MeetingData|null The room data or null if not found
     */
    public function getRoom(string $roomName): ?MeetingData;

    /**
     * Close/end a room.
     *
     * @param  string  $roomName  The room name
     * @return bool Whether the room was closed successfully
     */
    public function closeRoom(string $roomName): bool;

    /**
     * Check if a room exists and is active.
     *
     * @param  string  $roomName  The room name
     * @return bool Whether the room exists
     */
    public function roomExists(string $roomName): bool;

    /**
     * Generate an access token for a participant.
     *
     * @param  string  $roomName  The room to join
     * @param  User  $user  The user joining
     * @param  array  $permissions  Participant permissions
     * @return string The access token
     */
    public function generateToken(string $roomName, User $user, array $permissions = []): string;

    /**
     * Get participants currently in a room.
     *
     * @param  string  $roomName  The room name
     * @return array List of participants
     */
    public function getParticipants(string $roomName): array;

    /**
     * Remove a participant from a room.
     *
     * @param  string  $roomName  The room name
     * @param  string  $participantId  The participant identifier
     * @return bool Whether the participant was removed
     */
    public function removeParticipant(string $roomName, string $participantId): bool;

    /**
     * Start recording for a room.
     *
     * @param  string  $roomName  The room name
     * @param  array  $options  Recording options
     * @return string|null The recording ID or null on failure
     */
    public function startRecording(string $roomName, array $options = []): ?string;

    /**
     * Stop recording for a room.
     *
     * @param  string  $recordingId  The recording ID
     * @return bool Whether recording was stopped
     */
    public function stopRecording(string $recordingId): bool;

    /**
     * Get recording status.
     *
     * @param  string  $recordingId  The recording ID
     * @return array Recording status information
     */
    public function getRecordingStatus(string $recordingId): array;

    /**
     * Check if the provider is configured and available.
     *
     * @return bool Whether the provider is available
     */
    public function isAvailable(): bool;
}
