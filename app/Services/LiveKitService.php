<?php

namespace App\Services;

use App\Contracts\LiveKitServiceInterface;
use App\Models\Academy;
use App\Models\User;
use App\Services\LiveKit\LiveKitRecordingManager;
use App\Services\LiveKit\LiveKitRoomManager;
use App\Services\LiveKit\LiveKitTokenGenerator;
use App\Services\LiveKit\LiveKitWebhookHandler;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * LiveKit Service Coordinator
 *
 * This service coordinates the various LiveKit components:
 * - LiveKitTokenGenerator: JWT token generation
 * - LiveKitRoomManager: Room and participant management
 * - LiveKitWebhookHandler: Webhook processing
 * - LiveKitRecordingManager: Recording operations
 */
class LiveKitService implements LiveKitServiceInterface
{
    private ?string $serverUrl;

    public function __construct(
        private LiveKitTokenGenerator $tokenGenerator,
        private LiveKitRoomManager $roomManager,
        private LiveKitWebhookHandler $webhookHandler,
        private LiveKitRecordingManager $recordingManager,
    ) {
        $this->serverUrl = config('livekit.server_url');
    }

    /**
     * Check if LiveKit is properly configured
     */
    public function isConfigured(): bool
    {
        return $this->tokenGenerator->isConfigured() &&
               $this->roomManager->isConfigured() &&
               ! empty($this->serverUrl);
    }

    /**
     * Create a meeting room and return comprehensive meeting data
     */
    public function createMeeting(
        Academy $academy,
        string $sessionType,
        int $sessionId,
        Carbon $startTime,
        array $options = []
    ): array {
        // Generate deterministic room name early so it's available in catch block
        $roomName = $this->roomManager->generateRoomName($academy, $sessionType, $sessionId);

        try {
            if (! $this->isConfigured()) {
                throw new Exception('LiveKit is not properly configured. Please check API credentials.');
            }

            // SIMPLIFIED APPROACH: Skip SDK room creation/checking
            // LiveKit will automatically create rooms when first participant joins
            // This avoids SDK compatibility issues with self-hosted LiveKit servers
            Log::info('Generating LiveKit meeting (auto-create on join)', [
                'room_name' => $roomName,
                'session_id' => $sessionId,
                'academy_id' => $academy->id,
            ]);

            return [
                'platform' => 'livekit',
                'room_name' => $roomName,
                'room_sid' => null, // Will be assigned when room is created on first join
                'server_url' => $this->serverUrl,
                'meeting_id' => $roomName,
                'meeting_url' => $this->generateMeetingUrl($roomName),
                'join_info' => [
                    'server_url' => $this->serverUrl,
                    'room_name' => $roomName,
                    'access_method' => 'token_based',
                ],
                'settings' => [
                    'max_participants' => $options['max_participants'] ?? 100,
                    'recording_enabled' => $options['recording_enabled'] ?? false,
                    'auto_record' => $options['auto_record'] ?? false,
                    'empty_timeout' => $options['empty_timeout'] ?? 300,
                    'max_duration' => $options['max_duration'] ?? 7200,
                ],
                'features' => [
                    'video' => true,
                    'audio' => true,
                    'screen_sharing' => true,
                    'chat' => true,
                    'recording' => true,
                    'breakout_rooms' => false,
                    'whiteboard' => false,
                ],
                'created_at' => now(),
                'scheduled_at' => $startTime,
                'expires_at' => $options['expires_at'] ?? $startTime->copy()->addHours(3),
                'auto_create_on_join' => true,
            ];

        } catch (Exception $e) {
            Log::warning('Could not pre-create LiveKit room via API (will auto-create on join)', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'academy_id' => $academy->id,
                'room_name' => $roomName,
            ]);

            // Return meeting info without pre-creating room
            // LiveKit will automatically create the room when first participant joins
            return [
                'platform' => 'livekit',
                'room_name' => $roomName,
                'room_sid' => null, // Will be assigned by LiveKit on first join
                'server_url' => $this->serverUrl,
                'meeting_id' => $roomName,
                'meeting_url' => $this->generateMeetingUrl($roomName),
                'join_info' => [
                    'server_url' => $this->serverUrl,
                    'room_name' => $roomName,
                    'access_method' => 'token_based',
                ],
                'settings' => [
                    'max_participants' => $options['max_participants'] ?? 100,
                    'recording_enabled' => $options['recording_enabled'] ?? false,
                    'auto_record' => $options['auto_record'] ?? false,
                    'empty_timeout' => $options['empty_timeout'] ?? 300,
                    'max_duration' => $options['max_duration'] ?? 7200,
                ],
                'features' => [
                    'video' => true,
                    'audio' => true,
                    'screen_sharing' => true,
                    'chat' => true,
                    'recording' => true,
                    'breakout_rooms' => false,
                    'whiteboard' => false,
                ],
                'created_at' => now(),
                'scheduled_at' => $startTime,
                'expires_at' => $options['expires_at'] ?? $startTime->copy()->addHours(3),
                'auto_create_on_join' => true, // Flag indicating room will be created on first join
            ];
        }
    }

    /**
     * Generate access token for participant to join room
     *
     * Delegates to LiveKitTokenGenerator
     */
    public function generateParticipantToken(
        string $roomName,
        User $user,
        array $permissions = []
    ): string {
        return $this->tokenGenerator->generateParticipantToken($roomName, $user, $permissions);
    }

    /**
     * Get avatar metadata for a user — same shape that is embedded in the
     * JWT metadata claim. Exposed so the mobile token endpoint can echo the
     * same fields in its HTTP response body, keeping the wire + the JWT in
     * lockstep without forcing the client to parse the JWT itself.
     */
    public function getUserAvatarData(User $user): array
    {
        return $this->tokenGenerator->getUserAvatarData($user);
    }

    /**
     * Start recording for a room using LiveKit Egress
     *
     * Delegates to LiveKitRecordingManager
     */
    public function startRecording(string $roomName, array $options = []): array
    {
        return $this->recordingManager->startRecording($roomName, $options);
    }

    /**
     * Start audio-only track composite recording
     *
     * Delegates to LiveKitRecordingManager
     */
    public function startTrackRecording(string $roomName, array $options = []): array
    {
        return $this->recordingManager->startTrackRecording($roomName, $options);
    }

    /**
     * Stop an active recording
     *
     * Delegates to LiveKitRecordingManager
     */
    public function stopRecording(string $egressId): bool
    {
        return $this->recordingManager->stopRecording($egressId);
    }

    /**
     * Get room information and current participants
     *
     * Delegates to LiveKitRoomManager
     */
    public function getRoomInfo(string $roomName): ?array
    {
        return $this->roomManager->getRoomInfo($roomName);
    }

    /**
     * End meeting and clean up room
     *
     * Delegates to LiveKitRoomManager
     */
    public function endMeeting(string $roomName, bool $force = true): bool
    {
        return $this->roomManager->endMeeting($roomName, $force);
    }

    /**
     * True when the room has at least one active participant. Null return
     * (treated as "not empty") on transient LiveKit API failures so the
     * cron does not force-complete sessions just because a probe failed.
     */
    public function roomHasActiveParticipants(string $roomName): bool
    {
        $participants = $this->roomManager->listActiveParticipants($roomName);
        if ($participants === null) {
            return true;
        }

        return count($participants) > 0;
    }

    /**
     * Control meeting duration by setting room timeout
     *
     * Delegates to LiveKitRoomManager
     */
    public function setMeetingDuration(string $roomName, int $durationMinutes): bool
    {
        return $this->roomManager->setMeetingDuration($roomName, $durationMinutes);
    }

    /**
     * Handle webhooks from LiveKit server
     *
     * Delegates to LiveKitWebhookHandler
     */
    public function handleWebhook(array $webhookData): void
    {
        $this->webhookHandler->handleEvent($webhookData);
    }

    /**
     * Check if user is currently in LiveKit room by querying the API directly
     *
     * Delegates to LiveKitRoomManager
     */
    public function isUserInRoom(string $roomName, string $userIdentity): bool
    {
        return $this->roomManager->isUserInRoom($roomName, $userIdentity);
    }

    /**
     * Generate meeting URL
     */
    private function generateMeetingUrl(string $roomName): string
    {
        // This would be your frontend meeting room URL
        return config('app.url')."/meeting/{$roomName}";
    }

    /**
     * Get the token generator instance
     *
     * Provides direct access to advanced token generation features
     */
    public function tokenGenerator(): LiveKitTokenGenerator
    {
        return $this->tokenGenerator;
    }

    /**
     * Get the room manager instance
     *
     * Provides direct access to advanced room management features
     */
    public function roomManager(): LiveKitRoomManager
    {
        return $this->roomManager;
    }

    /**
     * Get the webhook handler instance
     *
     * Provides direct access to webhook verification and handling
     */
    public function webhookHandler(): LiveKitWebhookHandler
    {
        return $this->webhookHandler;
    }

    /**
     * Get the recording manager instance
     *
     * Provides direct access to advanced recording features
     */
    public function recordingManager(): LiveKitRecordingManager
    {
        return $this->recordingManager;
    }
}
