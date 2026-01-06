<?php

namespace Tests\Mocks;

use App\Contracts\LiveKitServiceInterface;
use App\Models\Academy;
use App\Models\User;
use App\Services\LiveKit\LiveKitRecordingManager;
use App\Services\LiveKit\LiveKitRoomManager;
use App\Services\LiveKit\LiveKitTokenGenerator;
use App\Services\LiveKit\LiveKitWebhookHandler;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * LiveKit Mock for Testing
 *
 * Provides a mock implementation of LiveKitServiceInterface for testing
 * without requiring actual LiveKit server connection.
 */
class LiveKitMock implements LiveKitServiceInterface
{
    public bool $isConfigured = true;

    public array $createdRooms = [];

    public array $generatedTokens = [];

    public array $processedWebhooks = [];

    public array $activeRecordings = [];

    public array $roomParticipants = [];

    // Control responses
    public ?string $nextTokenResponse = null;

    public bool $shouldFailTokenGeneration = false;

    public bool $shouldFailRoomCreation = false;

    /**
     * Check if LiveKit is configured
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Create a meeting room
     */
    public function createMeeting(
        Academy $academy,
        string $sessionType,
        int $sessionId,
        Carbon $startTime,
        array $options = []
    ): array {
        if ($this->shouldFailRoomCreation) {
            throw new \Exception('Mock: Room creation failed');
        }

        $roomName = $this->generateRoomName($academy, $sessionType, $sessionId);

        $meetingData = [
            'room_name' => $roomName,
            'server_url' => 'wss://mock-livekit-server.test',
            'api_url' => 'https://mock-livekit-server.test',
            'settings' => [
                'max_participants' => $options['max_participants'] ?? 10,
                'recording_enabled' => $options['recording_enabled'] ?? false,
                'auto_record' => $options['auto_record'] ?? false,
                'empty_timeout' => $options['empty_timeout'] ?? 300,
            ],
            'features' => [
                'video' => true,
                'audio' => true,
                'screen_share' => true,
                'chat' => true,
            ],
            'created_at' => now()->toIso8601String(),
        ];

        $this->createdRooms[$roomName] = $meetingData;

        return $meetingData;
    }

    /**
     * Generate a room name following the application convention
     */
    protected function generateRoomName(Academy $academy, string $sessionType, int $sessionId): string
    {
        $prefix = match ($sessionType) {
            'quran_session' => 'QS',
            'academic_session' => 'AS',
            'interactive_course_session' => 'IC',
            default => 'MT',
        };

        return sprintf('%s-%s-%d-%s', $prefix, $academy->subdomain, $sessionId, Str::random(8));
    }

    /**
     * Generate participant token
     */
    public function generateParticipantToken(
        string $roomName,
        User $user,
        array $permissions = []
    ): string {
        if ($this->shouldFailTokenGeneration) {
            throw new \Exception('Mock: Token generation failed');
        }

        if ($this->nextTokenResponse) {
            $token = $this->nextTokenResponse;
            $this->nextTokenResponse = null;

            return $token;
        }

        // Generate a mock JWT-like token
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'video' => [
                'room' => $roomName,
                'roomJoin' => true,
                'canPublish' => $permissions['can_publish'] ?? true,
                'canSubscribe' => $permissions['can_subscribe'] ?? true,
                'canPublishData' => $permissions['can_publish_data'] ?? true,
            ],
            'iss' => 'mock-api-key',
            'sub' => $this->formatUserIdentity($user),
            'name' => $user->name,
            'exp' => now()->addHours(2)->timestamp,
            'nbf' => now()->timestamp,
        ]));
        $signature = base64_encode(hash_hmac('sha256', "$header.$payload", 'mock-secret', true));

        $token = "$header.$payload.$signature";

        $this->generatedTokens[] = [
            'room_name' => $roomName,
            'user_id' => $user->id,
            'user_type' => $user->user_type,
            'permissions' => $permissions,
            'token' => $token,
            'generated_at' => now()->toIso8601String(),
        ];

        return $token;
    }

    /**
     * Format user identity for LiveKit
     */
    protected function formatUserIdentity(User $user): string
    {
        return "{$user->user_type}_{$user->id}";
    }

    /**
     * Start recording
     */
    public function startRecording(string $roomName, array $options = []): array
    {
        $egressId = 'EG_'.Str::random(16);

        $this->activeRecordings[$egressId] = [
            'room_name' => $roomName,
            'status' => 'active',
            'started_at' => now()->toIso8601String(),
            'options' => $options,
        ];

        return [
            'egress_id' => $egressId,
            'room_name' => $roomName,
            'status' => 'active',
        ];
    }

    /**
     * Stop recording
     */
    public function stopRecording(string $egressId): bool
    {
        if (isset($this->activeRecordings[$egressId])) {
            $this->activeRecordings[$egressId]['status'] = 'stopped';
            $this->activeRecordings[$egressId]['stopped_at'] = now()->toIso8601String();

            return true;
        }

        return false;
    }

    /**
     * Get room info
     */
    public function getRoomInfo(string $roomName): ?array
    {
        if (! isset($this->createdRooms[$roomName])) {
            return null;
        }

        return array_merge($this->createdRooms[$roomName], [
            'participants' => $this->roomParticipants[$roomName] ?? [],
            'participant_count' => count($this->roomParticipants[$roomName] ?? []),
        ]);
    }

    /**
     * End meeting
     */
    public function endMeeting(string $roomName): bool
    {
        if (isset($this->createdRooms[$roomName])) {
            $this->createdRooms[$roomName]['ended_at'] = now()->toIso8601String();
            $this->roomParticipants[$roomName] = [];

            return true;
        }

        return false;
    }

    /**
     * Set meeting duration
     */
    public function setMeetingDuration(string $roomName, int $durationMinutes): bool
    {
        if (isset($this->createdRooms[$roomName])) {
            $this->createdRooms[$roomName]['duration_minutes'] = $durationMinutes;

            return true;
        }

        return false;
    }

    /**
     * Handle webhook
     */
    public function handleWebhook(array $webhookData): void
    {
        $this->processedWebhooks[] = [
            'data' => $webhookData,
            'processed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Check if user is in room
     */
    public function isUserInRoom(string $roomName, string $userIdentity): bool
    {
        $participants = $this->roomParticipants[$roomName] ?? [];

        return in_array($userIdentity, array_column($participants, 'identity'));
    }

    // These methods return null since we don't need real sub-managers in tests
    public function tokenGenerator(): LiveKitTokenGenerator
    {
        throw new \Exception('Mock does not support direct tokenGenerator() access');
    }

    public function roomManager(): LiveKitRoomManager
    {
        throw new \Exception('Mock does not support direct roomManager() access');
    }

    public function webhookHandler(): LiveKitWebhookHandler
    {
        throw new \Exception('Mock does not support direct webhookHandler() access');
    }

    public function recordingManager(): LiveKitRecordingManager
    {
        throw new \Exception('Mock does not support direct recordingManager() access');
    }

    // ===========================================
    // TEST HELPER METHODS
    // ===========================================

    /**
     * Simulate a participant joining a room
     */
    public function simulateParticipantJoin(string $roomName, User $user): void
    {
        $identity = $this->formatUserIdentity($user);

        if (! isset($this->roomParticipants[$roomName])) {
            $this->roomParticipants[$roomName] = [];
        }

        $this->roomParticipants[$roomName][] = [
            'identity' => $identity,
            'user_id' => $user->id,
            'name' => $user->name,
            'joined_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Simulate a participant leaving a room
     */
    public function simulateParticipantLeave(string $roomName, User $user): void
    {
        $identity = $this->formatUserIdentity($user);

        if (isset($this->roomParticipants[$roomName])) {
            $this->roomParticipants[$roomName] = array_filter(
                $this->roomParticipants[$roomName],
                fn ($p) => $p['identity'] !== $identity
            );
        }
    }

    /**
     * Generate a participant_joined webhook payload
     */
    public static function participantJoinedWebhook(string $roomName, string $participantIdentity, ?string $participantName = null): array
    {
        return [
            'event' => 'participant_joined',
            'room' => [
                'name' => $roomName,
                'sid' => 'RM_'.Str::random(16),
            ],
            'participant' => [
                'identity' => $participantIdentity,
                'name' => $participantName ?? $participantIdentity,
                'sid' => 'PA_'.Str::random(16),
                'state' => 'ACTIVE',
                'joined_at' => now()->timestamp,
            ],
            'created_at' => now()->timestamp,
            'id' => 'EV_'.Str::random(16),
        ];
    }

    /**
     * Generate a participant_left webhook payload
     */
    public static function participantLeftWebhook(string $roomName, string $participantIdentity, ?string $participantName = null): array
    {
        return [
            'event' => 'participant_left',
            'room' => [
                'name' => $roomName,
                'sid' => 'RM_'.Str::random(16),
            ],
            'participant' => [
                'identity' => $participantIdentity,
                'name' => $participantName ?? $participantIdentity,
                'sid' => 'PA_'.Str::random(16),
                'state' => 'DISCONNECTED',
            ],
            'created_at' => now()->timestamp,
            'id' => 'EV_'.Str::random(16),
        ];
    }

    /**
     * Generate a room_started webhook payload
     */
    public static function roomStartedWebhook(string $roomName): array
    {
        return [
            'event' => 'room_started',
            'room' => [
                'name' => $roomName,
                'sid' => 'RM_'.Str::random(16),
                'created_at' => now()->timestamp,
            ],
            'created_at' => now()->timestamp,
            'id' => 'EV_'.Str::random(16),
        ];
    }

    /**
     * Generate a room_finished webhook payload
     */
    public static function roomFinishedWebhook(string $roomName): array
    {
        return [
            'event' => 'room_finished',
            'room' => [
                'name' => $roomName,
                'sid' => 'RM_'.Str::random(16),
            ],
            'created_at' => now()->timestamp,
            'id' => 'EV_'.Str::random(16),
        ];
    }

    /**
     * Generate a mock webhook signature (for bypassing signature validation in tests)
     */
    public static function generateMockSignature(array $payload, string $secret = 'test-secret'): string
    {
        $payloadJson = json_encode($payload);

        return base64_encode(hash_hmac('sha256', $payloadJson, $secret, true));
    }

    // ===========================================
    // ASSERTION HELPERS
    // ===========================================

    /**
     * Assert that a room was created
     */
    public function assertRoomCreated(string $roomName): void
    {
        if (! isset($this->createdRooms[$roomName])) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Room '{$roomName}' was not created. Created rooms: ".implode(', ', array_keys($this->createdRooms))
            );
        }
    }

    /**
     * Assert that a token was generated for a user
     */
    public function assertTokenGeneratedFor(User $user): void
    {
        $found = false;
        foreach ($this->generatedTokens as $tokenData) {
            if ($tokenData['user_id'] === $user->id) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "No token was generated for user ID {$user->id}"
            );
        }
    }

    /**
     * Assert that a webhook was processed
     */
    public function assertWebhookProcessed(string $eventType): void
    {
        $found = false;
        foreach ($this->processedWebhooks as $webhook) {
            if (($webhook['data']['event'] ?? null) === $eventType) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Webhook event '{$eventType}' was not processed"
            );
        }
    }

    /**
     * Get the number of rooms created
     */
    public function getRoomCount(): int
    {
        return count($this->createdRooms);
    }

    /**
     * Get the number of tokens generated
     */
    public function getTokenCount(): int
    {
        return count($this->generatedTokens);
    }

    /**
     * Reset mock state
     */
    public function reset(): void
    {
        $this->createdRooms = [];
        $this->generatedTokens = [];
        $this->processedWebhooks = [];
        $this->activeRecordings = [];
        $this->roomParticipants = [];
        $this->nextTokenResponse = null;
        $this->shouldFailTokenGeneration = false;
        $this->shouldFailRoomCreation = false;
        $this->isConfigured = true;
    }
}
