<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\User;
use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use Agence104\LiveKit\RoomServiceClient;
use Agence104\LiveKit\RoomCreateOptions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $this->serverUrl = config('livekit.server_url', 'wss://localhost');
        
        // Only initialize RoomServiceClient if we have valid credentials
        if ($this->apiKey && $this->apiSecret && $this->serverUrl) {
            $this->roomService = new RoomServiceClient($this->serverUrl, $this->apiKey, $this->apiSecret);
        }
    }

    /**
     * Check if LiveKit is properly configured
     */
    public function isConfigured(): bool
    {
        return $this->roomService !== null && 
               !empty($this->apiKey) && 
               !empty($this->apiSecret) && 
               !empty($this->serverUrl);
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
        try {
            if (!$this->isConfigured()) {
                throw new \Exception('LiveKit is not properly configured. Please check API credentials.');
            }

            // Generate unique room name
            $roomName = $this->generateRoomName($academy, $sessionType, $sessionId);
            
            // Create room with custom settings
            $roomOptions = $this->createRoomOptionsObject($roomName, $options);
            
            Log::info('Attempting to create LiveKit room', [
                'room_name' => $roomName,
                'session_id' => $sessionId,
                'academy_id' => $academy->id,
                'max_participants' => $roomOptions->getMaxParticipants(),
                'empty_timeout' => $roomOptions->getEmptyTimeout(),
            ]);
            

            $room = $this->roomService->createRoom($roomOptions);
            
            Log::info('LiveKit room created successfully', [
                'room_name' => $roomName,
                'session_id' => $sessionId,
                'academy_id' => $academy->id,
                'room_sid' => $room->getSid(),
            ]);

            return [
                'platform' => 'livekit',
                'room_name' => $roomName,
                'room_sid' => $room->getSid(),
                'server_url' => $this->serverUrl,
                'meeting_id' => $roomName,
                'meeting_url' => $this->generateMeetingUrl($roomName),
                'join_info' => [
                    'server_url' => $this->serverUrl,
                    'room_name' => $roomName,
                    'access_method' => 'token_based',
                ],
                'settings' => [
                    'max_participants' => $options['max_participants'] ?? 50,
                    'recording_enabled' => $options['recording_enabled'] ?? false,
                    'auto_record' => $options['auto_record'] ?? false,
                    'empty_timeout' => $options['empty_timeout'] ?? 300, // 5 minutes
                    'max_duration' => $options['max_duration'] ?? 7200, // 2 hours
                ],
                'features' => [
                    'video' => true,
                    'audio' => true,
                    'screen_sharing' => true,
                    'chat' => true,
                    'recording' => true,
                    'breakout_rooms' => false, // Can be enabled with custom implementation
                    'whiteboard' => false, // Can be added with third-party integration
                ],
                'created_at' => now(),
                'scheduled_at' => $startTime,
                'expires_at' => $options['expires_at'] ?? $startTime->copy()->addHours(3),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create LiveKit room', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'academy_id' => $academy->id,
            ]);
            
            throw new \Exception('Failed to create meeting room: ' . $e->getMessage());
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
            $tokenOptions = (new AccessTokenOptions())
                ->setIdentity($user->id . '_' . Str::slug($user->first_name . '_' . $user->last_name))
                ->setTtl(3600 * 3); // 3 hours
            
            $videoGrant = (new VideoGrant())
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
            
            throw new \Exception('Failed to generate access token: ' . $e->getMessage());
        }
    }

    /**
     * Start recording for a room
     */
    public function startRecording(string $roomName, array $options = []): array
    {
        try {
            // Configure recording options
            $recordingConfig = [
                'room_name' => $roomName,
                'layout' => $options['layout'] ?? 'grid',
                'audio_only' => $options['audio_only'] ?? false,
                'video_quality' => $options['video_quality'] ?? 'high',
                'file_outputs' => [
                    [
                        'file_type' => 'MP4',
                        'storage' => [
                            'type' => 's3',
                            'bucket' => config('livekit.recording.s3_bucket'),
                            'region' => config('livekit.recording.s3_region'),
                        ]
                    ]
                ]
            ];

            // Recording functionality not yet implemented in this SDK version
            Log::info('Recording requested for LiveKit room', [
                'room_name' => $roomName,
                'config' => $recordingConfig,
            ]);

            throw new \Exception('Recording functionality not yet implemented. Please use LiveKit Dashboard or API directly.');

        } catch (\Exception $e) {
            Log::error('Failed to start recording', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
            ]);
            
            throw new \Exception('Failed to start recording: ' . $e->getMessage());
        }
    }

    /**
     * Stop recording and get file location
     */
    public function stopRecording(string $recordingId): array
    {
        try {
            // Recording functionality not yet implemented in this SDK version
            Log::info('Recording stop requested', [
                'recording_id' => $recordingId,
            ]);
            
            throw new \Exception('Recording functionality not yet implemented. Please use LiveKit Dashboard or API directly.');

        } catch (\Exception $e) {
            Log::error('Failed to stop recording', [
                'error' => $e->getMessage(),
                'recording_id' => $recordingId,
            ]);
            
            throw new \Exception('Failed to stop recording: ' . $e->getMessage());
        }
    }

    /**
     * Get room information and current participants
     */
    public function getRoomInfo(string $roomName): ?array
    {
        try {
            $roomsResponse = $this->roomService->listRooms([$roomName]);
            
            // Get rooms array from response
            $rooms = [];
            if ($roomsResponse && method_exists($roomsResponse, 'getRooms')) {
                $rooms = $roomsResponse->getRooms();
            }
            
            if (empty($rooms)) {
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
        $timestamp = now()->format('YmdHi');
        
        return "{$academySlug}-{$sessionSlug}-{$sessionId}-{$timestamp}";
    }

    private function generateMeetingUrl(string $roomName): string
    {
        // This would be your frontend meeting room URL
        return config('app.url') . "/meeting/{$roomName}";
    }

    private function createRoomOptionsObject(string $roomName, array $options): RoomCreateOptions
    {
        return (new RoomCreateOptions())
            ->setName($roomName)
            ->setMaxParticipants($options['max_participants'] ?? 50)
            ->setEmptyTimeout($options['empty_timeout'] ?? 300)
            ->setMetadata(json_encode([
                'created_by' => 'itqan_platform',
                'session_type' => $options['session_type'] ?? 'quran',
                'recording_enabled' => $options['recording_enabled'] ?? false,
                'created_at' => now()->toISOString(),
            ]));
    }

    private function buildRoomOptions(string $roomName, array $options): RoomCreateOptions
    {
        return (new RoomCreateOptions())
            ->setName($roomName)
            ->setMaxParticipants($options['max_participants'] ?? 50)
            ->setEmptyTimeout($options['empty_timeout'] ?? 300)
            ->setMetadata(json_encode([
                'created_by' => 'itqan_platform',
                'session_type' => $options['session_type'] ?? 'quran',
                'recording_enabled' => $options['recording_enabled'] ?? false,
                'created_at' => now()->toISOString(),
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
}
