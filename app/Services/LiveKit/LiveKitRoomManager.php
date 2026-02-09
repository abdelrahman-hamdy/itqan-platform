<?php

namespace App\Services\LiveKit;

use Agence104\LiveKit\RoomCreateOptions;
use Agence104\LiveKit\RoomServiceClient;
use App\Models\Academy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LiveKitRoomManager
{
    private ?RoomServiceClient $roomService = null;

    private ?string $serverUrl;

    public function __construct()
    {
        $apiKey = config('livekit.api_key', '');
        $apiSecret = config('livekit.api_secret', '');
        $this->serverUrl = config('livekit.server_url');

        // Use configured API URL for backend API calls
        $apiUrl = config('livekit.api_url', str_replace('wss://', 'http://', $this->serverUrl));

        // Only initialize RoomServiceClient if we have valid credentials
        if ($apiKey && $apiSecret && $apiUrl) {
            Log::debug('Initializing LiveKit RoomServiceClient', [
                'api_url' => $apiUrl,
                'api_key' => $apiKey,
            ]);
            $this->roomService = new RoomServiceClient($apiUrl, $apiKey, $apiSecret);
        }
    }

    /**
     * Check if room manager is properly configured
     */
    public function isConfigured(): bool
    {
        return $this->roomService !== null;
    }

    /**
     * Create a LiveKit room with the given options
     */
    public function createRoom(string $roomName, array $options = []): array
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit room manager not configured properly');
            }

            $roomOptions = $this->buildRoomOptions($roomName, $options);

            Log::info('Creating LiveKit room', [
                'room_name' => $roomName,
                'options' => $options,
            ]);

            $room = $this->roomService->createRoom($roomOptions);

            return [
                'room_name' => $room->getName(),
                'room_sid' => $room->getSid(),
                'created_at' => Carbon::createFromTimestamp($room->getCreationTime()),
                'max_participants' => $room->getMaxParticipants(),
                'metadata' => json_decode($room->getMetadata(), true),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create LiveKit room', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
            ]);

            throw new \Exception('Failed to create room: '.$e->getMessage());
        }
    }

    /**
     * Delete a LiveKit room
     */
    public function deleteRoom(string $roomName): bool
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit room manager not configured properly');
            }

            $this->roomService->deleteRoom($roomName);

            Log::info('Deleted LiveKit room', [
                'room_name' => $roomName,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete LiveKit room', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
            ]);

            return false;
        }
    }

    /**
     * List all rooms or filter by names
     */
    public function listRooms(array $roomNames = []): array
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit room manager not configured properly');
            }

            $roomsResponse = $this->roomService->listRooms($roomNames);
            $rooms = [];

            if ($roomsResponse && method_exists($roomsResponse, 'getRooms')) {
                $roomsList = $roomsResponse->getRooms();

                foreach ($roomsList as $room) {
                    $rooms[] = [
                        'room_name' => $room->getName(),
                        'room_sid' => $room->getSid(),
                        'participant_count' => $room->getNumParticipants(),
                        'created_at' => Carbon::createFromTimestamp($room->getCreationTime()),
                        'max_participants' => $room->getMaxParticipants(),
                        'is_active' => $room->getNumParticipants() > 0,
                    ];
                }
            }

            return $rooms;

        } catch (\Exception $e) {
            Log::error('Failed to list LiveKit rooms', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get room information and current participants
     */
    public function getRoomInfo(string $roomName): ?array
    {
        try {
            if (! $this->isConfigured()) {
                Log::warning('LiveKit room manager not configured properly');

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
     * Get participants in a room
     */
    public function getParticipants(string $roomName): array
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit room manager not configured properly');
            }

            $participantsResponse = $this->roomService->listParticipants($roomName);
            $participants = [];

            if ($participantsResponse && method_exists($participantsResponse, 'getParticipants')) {
                $participantsList = $participantsResponse->getParticipants();

                foreach ($participantsList as $participant) {
                    $participants[] = [
                        'identity' => $participant->getIdentity(),
                        'name' => $participant->getName() ?: $participant->getIdentity(),
                        'joined_at' => Carbon::createFromTimestamp($participant->getJoinedAt()),
                        'is_publisher' => $participant->getPermission() ? $participant->getPermission()->getCanPublish() : false,
                        'metadata' => json_decode($participant->getMetadata(), true),
                    ];
                }
            }

            return $participants;

        } catch (\Exception $e) {
            Log::error('Failed to get participants', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
            ]);

            return [];
        }
    }

    /**
     * Remove a participant from a room
     */
    public function removeParticipant(string $roomName, string $participantIdentity): bool
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit room manager not configured properly');
            }

            $this->roomService->removeParticipant($roomName, $participantIdentity);

            Log::info('Removed participant from room', [
                'room_name' => $roomName,
                'participant_identity' => $participantIdentity,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to remove participant', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
                'participant_identity' => $participantIdentity,
            ]);

            return false;
        }
    }

    /**
     * Check if a user is currently in a room
     */
    public function isUserInRoom(string $roomName, string $userIdentity): bool
    {
        try {
            if (! $this->isConfigured()) {
                Log::warning('LiveKit not configured - cannot check user presence', [
                    'room_name' => $roomName,
                    'user_identity' => $userIdentity,
                ]);

                return false;
            }

            Log::debug('Checking LiveKit room for user', [
                'room_name' => $roomName,
                'user_identity' => $userIdentity,
            ]);

            // Query LiveKit API directly for participants in this room
            $participantsResponse = $this->roomService->listParticipants($roomName);

            if (! $participantsResponse) {
                Log::debug('No participants response from LiveKit', [
                    'room_name' => $roomName,
                ]);

                return false;
            }

            // Check if our user is in the participants list
            if (method_exists($participantsResponse, 'getParticipants')) {
                $participants = $participantsResponse->getParticipants();

                Log::debug('LiveKit participants in room', [
                    'room_name' => $roomName,
                    'total_participants' => count($participants),
                ]);

                foreach ($participants as $participant) {
                    $participantIdentity = $participant->getIdentity();

                    if ($participantIdentity === $userIdentity) {
                        Log::info('User found in LiveKit room', [
                            'room_name' => $roomName,
                            'user_identity' => $userIdentity,
                        ]);

                        return true;
                    }
                }

                Log::debug('User not in LiveKit room', [
                    'room_name' => $roomName,
                    'user_identity' => $userIdentity,
                    'participants_checked' => count($participants),
                ]);
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Error checking LiveKit room', [
                'room_name' => $roomName,
                'user_identity' => $userIdentity,
                'error' => $e->getMessage(),
            ]);

            // On error, assume user is NOT in room (fail-safe)
            return false;
        }
    }

    /**
     * Update room metadata
     */
    public function updateRoomMetadata(string $roomName, array $metadata): bool
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit room manager not configured properly');
            }

            $this->roomService->updateRoomMetadata($roomName, json_encode($metadata));

            Log::info('Updated room metadata', [
                'room_name' => $roomName,
                'metadata' => $metadata,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update room metadata', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
            ]);

            return false;
        }
    }

    /**
     * End meeting by removing all participants and deleting room
     */
    public function endMeeting(string $roomName): bool
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit room manager not configured properly');
            }

            // Disconnect all participants
            $participantsResponse = $this->roomService->listParticipants($roomName);

            if ($participantsResponse && method_exists($participantsResponse, 'getParticipants')) {
                foreach ($participantsResponse->getParticipants() as $participant) {
                    $this->roomService->removeParticipant($roomName, $participant->getIdentity());
                }
            }

            // Delete room
            $this->roomService->deleteRoom($roomName);

            Log::info('LiveKit room ended and deleted', [
                'room_name' => $roomName,
            ]);

            return true;

        } catch (\Exception $e) {
            // If the room doesn't exist, consider it already ended (not an error)
            if (str_contains($e->getMessage(), 'room does not exist')) {
                Log::info('LiveKit room already gone, treating as ended', [
                    'room_name' => $roomName,
                ]);

                return true;
            }

            Log::error('Failed to end meeting', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
            ]);

            return false;
        }
    }

    /**
     * Set meeting duration by updating room metadata
     */
    public function setMeetingDuration(string $roomName, int $durationMinutes): bool
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit room manager not configured properly');
            }

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
     * Generate deterministic room name for a session
     */
    public function generateRoomName(Academy $academy, string $sessionType, int $sessionId): string
    {
        $academySlug = Str::slug($academy->subdomain);
        $sessionSlug = Str::slug($sessionType);

        // Use deterministic naming based on session ID only - NO timestamp
        return "{$academySlug}-{$sessionSlug}-session-{$sessionId}";
    }

    /**
     * Build room creation options
     */
    private function buildRoomOptions(string $roomName, array $options): RoomCreateOptions
    {
        return (new RoomCreateOptions)
            ->setName($roomName)
            ->setMaxParticipants($options['max_participants'] ?? 100)
            ->setEmptyTimeout($options['empty_timeout'] ?? 300)
            ->setMetadata(json_encode([
                'created_by' => 'itqan_platform',
                'session_type' => $options['session_type'] ?? 'quran',
                'recording_enabled' => $options['recording_enabled'] ?? false,
                'created_at' => now()->toISOString(),
                'dynacast_enabled' => true,
            ]));
    }
}
