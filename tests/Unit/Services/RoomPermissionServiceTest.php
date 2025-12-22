<?php

use App\Services\RoomPermissionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

describe('RoomPermissionService', function () {
    beforeEach(function () {
        $this->service = new RoomPermissionService();
        $this->roomName = 'test-room-'.uniqid();
        Cache::flush();
    });

    describe('setMicrophonePermission()', function () {
        it('sets microphone permission to allowed', function () {
            $result = $this->service->setMicrophonePermission($this->roomName, true);

            expect($result)->toBeTrue();

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['microphone_allowed'])->toBeTrue();
        });

        it('sets microphone permission to disallowed', function () {
            $result = $this->service->setMicrophonePermission($this->roomName, false);

            expect($result)->toBeTrue();

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['microphone_allowed'])->toBeFalse();
        });

        it('stores user id when provided', function () {
            $userId = 123;
            $this->service->setMicrophonePermission($this->roomName, true, $userId);

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['updated_by'])->toBe($userId);
        });

        it('stores null user id when not provided', function () {
            $this->service->setMicrophonePermission($this->roomName, true);

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['updated_by'])->toBeNull();
        });

        it('stores timestamp when permission is updated', function () {
            $this->service->setMicrophonePermission($this->roomName, true);

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['updated_at'])->not->toBeNull()
                ->and($permissions['updated_at'])->toBeString();
        });

        it('preserves camera permission when updating microphone', function () {
            // Set camera permission first
            $this->service->setCameraPermission($this->roomName, false);

            // Update microphone permission
            $this->service->setMicrophonePermission($this->roomName, true);

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['camera_allowed'])->toBeFalse()
                ->and($permissions['microphone_allowed'])->toBeTrue();
        });

        it('logs info when permission is updated', function () {
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return $message === 'Room microphone permission updated'
                        && $context['room_name'] === $this->roomName
                        && $context['allowed'] === true;
                });

            $this->service->setMicrophonePermission($this->roomName, true);
        });

        it('returns false and logs error on cache exception', function () {
            Cache::shouldReceive('get')
                ->once()
                ->andThrow(new \Exception('Cache error'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Failed to set microphone permission')
                        && $context['room_name'] === $this->roomName;
                });

            $result = $this->service->setMicrophonePermission($this->roomName, true);

            expect($result)->toBeFalse();
        });
    });

    describe('setCameraPermission()', function () {
        it('sets camera permission to allowed', function () {
            $result = $this->service->setCameraPermission($this->roomName, true);

            expect($result)->toBeTrue();

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['camera_allowed'])->toBeTrue();
        });

        it('sets camera permission to disallowed', function () {
            $result = $this->service->setCameraPermission($this->roomName, false);

            expect($result)->toBeTrue();

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['camera_allowed'])->toBeFalse();
        });

        it('stores user id when provided', function () {
            $userId = 456;
            $this->service->setCameraPermission($this->roomName, false, $userId);

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['updated_by'])->toBe($userId);
        });

        it('stores null user id when not provided', function () {
            $this->service->setCameraPermission($this->roomName, false);

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['updated_by'])->toBeNull();
        });

        it('stores timestamp when permission is updated', function () {
            $this->service->setCameraPermission($this->roomName, false);

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['updated_at'])->not->toBeNull()
                ->and($permissions['updated_at'])->toBeString();
        });

        it('preserves microphone permission when updating camera', function () {
            // Set microphone permission first
            $this->service->setMicrophonePermission($this->roomName, false);

            // Update camera permission
            $this->service->setCameraPermission($this->roomName, true);

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['microphone_allowed'])->toBeFalse()
                ->and($permissions['camera_allowed'])->toBeTrue();
        });

        it('logs info when permission is updated', function () {
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return $message === 'Room camera permission updated'
                        && $context['room_name'] === $this->roomName
                        && $context['allowed'] === false;
                });

            $this->service->setCameraPermission($this->roomName, false);
        });

        it('returns false and logs error on cache exception', function () {
            Cache::shouldReceive('get')
                ->once()
                ->andThrow(new \Exception('Cache error'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Failed to set camera permission')
                        && $context['room_name'] === $this->roomName;
                });

            $result = $this->service->setCameraPermission($this->roomName, false);

            expect($result)->toBeFalse();
        });
    });

    describe('getRoomPermissions()', function () {
        it('returns default permissions for new room', function () {
            $permissions = $this->service->getRoomPermissions($this->roomName);

            expect($permissions)->toBeArray()
                ->and($permissions['microphone_allowed'])->toBeTrue()
                ->and($permissions['camera_allowed'])->toBeTrue()
                ->and($permissions['updated_at'])->toBeNull()
                ->and($permissions['updated_by'])->toBeNull();
        });

        it('returns cached permissions when they exist', function () {
            $this->service->setMicrophonePermission($this->roomName, false, 123);

            $permissions = $this->service->getRoomPermissions($this->roomName);

            expect($permissions['microphone_allowed'])->toBeFalse()
                ->and($permissions['camera_allowed'])->toBeTrue()
                ->and($permissions['updated_by'])->toBe(123);
        });

        it('returns all permission fields', function () {
            $permissions = $this->service->getRoomPermissions($this->roomName);

            expect($permissions)->toHaveKeys([
                'microphone_allowed',
                'camera_allowed',
                'updated_at',
                'updated_by',
            ]);
        });
    });

    describe('isMicrophoneAllowed()', function () {
        it('returns true by default for new room', function () {
            $result = $this->service->isMicrophoneAllowed($this->roomName);

            expect($result)->toBeTrue();
        });

        it('returns true when microphone is allowed', function () {
            $this->service->setMicrophonePermission($this->roomName, true);

            $result = $this->service->isMicrophoneAllowed($this->roomName);

            expect($result)->toBeTrue();
        });

        it('returns false when microphone is disallowed', function () {
            $this->service->setMicrophonePermission($this->roomName, false);

            $result = $this->service->isMicrophoneAllowed($this->roomName);

            expect($result)->toBeFalse();
        });

        it('returns true if permission key is missing', function () {
            // Manually set incomplete permissions
            $cacheKey = 'livekit:room:permissions:'.$this->roomName;
            Cache::put($cacheKey, ['camera_allowed' => true], 3600);

            $result = $this->service->isMicrophoneAllowed($this->roomName);

            expect($result)->toBeTrue();
        });
    });

    describe('isCameraAllowed()', function () {
        it('returns true by default for new room', function () {
            $result = $this->service->isCameraAllowed($this->roomName);

            expect($result)->toBeTrue();
        });

        it('returns true when camera is allowed', function () {
            $this->service->setCameraPermission($this->roomName, true);

            $result = $this->service->isCameraAllowed($this->roomName);

            expect($result)->toBeTrue();
        });

        it('returns false when camera is disallowed', function () {
            $this->service->setCameraPermission($this->roomName, false);

            $result = $this->service->isCameraAllowed($this->roomName);

            expect($result)->toBeFalse();
        });

        it('returns true if permission key is missing', function () {
            // Manually set incomplete permissions
            $cacheKey = 'livekit:room:permissions:'.$this->roomName;
            Cache::put($cacheKey, ['microphone_allowed' => true], 3600);

            $result = $this->service->isCameraAllowed($this->roomName);

            expect($result)->toBeTrue();
        });
    });

    describe('isTrackTypeAllowed()', function () {
        it('returns microphone permission for audio track', function () {
            $this->service->setMicrophonePermission($this->roomName, false);

            $result = $this->service->isTrackTypeAllowed($this->roomName, 'audio');

            expect($result)->toBeFalse();
        });

        it('returns camera permission for video track', function () {
            $this->service->setCameraPermission($this->roomName, false);

            $result = $this->service->isTrackTypeAllowed($this->roomName, 'video');

            expect($result)->toBeFalse();
        });

        it('returns true for unknown track type', function () {
            $this->service->setMicrophonePermission($this->roomName, false);
            $this->service->setCameraPermission($this->roomName, false);

            $result = $this->service->isTrackTypeAllowed($this->roomName, 'screen_share');

            expect($result)->toBeTrue();
        });

        it('handles audio track when microphone is allowed', function () {
            $this->service->setMicrophonePermission($this->roomName, true);

            $result = $this->service->isTrackTypeAllowed($this->roomName, 'audio');

            expect($result)->toBeTrue();
        });

        it('handles video track when camera is allowed', function () {
            $this->service->setCameraPermission($this->roomName, true);

            $result = $this->service->isTrackTypeAllowed($this->roomName, 'video');

            expect($result)->toBeTrue();
        });
    });

    describe('clearRoomPermissions()', function () {
        it('removes room permissions from cache', function () {
            // Set some permissions
            $this->service->setMicrophonePermission($this->roomName, false);
            $this->service->setCameraPermission($this->roomName, false);

            // Clear permissions
            $result = $this->service->clearRoomPermissions($this->roomName);

            expect($result)->toBeTrue();

            // Should return default permissions now
            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['microphone_allowed'])->toBeTrue()
                ->and($permissions['camera_allowed'])->toBeTrue()
                ->and($permissions['updated_at'])->toBeNull()
                ->and($permissions['updated_by'])->toBeNull();
        });

        it('logs info when permissions are cleared', function () {
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return $message === 'Room permissions cleared'
                        && $context['room_name'] === $this->roomName;
                });

            $this->service->clearRoomPermissions($this->roomName);
        });

        it('returns false and logs error on cache exception', function () {
            Cache::shouldReceive('forget')
                ->once()
                ->andThrow(new \Exception('Cache error'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Failed to clear room permissions')
                        && $context['room_name'] === $this->roomName;
                });

            $result = $this->service->clearRoomPermissions($this->roomName);

            expect($result)->toBeFalse();
        });

        it('returns true even if room had no permissions', function () {
            // Clear permissions for room that was never initialized
            $result = $this->service->clearRoomPermissions('never-existed-room');

            expect($result)->toBeTrue();
        });
    });

    describe('initializeRoomPermissions()', function () {
        it('initializes room with default permissions', function () {
            $result = $this->service->initializeRoomPermissions($this->roomName);

            expect($result)->toBeTrue();

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['microphone_allowed'])->toBeTrue()
                ->and($permissions['camera_allowed'])->toBeTrue();
        });

        it('initializes room with custom microphone permission', function () {
            $result = $this->service->initializeRoomPermissions($this->roomName, false, true);

            expect($result)->toBeTrue();

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['microphone_allowed'])->toBeFalse()
                ->and($permissions['camera_allowed'])->toBeTrue();
        });

        it('initializes room with custom camera permission', function () {
            $result = $this->service->initializeRoomPermissions($this->roomName, true, false);

            expect($result)->toBeTrue();

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['microphone_allowed'])->toBeTrue()
                ->and($permissions['camera_allowed'])->toBeFalse();
        });

        it('initializes room with both permissions disabled', function () {
            $result = $this->service->initializeRoomPermissions($this->roomName, false, false);

            expect($result)->toBeTrue();

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['microphone_allowed'])->toBeFalse()
                ->and($permissions['camera_allowed'])->toBeFalse();
        });

        it('sets updated_at timestamp', function () {
            $this->service->initializeRoomPermissions($this->roomName);

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['updated_at'])->not->toBeNull()
                ->and($permissions['updated_at'])->toBeString();
        });

        it('sets updated_by to null', function () {
            $this->service->initializeRoomPermissions($this->roomName);

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['updated_by'])->toBeNull();
        });

        it('logs info when room is initialized', function () {
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return $message === 'Room permissions initialized'
                        && $context['room_name'] === $this->roomName
                        && $context['microphone_allowed'] === true
                        && $context['camera_allowed'] === false;
                });

            $this->service->initializeRoomPermissions($this->roomName, true, false);
        });

        it('returns false and logs error on cache exception', function () {
            Cache::shouldReceive('put')
                ->once()
                ->andThrow(new \Exception('Cache error'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Failed to initialize room permissions')
                        && $context['room_name'] === $this->roomName;
                });

            $result = $this->service->initializeRoomPermissions($this->roomName);

            expect($result)->toBeFalse();
        });

        it('overwrites existing permissions', function () {
            // Set permissions first
            $this->service->setMicrophonePermission($this->roomName, false);
            $this->service->setCameraPermission($this->roomName, false);

            // Re-initialize with defaults
            $this->service->initializeRoomPermissions($this->roomName);

            $permissions = $this->service->getRoomPermissions($this->roomName);
            expect($permissions['microphone_allowed'])->toBeTrue()
                ->and($permissions['camera_allowed'])->toBeTrue();
        });
    });

    describe('cache key generation', function () {
        it('uses consistent cache key format', function () {
            $this->service->setMicrophonePermission($this->roomName, false);

            // Directly check cache with expected key
            $cacheKey = 'livekit:room:permissions:'.$this->roomName;
            $cached = Cache::get($cacheKey);

            expect($cached)->not->toBeNull()
                ->and($cached['microphone_allowed'])->toBeFalse();
        });

        it('isolates permissions by room name', function () {
            $room1 = 'room-1';
            $room2 = 'room-2';

            $this->service->setMicrophonePermission($room1, false);
            $this->service->setMicrophonePermission($room2, true);

            expect($this->service->isMicrophoneAllowed($room1))->toBeFalse()
                ->and($this->service->isMicrophoneAllowed($room2))->toBeTrue();
        });
    });

    describe('cache TTL', function () {
        it('stores permissions with 24 hour TTL', function () {
            Cache::shouldReceive('put')
                ->once()
                ->withArgs(function ($key, $value, $ttl) {
                    return $ttl === 86400; // 24 hours in seconds
                });

            $this->service->initializeRoomPermissions($this->roomName);
        });
    });

    describe('permission state transitions', function () {
        it('allows toggling microphone permission multiple times', function () {
            $this->service->setMicrophonePermission($this->roomName, false);
            expect($this->service->isMicrophoneAllowed($this->roomName))->toBeFalse();

            $this->service->setMicrophonePermission($this->roomName, true);
            expect($this->service->isMicrophoneAllowed($this->roomName))->toBeTrue();

            $this->service->setMicrophonePermission($this->roomName, false);
            expect($this->service->isMicrophoneAllowed($this->roomName))->toBeFalse();
        });

        it('allows toggling camera permission multiple times', function () {
            $this->service->setCameraPermission($this->roomName, false);
            expect($this->service->isCameraAllowed($this->roomName))->toBeFalse();

            $this->service->setCameraPermission($this->roomName, true);
            expect($this->service->isCameraAllowed($this->roomName))->toBeTrue();

            $this->service->setCameraPermission($this->roomName, false);
            expect($this->service->isCameraAllowed($this->roomName))->toBeFalse();
        });

        it('maintains independence between microphone and camera permissions', function () {
            $this->service->setMicrophonePermission($this->roomName, false);
            $this->service->setCameraPermission($this->roomName, true);

            expect($this->service->isMicrophoneAllowed($this->roomName))->toBeFalse()
                ->and($this->service->isCameraAllowed($this->roomName))->toBeTrue();

            $this->service->setMicrophonePermission($this->roomName, true);

            expect($this->service->isMicrophoneAllowed($this->roomName))->toBeTrue()
                ->and($this->service->isCameraAllowed($this->roomName))->toBeTrue();
        });
    });
});
