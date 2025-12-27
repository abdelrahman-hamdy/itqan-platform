<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Enums\SessionStatus;

/**
 * Service to manage LiveKit room permissions for microphone and camera
 * Uses Redis cache for fast permission checks during webhook processing
 */
class RoomPermissionService
{
    /**
     * Cache key prefix for room permissions
     */
    private const CACHE_PREFIX = 'livekit:room:permissions:';

    /**
     * Default permission state (all allowed)
     */
    private const DEFAULT_PERMISSIONS = [
        'microphone_allowed' => true,
        'camera_allowed' => true,
        'updated_at' => null,
        'updated_by' => null,
    ];

    /**
     * Cache TTL in seconds (24 hours)
     */
    private const CACHE_TTL = 86400;

    /**
     * Set microphone permission for a room
     *
     * @param string $roomName
     * @param bool $allowed
     * @param int|null $userId User ID of who changed the permission (teacher)
     * @return bool
     */
    public function setMicrophonePermission(string $roomName, bool $allowed, ?int $userId = null): bool
    {
        try {
            $permissions = $this->getRoomPermissions($roomName);
            $permissions['microphone_allowed'] = $allowed;
            $permissions['updated_at'] = now()->toIso8601String();
            $permissions['updated_by'] = $userId;

            $cacheKey = $this->getCacheKey($roomName);
            Cache::put($cacheKey, $permissions, self::CACHE_TTL);

            Log::info('Room microphone permission updated', [
                'room_name' => $roomName,
                'allowed' => $allowed,
                'updated_by' => $userId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to set microphone permission', [
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Set camera permission for a room
     *
     * @param string $roomName
     * @param bool $allowed
     * @param int|null $userId User ID of who changed the permission (teacher)
     * @return bool
     */
    public function setCameraPermission(string $roomName, bool $allowed, ?int $userId = null): bool
    {
        try {
            $permissions = $this->getRoomPermissions($roomName);
            $permissions['camera_allowed'] = $allowed;
            $permissions['updated_at'] = now()->toIso8601String();
            $permissions['updated_by'] = $userId;

            $cacheKey = $this->getCacheKey($roomName);
            Cache::put($cacheKey, $permissions, self::CACHE_TTL);

            Log::info('Room camera permission updated', [
                'room_name' => $roomName,
                'allowed' => $allowed,
                'updated_by' => $userId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to set camera permission', [
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get all permissions for a room
     *
     * @param string $roomName
     * @return array
     */
    public function getRoomPermissions(string $roomName): array
    {
        $cacheKey = $this->getCacheKey($roomName);

        return Cache::get($cacheKey, self::DEFAULT_PERMISSIONS);
    }

    /**
     * Check if microphone is allowed in a room
     *
     * @param string $roomName
     * @return bool
     */
    public function isMicrophoneAllowed(string $roomName): bool
    {
        $permissions = $this->getRoomPermissions($roomName);

        return $permissions['microphone_allowed'] ?? true;
    }

    /**
     * Check if camera is allowed in a room
     *
     * @param string $roomName
     * @return bool
     */
    public function isCameraAllowed(string $roomName): bool
    {
        $permissions = $this->getRoomPermissions($roomName);

        return $permissions['camera_allowed'] ?? true;
    }

    /**
     * Check if a specific track type is allowed
     *
     * @param string $roomName
     * @param string $trackType 'audio' or 'video'
     * @return bool
     */
    public function isTrackTypeAllowed(string $roomName, string $trackType): bool
    {
        if ($trackType === 'audio') {
            return $this->isMicrophoneAllowed($roomName);
        }

        if ($trackType === 'video') {
            return $this->isCameraAllowed($roomName);
        }

        // Unknown track type, allow by default
        return true;
    }

    /**
     * Clear permissions for a room (when room closes)
     *
     * @param string $roomName
     * @return bool
     */
    public function clearRoomPermissions(string $roomName): bool
    {
        try {
            $cacheKey = $this->getCacheKey($roomName);
            Cache::forget($cacheKey);

            Log::info('Room permissions cleared', [
                'room_name' => $roomName,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear room permissions', [
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get cache key for room permissions
     *
     * @param string $roomName
     * @return string
     */
    private function getCacheKey(string $roomName): string
    {
        return self::CACHE_PREFIX.$roomName;
    }

    /**
     * Initialize room permissions when room is created
     *
     * @param string $roomName
     * @param bool $micAllowed
     * @param bool $cameraAllowed
     * @return bool
     */
    public function initializeRoomPermissions(
        string $roomName,
        bool $micAllowed = true,
        bool $cameraAllowed = true
    ): bool {
        try {
            $permissions = [
                'microphone_allowed' => $micAllowed,
                'camera_allowed' => $cameraAllowed,
                'updated_at' => now()->toIso8601String(),
                'updated_by' => null,
            ];

            $cacheKey = $this->getCacheKey($roomName);
            Cache::put($cacheKey, $permissions, self::CACHE_TTL);

            Log::info('Room permissions initialized', [
                'room_name' => $roomName,
                'microphone_allowed' => $micAllowed,
                'camera_allowed' => $cameraAllowed,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to initialize room permissions', [
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
