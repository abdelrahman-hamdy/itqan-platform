<?php

use App\Models\Academy;
use App\Models\User;
use App\Services\LiveKitService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

describe('LiveKitService', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();

        // Set up LiveKit configuration
        Config::set('livekit.api_key', 'test-api-key');
        Config::set('livekit.api_secret', 'test-api-secret');
        Config::set('livekit.server_url', 'wss://livekit.test');
        Config::set('livekit.api_url', 'http://livekit.test');
        Config::set('app.url', 'http://localhost');

        $this->service = new LiveKitService();
    });

    describe('isConfigured()', function () {
        it('returns true when all credentials are configured', function () {
            expect($this->service->isConfigured())->toBeTrue();
        });

        it('returns false when api_key is missing', function () {
            Config::set('livekit.api_key', '');
            $service = new LiveKitService();

            expect($service->isConfigured())->toBeFalse();
        });

        it('returns false when api_secret is missing', function () {
            Config::set('livekit.api_secret', '');
            $service = new LiveKitService();

            expect($service->isConfigured())->toBeFalse();
        });

        it('returns false when server_url is missing', function () {
            Config::set('livekit.server_url', '');
            $service = new LiveKitService();

            expect($service->isConfigured())->toBeFalse();
        });
    });

    describe('createMeeting()', function () {
        it('creates meeting data with room name and server url', function () {
            $sessionId = 123;
            $sessionType = 'quran_session';
            $startTime = Carbon::now()->addHour();

            $result = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result)->toBeArray()
                ->and($result['platform'])->toBe('livekit')
                ->and($result['room_name'])->toContain('session-'.$sessionId)
                ->and($result['server_url'])->toBe('wss://livekit.test')
                ->and($result['auto_create_on_join'])->toBeTrue();
        });

        it('generates deterministic room name based on academy and session', function () {
            $sessionId = 456;
            $sessionType = 'academic_session';
            $startTime = Carbon::now()->addHour();

            $result1 = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            $result2 = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result1['room_name'])->toBe($result2['room_name']);
        });

        it('includes default settings when no options provided', function () {
            $sessionId = 789;
            $sessionType = 'quran_session';
            $startTime = Carbon::now()->addHour();

            $result = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result['settings'])->toBeArray()
                ->and($result['settings']['max_participants'])->toBe(100)
                ->and($result['settings']['recording_enabled'])->toBeFalse()
                ->and($result['settings']['empty_timeout'])->toBe(300)
                ->and($result['settings']['max_duration'])->toBe(7200);
        });

        it('includes custom settings when options provided', function () {
            $sessionId = 999;
            $sessionType = 'quran_session';
            $startTime = Carbon::now()->addHour();

            $result = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime,
                [
                    'max_participants' => 50,
                    'recording_enabled' => true,
                    'auto_record' => true,
                    'empty_timeout' => 600,
                    'max_duration' => 3600,
                ]
            );

            expect($result['settings']['max_participants'])->toBe(50)
                ->and($result['settings']['recording_enabled'])->toBeTrue()
                ->and($result['settings']['auto_record'])->toBeTrue()
                ->and($result['settings']['empty_timeout'])->toBe(600)
                ->and($result['settings']['max_duration'])->toBe(3600);
        });

        it('includes all required features', function () {
            $sessionId = 111;
            $sessionType = 'quran_session';
            $startTime = Carbon::now()->addHour();

            $result = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result['features'])->toBeArray()
                ->and($result['features']['video'])->toBeTrue()
                ->and($result['features']['audio'])->toBeTrue()
                ->and($result['features']['screen_sharing'])->toBeTrue()
                ->and($result['features']['chat'])->toBeTrue()
                ->and($result['features']['recording'])->toBeTrue();
        });

        it('includes scheduled time and expiration', function () {
            $sessionId = 222;
            $sessionType = 'quran_session';
            $startTime = Carbon::parse('2025-12-25 10:00:00');

            $result = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result['scheduled_at'])->toBeInstanceOf(Carbon::class)
                ->and($result['scheduled_at']->equalTo($startTime))->toBeTrue()
                ->and($result['expires_at'])->toBeInstanceOf(Carbon::class);
        });

        it('generates meeting url with room name', function () {
            $sessionId = 333;
            $sessionType = 'quran_session';
            $startTime = Carbon::now()->addHour();

            $result = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result['meeting_url'])->toContain('/meeting/')
                ->and($result['meeting_url'])->toContain($result['room_name']);
        });

        it('throws exception when not configured', function () {
            Config::set('livekit.api_key', '');
            $service = new LiveKitService();

            $sessionId = 444;
            $sessionType = 'quran_session';
            $startTime = Carbon::now()->addHour();

            expect(fn () => $service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            ))->toThrow(Exception::class);
        });
    });

    describe('generateParticipantToken()', function () {
        it('generates valid JWT token for student', function () {
            $roomName = 'test-room-123';
            $user = User::factory()->student()->create();

            $token = $this->service->generateParticipantToken($roomName, $user);

            expect($token)->toBeString()
                ->and(strlen($token))->toBeGreaterThan(0);
        });

        it('generates valid JWT token for quran teacher', function () {
            $roomName = 'test-room-456';
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $token = $this->service->generateParticipantToken($roomName, $user);

            expect($token)->toBeString()
                ->and(strlen($token))->toBeGreaterThan(0);
        });

        it('generates valid JWT token for academic teacher', function () {
            $roomName = 'test-room-789';
            $user = User::factory()->academicTeacher()->forAcademy($this->academy)->create();

            $token = $this->service->generateParticipantToken($roomName, $user);

            expect($token)->toBeString()
                ->and(strlen($token))->toBeGreaterThan(0);
        });

        it('generates valid JWT token for admin', function () {
            $roomName = 'test-room-admin';
            $user = User::factory()->admin()->create();

            $token = $this->service->generateParticipantToken($roomName, $user);

            expect($token)->toBeString()
                ->and(strlen($token))->toBeGreaterThan(0);
        });

        it('accepts custom permissions for participant', function () {
            $roomName = 'test-room-permissions';
            $user = User::factory()->student()->create();

            $token = $this->service->generateParticipantToken($roomName, $user, [
                'can_publish' => false,
                'can_subscribe' => true,
            ]);

            expect($token)->toBeString()
                ->and(strlen($token))->toBeGreaterThan(0);
        });

        it('throws exception on token generation failure', function () {
            Config::set('livekit.api_key', 'invalid-key');
            Config::set('livekit.api_secret', 'invalid');
            $service = new LiveKitService();

            $roomName = 'test-room-fail';
            $user = User::factory()->student()->create();

            expect(fn () => $service->generateParticipantToken($roomName, $user))
                ->toThrow(Exception::class);
        });
    });

    describe('startRecording()', function () {
        it('starts recording with default options', function () {
            Http::fake([
                '*/twirp/livekit.Egress/StartRoomCompositeEgress' => Http::response([
                    'egressId' => 'egress-123',
                    'roomName' => 'test-room',
                ], 200),
            ]);

            $roomName = 'test-room';
            $result = $this->service->startRecording($roomName);

            expect($result)->toBeArray()
                ->and($result['egress_id'])->toBe('egress-123')
                ->and($result['room_name'])->toBe($roomName);
        });

        it('starts recording with custom filename', function () {
            Http::fake([
                '*/twirp/livekit.Egress/StartRoomCompositeEgress' => Http::response([
                    'egressId' => 'egress-456',
                ], 200),
            ]);

            $roomName = 'test-room-custom';
            $result = $this->service->startRecording($roomName, [
                'filename' => 'my-custom-recording',
            ]);

            expect($result['filepath'])->toContain('my-custom-recording.mp4');
        });

        it('starts recording with custom storage path', function () {
            Http::fake([
                '*/twirp/livekit.Egress/StartRoomCompositeEgress' => Http::response([
                    'egressId' => 'egress-789',
                ], 200),
            ]);

            $roomName = 'test-room-path';
            $result = $this->service->startRecording($roomName, [
                'storage_path' => '/custom/path',
            ]);

            expect($result['filepath'])->toStartWith('/custom/path/');
        });

        it('includes metadata when provided', function () {
            Http::fake([
                '*/twirp/livekit.Egress/StartRoomCompositeEgress' => Http::response([
                    'egressId' => 'egress-meta',
                ], 200),
            ]);

            $roomName = 'test-room-meta';
            $result = $this->service->startRecording($roomName, [
                'metadata' => [
                    'session_id' => 123,
                    'teacher_id' => 456,
                ],
            ]);

            expect($result['egress_id'])->toBe('egress-meta');
        });

        it('throws exception when API returns error', function () {
            Http::fake([
                '*/twirp/livekit.Egress/StartRoomCompositeEgress' => Http::response([
                    'error' => 'Room not found',
                ], 404),
            ]);

            $roomName = 'non-existent-room';

            expect(fn () => $this->service->startRecording($roomName))
                ->toThrow(Exception::class);
        });

        it('throws exception when not configured', function () {
            Config::set('livekit.api_key', '');
            $service = new LiveKitService();

            $roomName = 'test-room';

            expect(fn () => $service->startRecording($roomName))
                ->toThrow(Exception::class, 'LiveKit service not configured properly');
        });
    });

    describe('stopRecording()', function () {
        it('stops recording successfully', function () {
            Http::fake([
                '*/twirp/livekit.Egress/StopEgress' => Http::response([], 200),
            ]);

            $egressId = 'egress-123';
            $result = $this->service->stopRecording($egressId);

            expect($result)->toBeTrue();
        });

        it('throws exception when API returns error', function () {
            Http::fake([
                '*/twirp/livekit.Egress/StopEgress' => Http::response([
                    'error' => 'Egress not found',
                ], 404),
            ]);

            $egressId = 'non-existent-egress';

            expect(fn () => $this->service->stopRecording($egressId))
                ->toThrow(Exception::class);
        });

        it('throws exception when not configured', function () {
            Config::set('livekit.api_key', '');
            $service = new LiveKitService();

            $egressId = 'egress-123';

            expect(fn () => $service->stopRecording($egressId))
                ->toThrow(Exception::class, 'LiveKit service not configured properly');
        });
    });

    describe('getRoomInfo()', function () {
        it('returns null when not configured', function () {
            Config::set('livekit.api_key', '');
            $service = new LiveKitService();

            Log::shouldReceive('warning')->once();

            $result = $service->getRoomInfo('test-room');

            expect($result)->toBeNull();
        });

        it('returns null when room does not exist', function () {
            Log::shouldReceive('info')->atLeast()->once();
            Log::shouldReceive('warning')->once();

            $result = $this->service->getRoomInfo('non-existent-room');

            expect($result)->toBeNull();
        });
    });

    describe('endMeeting()', function () {
        it('returns false on error', function () {
            Log::shouldReceive('error')->once();

            $result = $this->service->endMeeting('non-existent-room');

            expect($result)->toBeFalse();
        });
    });

    describe('setMeetingDuration()', function () {
        it('returns false on error', function () {
            Log::shouldReceive('error')->once();

            $result = $this->service->setMeetingDuration('non-existent-room', 60);

            expect($result)->toBeFalse();
        });
    });

    describe('handleWebhook()', function () {
        it('handles room_started event', function () {
            Log::shouldReceive('info')->atLeast()->once();

            $webhookData = [
                'event' => 'room_started',
                'room' => [
                    'name' => 'test-room',
                    'sid' => 'room-123',
                ],
            ];

            $this->service->handleWebhook($webhookData);

            expect(true)->toBeTrue();
        });

        it('handles room_finished event', function () {
            Log::shouldReceive('info')->atLeast()->once();

            $webhookData = [
                'event' => 'room_finished',
                'room' => [
                    'name' => 'test-room',
                    'sid' => 'room-123',
                ],
            ];

            $this->service->handleWebhook($webhookData);

            expect(true)->toBeTrue();
        });

        it('handles participant_joined event', function () {
            Log::shouldReceive('info')->atLeast()->once();

            $webhookData = [
                'event' => 'participant_joined',
                'participant' => [
                    'identity' => 'user-123',
                    'name' => 'Test User',
                ],
            ];

            $this->service->handleWebhook($webhookData);

            expect(true)->toBeTrue();
        });

        it('handles participant_left event', function () {
            Log::shouldReceive('info')->atLeast()->once();

            $webhookData = [
                'event' => 'participant_left',
                'participant' => [
                    'identity' => 'user-123',
                    'name' => 'Test User',
                ],
            ];

            $this->service->handleWebhook($webhookData);

            expect(true)->toBeTrue();
        });

        it('handles recording_finished event', function () {
            Log::shouldReceive('info')->atLeast()->once();

            $webhookData = [
                'event' => 'recording_finished',
                'egress_info' => [
                    'egress_id' => 'egress-123',
                    'file_path' => '/recordings/test.mp4',
                ],
            ];

            $this->service->handleWebhook($webhookData);

            expect(true)->toBeTrue();
        });

        it('handles unknown event gracefully', function () {
            Log::shouldReceive('info')->atLeast()->once();

            $webhookData = [
                'event' => 'unknown_event',
                'data' => [],
            ];

            $this->service->handleWebhook($webhookData);

            expect(true)->toBeTrue();
        });
    });

    describe('isUserInRoom()', function () {
        it('returns false when not configured', function () {
            Config::set('livekit.api_key', '');
            $service = new LiveKitService();

            Log::shouldReceive('warning')->once();

            $result = $service->isUserInRoom('test-room', 'user-123');

            expect($result)->toBeFalse();
        });

        it('returns false when room service fails', function () {
            Log::shouldReceive('info')->atLeast()->once();
            Log::shouldReceive('error')->once();

            $result = $this->service->isUserInRoom('non-existent-room', 'user-123');

            expect($result)->toBeFalse();
        });

        it('returns false when participants response is null', function () {
            Log::shouldReceive('info')->atLeast()->once();

            $result = $this->service->isUserInRoom('empty-room', 'user-123');

            expect($result)->toBeFalse();
        });
    });

    describe('room name generation', function () {
        it('generates consistent room names for same inputs', function () {
            $sessionId = 123;
            $sessionType = 'quran_session';
            $startTime = Carbon::now()->addHour();

            $result1 = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            $result2 = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result1['room_name'])->toBe($result2['room_name']);
        });

        it('includes academy subdomain in room name', function () {
            $sessionId = 456;
            $sessionType = 'academic_session';
            $startTime = Carbon::now()->addHour();

            $result = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result['room_name'])->toContain(\Illuminate\Support\Str::slug($this->academy->subdomain));
        });

        it('includes session type in room name', function () {
            $sessionId = 789;
            $sessionType = 'quran_session';
            $startTime = Carbon::now()->addHour();

            $result = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result['room_name'])->toContain(\Illuminate\Support\Str::slug($sessionType));
        });

        it('includes session id in room name', function () {
            $sessionId = 999;
            $sessionType = 'academic_session';
            $startTime = Carbon::now()->addHour();

            $result = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result['room_name'])->toContain((string) $sessionId);
        });
    });

    describe('join info structure', function () {
        it('includes server url in join info', function () {
            $sessionId = 111;
            $sessionType = 'quran_session';
            $startTime = Carbon::now()->addHour();

            $result = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result['join_info'])->toBeArray()
                ->and($result['join_info']['server_url'])->toBe('wss://livekit.test');
        });

        it('includes room name in join info', function () {
            $sessionId = 222;
            $sessionType = 'quran_session';
            $startTime = Carbon::now()->addHour();

            $result = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result['join_info']['room_name'])->toBe($result['room_name']);
        });

        it('specifies token-based access method', function () {
            $sessionId = 333;
            $sessionType = 'quran_session';
            $startTime = Carbon::now()->addHour();

            $result = $this->service->createMeeting(
                $this->academy,
                $sessionType,
                $sessionId,
                $startTime
            );

            expect($result['join_info']['access_method'])->toBe('token_based');
        });
    });
});
