<?php

namespace App\Services;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\RoomCreateOptions;
use Agence104\LiveKit\RoomServiceClient;
use Agence104\LiveKit\VideoGrant;
use App\Models\Academy;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Enums\SessionStatus;

class LiveKitService
{
    private ?string $apiKey;

    private ?string $apiSecret;

    private ?string $serverUrl;

    private ?RoomServiceClient $roomService = null;

    public function __construct()
    {
        $this->apiKey = config('livekit.api_key', '');
        $this->apiSecret = config('livekit.api_secret', '');
        $this->serverUrl = config('livekit.server_url', 'wss://localhost'); // For frontend connections

        // Use configured API URL for backend API calls
        // Note: Use HTTP (not HTTPS) when using self-signed SSL certificates to avoid verification issues
        // Browser WebSocket connections use wss:// through Nginx SSL proxy
        $apiUrl = config('livekit.api_url', str_replace('wss://', 'http://', $this->serverUrl));

        // Only initialize RoomServiceClient if we have valid credentials
        if ($this->apiKey && $this->apiSecret && $apiUrl) {
            Log::debug('Initializing LiveKit RoomServiceClient', [
                'api_url' => $apiUrl,
                'api_key' => $this->apiKey,
            ]);
            $this->roomService = new RoomServiceClient($apiUrl, $this->apiKey, $this->apiSecret);
        }
    }

    /**
     * Check if LiveKit is properly configured
     */
    public function isConfigured(): bool
    {
        return $this->roomService !== null &&
               ! empty($this->apiKey) &&
               ! empty($this->apiSecret) &&
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
        $roomName = $this->generateRoomName($academy, $sessionType, $sessionId);

        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit is not properly configured. Please check API credentials.');
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

        } catch (\Exception $e) {
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
     */
    public function generateParticipantToken(
        string $roomName,
        User $user,
        array $permissions = []
    ): string {
        try {
            // Create participant identity and metadata with Arabic name
            $participantIdentity = $user->id.'_'.Str::slug($user->first_name.'_'.$user->last_name);
            $metadata = json_encode([
                'name' => $user->name, // Full Arabic name
                'role' => $this->isTeacher($user) ? 'teacher' : 'student',
                'user_id' => $user->id,
            ]);

            $tokenOptions = (new AccessTokenOptions)
                ->setIdentity($participantIdentity)
                ->setMetadata($metadata)
                ->setTtl(3600 * 3); // 3 hours

            $videoGrant = (new VideoGrant)
                ->setRoomJoin()
                ->setRoomName($roomName)
                ->setCanPublish($permissions['can_publish'] ?? true)
                ->setCanSubscribe($permissions['can_subscribe'] ?? true);

            // Additional permissions for teachers/admins
            if ($this->isTeacher($user) || $this->isAdmin($user)) {
                $videoGrant->setRoomAdmin();
            }

            $token = (new AccessToken($this->apiKey, $this->apiSecret))
                ->init($tokenOptions)
                ->setGrant($videoGrant)
                ->toJwt();

            Log::info('Generated LiveKit access token', [
                'user_id' => $user->id,
                'room_name' => $roomName,
                'permissions' => $permissions,
            ]);

            return $token;

        } catch (\Exception $e) {
            Log::error('Failed to generate LiveKit token', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'room_name' => $roomName,
            ]);

            throw new \Exception('Failed to generate access token: '.$e->getMessage());
        }
    }

    /**
     * Start recording for a room using LiveKit Egress
     *
     * @param string $roomName The LiveKit room name to record
     * @param array $options Recording configuration options
     * @return array Egress response with egress_id
     * @throws \Exception If recording cannot be started
     */
    public function startRecording(string $roomName, array $options = []): array
    {
        try {
            if (!$this->isConfigured()) {
                throw new \Exception('LiveKit service not configured properly');
            }

            // Get API URL for backend calls
            $apiUrl = config('livekit.api_url', str_replace('wss://', 'http://', $this->serverUrl));

            // Build file path for local storage on LiveKit server
            $filename = $options['filename'] ?? sprintf('recording-%s-%s', $roomName, now()->timestamp);
            $filepath = sprintf('%s/%s.mp4',
                rtrim($options['storage_path'] ?? '/recordings', '/'),
                $filename
            );

            // Prepare Egress request payload for room composite recording
            $payload = [
                'room_name' => $roomName,
                'file' => [
                    'filepath' => $filepath,
                ],
                'options' => [
                    'preset' => $options['preset'] ?? 'HD',
                    'layout' => $options['layout'] ?? 'grid',
                ],
            ];

            // Add metadata if provided
            if (!empty($options['metadata'])) {
                $payload['metadata'] = json_encode($options['metadata']);
            }

            Log::info('Starting LiveKit Egress recording', [
                'room_name' => $roomName,
                'filepath' => $filepath,
                'api_url' => $apiUrl,
            ]);

            // Call LiveKit Egress Twirp API (StartRoomCompositeEgress)
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->generateEgressToken(),
                'Content-Type' => 'application/json',
            ])->post($apiUrl . '/twirp/livekit.Egress/StartRoomCompositeEgress', $payload);

            if (!$response->successful()) {
                throw new \Exception('Egress API error: ' . $response->body());
            }

            $responseData = $response->json();

            Log::info('Recording started successfully', [
                'room_name' => $roomName,
                'egress_id' => $responseData['egressId'] ?? null,
            ]);

            return [
                'egress_id' => $responseData['egressId'] ?? $responseData['egress_id'] ?? null,
                'room_name' => $roomName,
                'filepath' => $filepath,
                'response' => $responseData,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to start recording', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
                'options' => $options,
            ]);

            throw new \Exception('Failed to start recording: ' . $e->getMessage());
        }
    }

    /**
     * Stop an active recording
     *
     * @param string $egressId The LiveKit egress ID to stop
     * @return bool Whether recording was stopped successfully
     * @throws \Exception If recording cannot be stopped
     */
    public function stopRecording(string $egressId): bool
    {
        try {
            if (!$this->isConfigured()) {
                throw new \Exception('LiveKit service not configured properly');
            }

            // Get API URL for backend calls
            $apiUrl = config('livekit.api_url', str_replace('wss://', 'http://', $this->serverUrl));

            Log::info('Stopping LiveKit Egress recording', [
                'egress_id' => $egressId,
                'api_url' => $apiUrl,
            ]);

            // Call LiveKit Egress Twirp API (StopEgress)
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->generateEgressToken(),
                'Content-Type' => 'application/json',
            ])->post($apiUrl . '/twirp/livekit.Egress/StopEgress', [
                'egress_id' => $egressId,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Egress API error: ' . $response->body());
            }

            Log::info('Recording stopped successfully', [
                'egress_id' => $egressId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to stop recording', [
                'error' => $e->getMessage(),
                'egress_id' => $egressId,
            ]);

            throw new \Exception('Failed to stop recording: ' . $e->getMessage());
        }
    }

    /**
     * Generate JWT token for Egress API calls
     *
     * @return string JWT token
     */
    protected function generateEgressToken(): string
    {
        // For LiveKit Egress API, we can use the same API key/secret as access tokens
        // Create a JWT with canCreateEgress grant
        $tokenOptions = (new AccessTokenOptions)
            ->setIdentity('egress-service')
            ->setTtl(3600); // 1 hour

        // Add video grant with recording/egress permissions
        $grant = new VideoGrant();
        $grant->setRoomRecord(true);
        $grant->setRoomAdmin(true);

        $token = new AccessToken($this->apiKey, $this->apiSecret, $tokenOptions);
        $token->setGrant($grant);

        return $token->toJwt();
    }

    /**
     * Get room information and current participants
     */
    public function getRoomInfo(string $roomName): ?array
    {
        try {
            if (! $this->isConfigured()) {
                Log::warning('LiveKit service not configured properly');

                return null;
            }

            $roomsResponse = $this->roomService->listRooms([$roomName]);

            // Get rooms array from response
            $rooms = [];
            if ($roomsResponse && method_exists($roomsResponse, 'getRooms')) {
                $rooms = $roomsResponse->getRooms();
            }

            Log::info('LiveKit listRooms response', [
                'room_name' => $roomName,
                'rooms_count' => count($rooms),
                'server_url' => $this->serverUrl,
            ]);

            if (empty($rooms)) {
                Log::warning('Room not found on LiveKit server', [
                    'room_name' => $roomName,
                    'server_url' => $this->serverUrl,
                ]);

                return null;
            }

            $room = $rooms[0];
            $participantsResponse = $this->roomService->listParticipants($roomName);

            // Convert participants response to array
            $participants = [];
            if ($participantsResponse && method_exists($participantsResponse, 'getParticipants')) {
                $participantsList = $participantsResponse->getParticipants();
                foreach ($participantsList as $participant) {
                    $participants[] = [
                        'id' => $participant->getIdentity(),
                        'name' => $participant->getName() ?: $participant->getIdentity(),
                        'joined_at' => Carbon::createFromTimestamp($participant->getJoinedAt()),
                        'is_publisher' => $participant->getPermission() ? $participant->getPermission()->getCanPublish() : false,
                    ];
                }
            }

            return [
                'room_name' => $room->getName(),
                'room_sid' => $room->getSid(),
                'participant_count' => $room->getNumParticipants(),
                'created_at' => Carbon::createFromTimestamp($room->getCreationTime()),
                'participants' => $participants,
                'is_active' => $room->getNumParticipants() > 0,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get room info', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
            ]);

            return null;
        }
    }

    /**
     * End meeting and clean up room
     */
    public function endMeeting(string $roomName): bool
    {
        try {
            // Disconnect all participants
            $participants = $this->roomService->listParticipants($roomName);
            foreach ($participants as $participant) {
                $this->roomService->removeParticipant($roomName, $participant->getIdentity());
            }

            // Delete room
            $this->roomService->deleteRoom($roomName);

            Log::info('LiveKit room ended and deleted', [
                'room_name' => $roomName,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to end meeting', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
            ]);

            return false;
        }
    }

    /**
     * Control meeting duration by setting room timeout
     */
    public function setMeetingDuration(string $roomName, int $durationMinutes): bool
    {
        try {
            // Update room with timeout settings
            $this->roomService->updateRoomMetadata($roomName, json_encode([
                'max_duration' => $durationMinutes * 60,
                'timeout_at' => now()->addMinutes($durationMinutes)->timestamp,
            ]));

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to set meeting duration', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
                'duration_minutes' => $durationMinutes,
            ]);

            return false;
        }
    }

    /**
     * Handle webhooks from LiveKit server
     */
    public function handleWebhook(array $webhookData): void
    {
        $event = $webhookData['event'] ?? '';

        Log::info('LiveKit webhook received', [
            'event' => $event,
            'data' => $webhookData,
        ]);

        switch ($event) {
            case 'room_started':
                $this->handleRoomStarted($webhookData);
                break;

            case 'room_finished':
                $this->handleRoomFinished($webhookData);
                break;

            case 'participant_joined':
                $this->handleParticipantJoined($webhookData);
                break;

            case 'participant_left':
                $this->handleParticipantLeft($webhookData);
                break;

            case 'recording_finished':
                $this->handleRecordingFinished($webhookData);
                break;
        }
    }

    // Private helper methods

    private function generateRoomName(Academy $academy, string $sessionType, int $sessionId): string
    {
        $academySlug = Str::slug($academy->subdomain);
        $sessionSlug = Str::slug($sessionType);

        // Use deterministic naming based on session ID only - NO timestamp
        return "{$academySlug}-{$sessionSlug}-session-{$sessionId}";
    }

    private function generateMeetingUrl(string $roomName): string
    {
        // This would be your frontend meeting room URL
        return config('app.url')."/meeting/{$roomName}";
    }

    private function createRoomOptionsObject(string $roomName, array $options): RoomCreateOptions
    {
        return (new RoomCreateOptions)
            ->setName($roomName)
            ->setMaxParticipants($options['max_participants'] ?? 100)  // Updated from 50 to 100
            ->setEmptyTimeout($options['empty_timeout'] ?? 300)
            ->setMetadata(json_encode([
                'created_by' => 'itqan_platform',
                'session_type' => $options['session_type'] ?? 'quran',
                'recording_enabled' => $options['recording_enabled'] ?? false,
                'created_at' => now()->toISOString(),
                // Dynacast (selective layer forwarding) is enabled client-side in connection options
                // This reduces server CPU by 60-70% by only forwarding quality layers each client needs
                'dynacast_enabled' => true,
            ]));
    }

    private function buildRoomOptions(string $roomName, array $options): RoomCreateOptions
    {
        return (new RoomCreateOptions)
            ->setName($roomName)
            ->setMaxParticipants($options['max_participants'] ?? 100)  // Updated from 50 to 100
            ->setEmptyTimeout($options['empty_timeout'] ?? 300)
            ->setMetadata(json_encode([
                'created_by' => 'itqan_platform',
                'session_type' => $options['session_type'] ?? 'quran',
                'recording_enabled' => $options['recording_enabled'] ?? false,
                'created_at' => now()->toISOString(),
                'dynacast_enabled' => true,
            ]));
    }

    private function isTeacher(User $user): bool
    {
        return in_array($user->user_type, ['quran_teacher', 'academic_teacher']);
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->user_type, ['admin', 'super_admin']);
    }

    private function handleRoomStarted(array $data): void
    {
        // Update session status when room starts
        // You can implement custom logic here
    }

    private function handleRoomFinished(array $data): void
    {
        // Update session as completed, calculate duration, etc.
        // You can implement custom logic here
    }

    private function handleParticipantJoined(array $data): void
    {
        // Track attendance, send notifications, etc.
        // You can implement custom logic here
    }

    private function handleParticipantLeft(array $data): void
    {
        // Update attendance records, handle early departures, etc.
        // You can implement custom logic here
    }

    private function handleRecordingFinished(array $data): void
    {
        // Process completed recordings, save to database, notify users, etc.
        // You can implement custom logic here
    }

    /**
     * 🔥 SIMPLE SOLUTION: Check if user is currently in LiveKit room by querying the API directly
     * This is the SOURCE OF TRUTH - no webhooks or database needed!
     *
     * @param string $roomName The room name (e.g., "session-96")
     * @param string $userIdentity The participant identity (e.g., "5_ameer_maher")
     * @return bool True if user is currently in the room, false otherwise
     */
    public function isUserInRoom(string $roomName, string $userIdentity): bool
    {
        try {
            if (!$this->isConfigured()) {
                Log::warning('LiveKit not configured - cannot check user presence', [
                    'room_name' => $roomName,
                    'user_identity' => $userIdentity,
                ]);
                return false;
            }

            Log::info('🔍 Checking LiveKit room for user', [
                'room_name' => $roomName,
                'user_identity' => $userIdentity,
            ]);

            // Query LiveKit API directly for participants in this room
            $participantsResponse = $this->roomService->listParticipants($roomName);

            if (!$participantsResponse) {
                Log::info('❌ No participants response from LiveKit', [
                    'room_name' => $roomName,
                ]);
                return false;
            }

            // Check if our user is in the participants list
            if (method_exists($participantsResponse, 'getParticipants')) {
                $participants = $participantsResponse->getParticipants();

                Log::info('📊 LiveKit participants in room', [
                    'room_name' => $roomName,
                    'total_participants' => count($participants),
                ]);

                foreach ($participants as $participant) {
                    $participantIdentity = $participant->getIdentity();

                    Log::debug('Checking participant', [
                        'participant_identity' => $participantIdentity,
                        'looking_for' => $userIdentity,
                        'match' => $participantIdentity === $userIdentity,
                    ]);

                    if ($participantIdentity === $userIdentity) {
                        Log::info('✅ USER FOUND IN LIVEKIT ROOM!', [
                            'room_name' => $roomName,
                            'user_identity' => $userIdentity,
                            'participant_name' => $participant->getName(),
                            'joined_at' => $participant->getJoinedAt(),
                        ]);
                        return true;
                    }
                }

                Log::info('❌ User NOT in LiveKit room', [
                    'room_name' => $roomName,
                    'user_identity' => $userIdentity,
                    'participants_checked' => count($participants),
                ]);
            }

            return false;

        } catch (\Exception $e) {
            Log::error('❌ Error checking LiveKit room', [
                'room_name' => $roomName,
                'user_identity' => $userIdentity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // On error, assume user is NOT in room (fail-safe)
            return false;
        }
    }
}
