<?php

use App\Contracts\RecordingCapable;
use App\Models\SessionRecording;
use App\Services\LiveKitService;
use App\Services\RecordingService;
use Illuminate\Support\Facades\Log;

describe('RecordingService', function () {
    beforeEach(function () {
        $this->liveKitService = Mockery::mock(LiveKitService::class);
        $this->service = new RecordingService($this->liveKitService);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('startRecording()', function () {
        it('throws exception when session cannot be recorded', function () {
            $session = Mockery::mock(RecordingCapable::class);
            $session->shouldReceive('canBeRecorded')->once()->andReturn(false);

            expect(fn () => $this->service->startRecording($session))
                ->toThrow(Exception::class, 'Session cannot be recorded at this time');
        });

        it('throws exception when session is already recording', function () {
            $session = Mockery::mock(RecordingCapable::class);
            $session->shouldReceive('canBeRecorded')->once()->andReturn(true);
            $session->shouldReceive('isRecording')->once()->andReturn(true);

            expect(fn () => $this->service->startRecording($session))
                ->toThrow(Exception::class, 'Session is already being recorded');
        });

        it('creates recording record when successfully started', function () {
            $session = Mockery::mock(RecordingCapable::class);
            $session->id = 123;
            $session->shouldReceive('canBeRecorded')->once()->andReturn(true);
            $session->shouldReceive('isRecording')->once()->andReturn(false);
            $session->shouldReceive('getRecordingConfiguration')->once()->andReturn([
                'room_name' => 'test-room',
                'preset' => 'HD',
                'metadata' => ['session_id' => 123],
            ]);

            $this->liveKitService->shouldReceive('startRecording')
                ->once()
                ->with('test-room', [
                    'room_name' => 'test-room',
                    'preset' => 'HD',
                    'metadata' => ['session_id' => 123],
                ])
                ->andReturn([
                    'egress_id' => 'egress-123',
                ]);

            Log::shouldReceive('info')->once();

            $recording = $this->service->startRecording($session);

            expect($recording)->toBeInstanceOf(SessionRecording::class)
                ->and($recording->recording_id)->toBe('egress-123')
                ->and($recording->meeting_room)->toBe('test-room')
                ->and($recording->status)->toBe('recording')
                ->and($recording->file_format)->toBe('mp4')
                ->and($recording->started_at)->not->toBeNull();
        });

        it('sets file format to m4a for audio only recordings', function () {
            $session = Mockery::mock(RecordingCapable::class);
            $session->id = 123;
            $session->shouldReceive('canBeRecorded')->once()->andReturn(true);
            $session->shouldReceive('isRecording')->once()->andReturn(false);
            $session->shouldReceive('getRecordingConfiguration')->once()->andReturn([
                'room_name' => 'test-room',
                'preset' => 'AUDIO_ONLY',
                'metadata' => [],
            ]);

            $this->liveKitService->shouldReceive('startRecording')
                ->once()
                ->andReturn(['egress_id' => 'egress-audio']);

            Log::shouldReceive('info')->once();

            $recording = $this->service->startRecording($session);

            expect($recording->file_format)->toBe('m4a');
        });

        it('logs error and throws exception on failure', function () {
            $session = Mockery::mock(RecordingCapable::class);
            $session->id = 123;
            $session->shouldReceive('canBeRecorded')->once()->andReturn(true);
            $session->shouldReceive('isRecording')->once()->andReturn(false);
            $session->shouldReceive('getRecordingConfiguration')->once()->andReturn([
                'room_name' => 'test-room',
                'preset' => 'HD',
            ]);

            $this->liveKitService->shouldReceive('startRecording')
                ->once()
                ->andThrow(new Exception('LiveKit error'));

            Log::shouldReceive('error')->once();

            expect(fn () => $this->service->startRecording($session))
                ->toThrow(Exception::class, 'Failed to start recording: LiveKit error');
        });
    });

    describe('stopRecording()', function () {
        it('returns false when recording is not active', function () {
            $recording = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-test',
                'meeting_room' => 'test-room',
                'status' => 'completed',
                'started_at' => now(),
            ]);

            Log::shouldReceive('warning')->once();

            $result = $this->service->stopRecording($recording);

            expect($result)->toBeFalse();
        });

        it('stops recording and marks as processing when successful', function () {
            $recording = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-123',
                'meeting_room' => 'test-room',
                'status' => 'recording',
                'started_at' => now(),
            ]);

            $this->liveKitService->shouldReceive('stopRecording')
                ->once()
                ->with('egress-123')
                ->andReturn(true);

            Log::shouldReceive('info')->once();

            $result = $this->service->stopRecording($recording);

            expect($result)->toBeTrue();

            $recording->refresh();
            expect($recording->status)->toBe('processing');
        });

        it('returns false when LiveKit service fails to stop', function () {
            $recording = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-fail',
                'meeting_room' => 'test-room',
                'status' => 'recording',
                'started_at' => now(),
            ]);

            $this->liveKitService->shouldReceive('stopRecording')
                ->once()
                ->with('egress-fail')
                ->andReturn(false);

            $result = $this->service->stopRecording($recording);

            expect($result)->toBeFalse();
        });

        it('logs error and returns false on exception', function () {
            $recording = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-error',
                'meeting_room' => 'test-room',
                'status' => 'recording',
                'started_at' => now(),
            ]);

            $this->liveKitService->shouldReceive('stopRecording')
                ->once()
                ->andThrow(new Exception('Connection failed'));

            Log::shouldReceive('error')->once();

            $result = $this->service->stopRecording($recording);

            expect($result)->toBeFalse();
        });
    });

    describe('processEgressWebhook()', function () {
        it('returns false for non-egress_ended events', function () {
            $webhookData = [
                'event' => 'egress_started',
            ];

            $result = $this->service->processEgressWebhook($webhookData);

            expect($result)->toBeFalse();
        });

        it('returns false when egressId is missing', function () {
            $webhookData = [
                'event' => 'egress_ended',
                'egressInfo' => [],
            ];

            Log::shouldReceive('warning')->once();

            $result = $this->service->processEgressWebhook($webhookData);

            expect($result)->toBeFalse();
        });

        it('returns false when recording not found', function () {
            $webhookData = [
                'event' => 'egress_ended',
                'egressInfo' => [
                    'egressId' => 'non-existent-egress',
                ],
            ];

            Log::shouldReceive('warning')->once();

            $result = $this->service->processEgressWebhook($webhookData);

            expect($result)->toBeFalse();
        });

        it('marks recording as completed for successful egress', function () {
            $recording = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-success',
                'meeting_room' => 'test-room',
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $webhookData = [
                'event' => 'egress_ended',
                'egressInfo' => [
                    'egressId' => 'egress-success',
                    'status' => 'EGRESS_COMPLETE',
                    'fileResults' => [
                        [
                            'filename' => '/recordings/test.mp4',
                            'size' => 1024000,
                            'duration' => 3600,
                        ],
                    ],
                ],
            ];

            Log::shouldReceive('info')->once();

            $result = $this->service->processEgressWebhook($webhookData);

            expect($result)->toBeTrue();

            $recording->refresh();
            expect($recording->status)->toBe('completed')
                ->and($recording->file_path)->toBe('/recordings/test.mp4')
                ->and($recording->file_size)->toBe(1024000)
                ->and($recording->duration)->toBe(3600);
        });

        it('marks recording as failed for failed egress', function () {
            $recording = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-failed',
                'meeting_room' => 'test-room',
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $webhookData = [
                'event' => 'egress_ended',
                'egressInfo' => [
                    'egressId' => 'egress-failed',
                    'status' => 'EGRESS_FAILED',
                    'error' => 'Recording failed: disk full',
                ],
            ];

            Log::shouldReceive('error')->once();

            $result = $this->service->processEgressWebhook($webhookData);

            expect($result)->toBeTrue();

            $recording->refresh();
            expect($recording->status)->toBe('failed')
                ->and($recording->processing_error)->toBe('Recording failed: disk full');
        });

        it('returns false when recording not found for webhook', function () {
            $webhookData = [
                'event' => 'egress_ended',
                'egressInfo' => [
                    'egressId' => 'non-existent',
                    'status' => 'EGRESS_COMPLETE',
                ],
            ];

            Log::shouldReceive('warning')->once();

            $result = $this->service->processEgressWebhook($webhookData);

            expect($result)->toBeFalse();
        });
    });

    describe('extractFileInfoFromWebhook()', function () {
        it('returns null values when file list is empty', function () {
            $egressInfo = [];

            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('extractFileInfoFromWebhook');
            $method->setAccessible(true);

            $result = $method->invoke($this->service, $egressInfo);

            expect($result)->toBe([
                'file_path' => null,
                'file_name' => null,
                'file_size' => null,
                'duration' => null,
            ]);
        });

        it('extracts file info from fileResults array', function () {
            $egressInfo = [
                'fileResults' => [
                    [
                        'filename' => '/recordings/session-123.mp4',
                        'size' => 2048000,
                        'duration' => 1800,
                    ],
                ],
            ];

            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('extractFileInfoFromWebhook');
            $method->setAccessible(true);

            $result = $method->invoke($this->service, $egressInfo);

            expect($result['file_path'])->toBe('/recordings/session-123.mp4')
                ->and($result['file_name'])->toBe('session-123.mp4')
                ->and($result['file_size'])->toBe(2048000)
                ->and($result['duration'])->toBe(1800);
        });

        it('extracts file info from file object', function () {
            $egressInfo = [
                'file' => [
                    'location' => '/recordings/test.webm',
                    'size' => 512000,
                ],
                'duration' => 900,
            ];

            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('extractFileInfoFromWebhook');
            $method->setAccessible(true);

            $result = $method->invoke($this->service, $egressInfo);

            expect($result['file_path'])->toBe('/recordings/test.webm')
                ->and($result['duration'])->toBe(900);
        });
    });

    describe('getSessionRecordings()', function () {
        it('returns session recordings collection', function () {
            $recording1 = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-1',
                'meeting_room' => 'test-room',
                'status' => 'completed',
                'started_at' => now(),
            ]);

            $recording2 = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-2',
                'meeting_room' => 'test-room',
                'status' => 'completed',
                'started_at' => now(),
            ]);

            $recordings = SessionRecording::whereIn('id', [$recording1->id, $recording2->id])->get();

            $session = Mockery::mock(RecordingCapable::class);
            $session->shouldReceive('getRecordings')->once()->andReturn($recordings);

            $result = $this->service->getSessionRecordings($session);

            expect($result)->toHaveCount(2)
                ->and($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
        });
    });

    describe('deleteRecording()', function () {
        it('marks recording as deleted', function () {
            $recording = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-delete',
                'meeting_room' => 'test-room',
                'status' => 'completed',
                'started_at' => now(),
            ]);

            Log::shouldReceive('info')->once();

            $result = $this->service->deleteRecording($recording);

            expect($result)->toBeTrue();

            $recording->refresh();
            expect($recording->status)->toBe('deleted');
        });

        it('logs note when remove file flag is set', function () {
            $recording = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-delete',
                'meeting_room' => 'test-room',
                'status' => 'completed',
                'started_at' => now(),
                'file_path' => '/recordings/test.mp4',
            ]);

            Log::shouldReceive('info')->twice();

            $result = $this->service->deleteRecording($recording, true);

            expect($result)->toBeTrue();
        });

        it('returns true even if recording already deleted', function () {
            $recording = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-already-deleted',
                'meeting_room' => 'test-room',
                'status' => 'deleted',
                'started_at' => now(),
            ]);

            Log::shouldReceive('info')->once();

            $result = $this->service->deleteRecording($recording);

            expect($result)->toBeTrue();
        });
    });

    describe('getRecordingStatistics()', function () {
        it('returns statistics for all recordings when no filters provided', function () {
            SessionRecording::query()->delete();

            SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-stats-1',
                'meeting_room' => 'room-1',
                'status' => 'completed',
                'started_at' => now(),
                'file_size' => 1024000,
                'duration' => 3600,
            ]);

            SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 2,
                'recording_id' => 'egress-stats-2',
                'meeting_room' => 'room-2',
                'status' => 'recording',
                'started_at' => now(),
            ]);

            $stats = $this->service->getRecordingStatistics();

            expect($stats['total_count'])->toBe(2)
                ->and($stats['completed_count'])->toBe(1)
                ->and($stats['recording_count'])->toBe(1)
                ->and($stats['total_size_bytes'])->toBe(1024000)
                ->and($stats['total_duration_seconds'])->toBe(3600);
        });

        it('filters statistics by session type', function () {
            SessionRecording::query()->delete();

            SessionRecording::create([
                'recordable_type' => 'App\Models\QuranSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-quran',
                'meeting_room' => 'room-1',
                'status' => 'completed',
                'started_at' => now(),
            ]);

            SessionRecording::create([
                'recordable_type' => 'App\Models\AcademicSession',
                'recordable_id' => 2,
                'recording_id' => 'egress-academic',
                'meeting_room' => 'room-2',
                'status' => 'completed',
                'started_at' => now(),
            ]);

            $stats = $this->service->getRecordingStatistics([
                'session_type' => 'App\Models\QuranSession',
            ]);

            expect($stats['total_count'])->toBe(1);
        });

        it('filters statistics by status', function () {
            SessionRecording::query()->delete();

            SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-completed',
                'meeting_room' => 'room-1',
                'status' => 'completed',
                'started_at' => now(),
            ]);

            SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 2,
                'recording_id' => 'egress-failed',
                'meeting_room' => 'room-2',
                'status' => 'failed',
                'started_at' => now(),
            ]);

            $stats = $this->service->getRecordingStatistics([
                'status' => 'failed',
            ]);

            expect($stats['total_count'])->toBe(1)
                ->and($stats['failed_count'])->toBe(1);
        });

        it('filters statistics by date range', function () {
            SessionRecording::query()->delete();

            $old = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-old',
                'meeting_room' => 'room-1',
                'status' => 'completed',
                'started_at' => now()->subDays(10),
            ]);
            $old->created_at = now()->subDays(10);
            $old->save();

            $new = SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 2,
                'recording_id' => 'egress-new',
                'meeting_room' => 'room-2',
                'status' => 'completed',
                'started_at' => now()->subDays(2),
            ]);
            $new->created_at = now()->subDays(2);
            $new->save();

            $stats = $this->service->getRecordingStatistics([
                'date_from' => now()->subDays(5),
            ]);

            expect($stats['total_count'])->toBe(1);
        });

        it('includes formatted size and duration', function () {
            SessionRecording::query()->delete();

            SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 1,
                'recording_id' => 'egress-formatted',
                'meeting_room' => 'room-1',
                'status' => 'completed',
                'started_at' => now(),
                'file_size' => 2097152,
                'duration' => 3661,
            ]);

            $stats = $this->service->getRecordingStatistics();

            expect($stats['total_size_formatted'])->toContain('MB')
                ->and($stats['total_duration_formatted'])->toBe('01:01:01');
        });

        it('calculates average file size and duration', function () {
            SessionRecording::query()->delete();

            SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 101,
                'recording_id' => 'egress-avg-1',
                'meeting_room' => 'room-1',
                'status' => 'completed',
                'started_at' => now(),
                'file_size' => 1000,
                'duration' => 100,
            ]);

            SessionRecording::create([
                'recordable_type' => 'App\Models\TestSession',
                'recordable_id' => 102,
                'recording_id' => 'egress-avg-2',
                'meeting_room' => 'room-2',
                'status' => 'completed',
                'started_at' => now(),
                'file_size' => 3000,
                'duration' => 300,
            ]);

            $stats = $this->service->getRecordingStatistics();

            expect($stats['average_file_size_bytes'])->toBe(2000)
                ->and($stats['average_duration_seconds'])->toBe(200);
        });
    });

    describe('formatBytes()', function () {
        it('formats zero bytes', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('formatBytes');
            $method->setAccessible(true);

            $result = $method->invoke($this->service, 0);

            expect($result)->toBe('0 B');
        });

        it('formats null as zero bytes', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('formatBytes');
            $method->setAccessible(true);

            $result = $method->invoke($this->service, null);

            expect($result)->toBe('0 B');
        });

        it('formats bytes correctly', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('formatBytes');
            $method->setAccessible(true);

            expect($method->invoke($this->service, 500))->toBe('500 B');
            expect($method->invoke($this->service, 2048))->toContain('KB');
            expect($method->invoke($this->service, 2097152))->toContain('MB');
            expect($method->invoke($this->service, 2147483648))->toContain('GB');
        });
    });

    describe('formatDuration()', function () {
        it('formats zero duration', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('formatDuration');
            $method->setAccessible(true);

            $result = $method->invoke($this->service, 0);

            expect($result)->toBe('00:00');
        });

        it('formats null as zero duration', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('formatDuration');
            $method->setAccessible(true);

            $result = $method->invoke($this->service, null);

            expect($result)->toBe('00:00');
        });

        it('formats duration in minutes and seconds', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('formatDuration');
            $method->setAccessible(true);

            expect($method->invoke($this->service, 30))->toBe('00:30')
                ->and($method->invoke($this->service, 90))->toBe('01:30')
                ->and($method->invoke($this->service, 3599))->toBe('59:59');
        });

        it('formats duration with hours when needed', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('formatDuration');
            $method->setAccessible(true);

            expect($method->invoke($this->service, 3600))->toBe('01:00:00')
                ->and($method->invoke($this->service, 3661))->toBe('01:01:01')
                ->and($method->invoke($this->service, 7322))->toBe('02:02:02');
        });
    });
});
