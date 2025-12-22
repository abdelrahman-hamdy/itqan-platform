<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\AcademySettings;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\LiveKitService;
use App\Services\SessionMeetingService;
use App\Services\UnifiedSessionStatusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

describe('SessionMeetingService', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->livekitService = Mockery::mock(LiveKitService::class);
        $this->statusService = Mockery::mock(UnifiedSessionStatusService::class);

        $this->service = new SessionMeetingService(
            $this->livekitService,
            $this->statusService
        );
    });

    afterEach(function () {
        Mockery::close();
        Cache::flush();
    });

    describe('getSessionType()', function () {
        it('returns quran as session type', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('getSessionType');
            $method->setAccessible(true);

            expect($method->invoke($this->service))->toBe('quran');
        });
    });

    describe('getMaxParticipants()', function () {
        it('returns 50 as max participants for quran sessions', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('getMaxParticipants');
            $method->setAccessible(true);

            expect($method->invoke($this->service))->toBe(50);
        });
    });

    describe('getSessionLabel()', function () {
        it('returns arabic label for messages', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('getSessionLabel');
            $method->setAccessible(true);

            expect($method->invoke($this->service))->toBe('الجلسة');
        });
    });

    describe('getCacheKeyPrefix()', function () {
        it('returns session_meeting as cache key prefix', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('getCacheKeyPrefix');
            $method->setAccessible(true);

            expect($method->invoke($this->service))->toBe('session_meeting');
        });
    });

    describe('ensureMeetingAvailable()', function () {
        it('throws exception when session is not available and force create is false', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now()->addHours(2),
                'duration_minutes' => 45,
            ]);

            AcademySettings::factory()->create([
                'academy_id' => $this->academy->id,
                'default_preparation_minutes' => 10,
            ]);

            expect(fn () => $this->service->ensureMeetingAvailable($session, false))
                ->toThrow(\Exception::class);
        });

        it('generates meeting link when session has no room name', function () {
            $session = QuranSession::factory()->ready()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->once()
                ->andReturn([
                    'room_name' => 'room-123',
                    'participant_count' => 0,
                    'is_active' => false,
                ]);

            $result = $this->service->ensureMeetingAvailable($session, true);

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['room_name', 'room_info', 'session_timing', 'join_url'])
                ->and($session->fresh()->meeting_room_name)->not->toBeNull();
        });

        it('recreates room when not found on server', function () {
            $session = QuranSession::factory()->ready()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'existing-room',
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->with('existing-room')
                ->once()
                ->andReturn(null);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->once()
                ->andReturn([
                    'room_name' => 'new-room',
                    'participant_count' => 0,
                    'is_active' => false,
                ]);

            Log::shouldReceive('info')
                ->with('Meeting room not found on server, recreating', Mockery::any())
                ->once();

            $result = $this->service->ensureMeetingAvailable($session, true);

            expect($result)->toBeArray()
                ->and($result)->toHaveKey('room_name');
        });

        it('returns complete meeting info with join url', function () {
            $session = QuranSession::factory()->ready()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'test-room',
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->once()
                ->andReturn([
                    'room_name' => 'test-room',
                    'participant_count' => 2,
                    'is_active' => true,
                ]);

            $result = $this->service->ensureMeetingAvailable($session, true);

            expect($result)->toHaveKey('join_url')
                ->and($result['join_url'])->toContain('student.sessions.show');
        });

        it('allows force creation regardless of availability', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
                'scheduled_at' => Carbon::now()->addDays(2),
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->once()
                ->andReturn([
                    'room_name' => 'forced-room',
                    'participant_count' => 0,
                    'is_active' => false,
                ]);

            $result = $this->service->ensureMeetingAvailable($session, true);

            expect($result)->toBeArray()
                ->and($session->fresh()->meeting_room_name)->not->toBeNull();
        });
    });

    describe('processScheduledSessions()', function () {
        it('creates meetings for ready sessions without meeting rooms', function () {
            QuranSession::factory()->ready()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->once()
                ->andReturn([
                    'room_name' => 'auto-room',
                    'participant_count' => 0,
                    'is_active' => false,
                ]);

            Log::shouldReceive('info')
                ->with('Auto-created meeting for ready Quran session', Mockery::any())
                ->once();

            $results = $this->service->processScheduledSessions();

            expect($results)->toHaveKey('started')
                ->and($results['started'])->toBe(1);
        });

        it('cleans up expired sessions', function () {
            $expiredSession = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'expired-room',
                'scheduled_at' => Carbon::now()->subHours(3),
            ]);

            $this->livekitService->shouldReceive('endMeeting')
                ->with('expired-room')
                ->once();

            $results = $this->service->processScheduledSessions();

            expect($results)->toHaveKey('cleaned')
                ->and($results['cleaned'])->toBeGreaterThanOrEqual(1);
        });

        it('tracks errors when meeting creation fails', function () {
            QuranSession::factory()->ready()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->once()
                ->andThrow(new \Exception('LiveKit error'));

            Log::shouldReceive('error')
                ->with('Failed to auto-create meeting for Quran session', Mockery::any())
                ->once();

            $results = $this->service->processScheduledSessions();

            expect($results)->toHaveKey('errors')
                ->and($results['errors'])->toBeGreaterThan(0);
        });

        it('returns results summary with all counters', function () {
            $results = $this->service->processScheduledSessions();

            expect($results)->toBeArray()
                ->and($results)->toHaveKeys(['started', 'updated', 'cleaned', 'errors']);
        });
    });

    describe('forceCreateMeeting()', function () {
        it('creates meeting regardless of timing constraints', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
                'scheduled_at' => Carbon::now()->addDays(5),
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->once()
                ->andReturn([
                    'room_name' => 'forced-room',
                    'participant_count' => 0,
                    'is_active' => false,
                ]);

            $result = $this->service->forceCreateMeeting($session);

            expect($result)->toBeArray()
                ->and($result)->toHaveKey('room_name');
        });

        it('delegates to ensureMeetingAvailable with force flag', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now()->addHours(10),
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->once()
                ->andReturn([
                    'room_name' => 'test-room',
                    'participant_count' => 0,
                    'is_active' => false,
                ]);

            $result = $this->service->forceCreateMeeting($session);

            expect($result)->toBeArray();
        });
    });

    describe('createMeetingsForReadySessions()', function () {
        it('creates meetings for all ready sessions without rooms', function () {
            QuranSession::factory()->ready()->count(3)->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            $results = $this->service->createMeetingsForReadySessions();

            expect($results)->toHaveKey('meetings_created')
                ->and($results['meetings_created'])->toBe(3)
                ->and($results['sessions_processed'])->toBe(3);
        });

        it('skips sessions that already have meeting rooms', function () {
            QuranSession::factory()->ready()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'existing-room',
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            $results = $this->service->createMeetingsForReadySessions();

            expect($results['meetings_created'])->toBe(0);
        });

        it('logs session details during processing', function () {
            $session = QuranSession::factory()->ready()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            Log::shouldReceive('info')
                ->with('Found ready sessions without meetings', Mockery::any())
                ->once();

            Log::shouldReceive('info')
                ->with('Creating meeting for ready session', Mockery::any())
                ->once();

            Log::shouldReceive('info')
                ->with('Meeting created for ready session', Mockery::any())
                ->once();

            $this->service->createMeetingsForReadySessions();
        });

        it('captures errors for individual session failures', function () {
            $session = QuranSession::factory()->ready()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            // Mock the session to throw error on generateMeetingLink
            QuranSession::creating(function () {
                throw new \Exception('Database error');
            });

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')
                ->with('Failed to create meeting for ready session', Mockery::any())
                ->once();

            $results = $this->service->createMeetingsForReadySessions();

            expect($results)->toHaveKey('errors')
                ->and($results['errors'])->not->toBeEmpty();
        });

        it('processes multiple sessions with mixed success', function () {
            QuranSession::factory()->ready()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            QuranSession::factory()->ready()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'existing-room',
                'scheduled_at' => Carbon::now()->addMinutes(10),
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();

            $results = $this->service->createMeetingsForReadySessions();

            expect($results)->toHaveKey('meetings_created')
                ->and($results)->toHaveKey('sessions_processed');
        });
    });

    describe('terminateExpiredMeetings()', function () {
        it('terminates meetings for completed sessions', function () {
            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'completed-room',
            ]);

            $this->livekitService->shouldReceive('endMeeting')
                ->with('completed-room')
                ->once()
                ->andReturn(true);

            Log::shouldReceive('info')
                ->with('Meeting terminated for completed Quran session', Mockery::any())
                ->once();

            $results = $this->service->terminateExpiredMeetings();

            expect($results)->toHaveKey('meetings_terminated')
                ->and($results['meetings_terminated'])->toBeGreaterThanOrEqual(1);
        });

        it('skips sessions without meeting rooms', function () {
            QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
            ]);

            $results = $this->service->terminateExpiredMeetings();

            expect($results['meetings_terminated'])->toBe(0);
        });

        it('handles termination errors gracefully', function () {
            QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'error-room',
            ]);

            $this->livekitService->shouldReceive('endMeeting')
                ->once()
                ->andThrow(new \Exception('Termination failed'));

            Log::shouldReceive('error')
                ->with('Failed to terminate meeting for completed Quran session', Mockery::any())
                ->once();

            $results = $this->service->terminateExpiredMeetings();

            expect($results)->toHaveKey('errors')
                ->and($results['errors'])->not->toBeEmpty();
        });

        it('processes all completed sessions with meetings', function () {
            QuranSession::factory()->completed()->count(3)->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => fn () => 'room-' . uniqid(),
            ]);

            $this->livekitService->shouldReceive('endMeeting')
                ->times(3)
                ->andReturn(true);

            Log::shouldReceive('info')->zeroOrMoreTimes();

            $results = $this->service->terminateExpiredMeetings();

            expect($results['sessions_processed'])->toBe(3);
        });

        it('returns complete results structure', function () {
            $results = $this->service->terminateExpiredMeetings();

            expect($results)->toBeArray()
                ->and($results)->toHaveKeys(['meetings_terminated', 'sessions_processed', 'errors']);
        });
    });

    describe('processSessionMeetings()', function () {
        it('combines creation and termination operations', function () {
            QuranSession::factory()->ready()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'completed-room',
            ]);

            $this->livekitService->shouldReceive('endMeeting')
                ->once()
                ->andReturn(true);

            Log::shouldReceive('info')->zeroOrMoreTimes();

            $results = $this->service->processSessionMeetings();

            expect($results)->toHaveKeys(['meetings_created', 'meetings_terminated', 'status_transitions', 'errors']);
        });

        it('returns empty results when no sessions need processing', function () {
            $results = $this->service->processSessionMeetings();

            expect($results['meetings_created'])->toBe(0)
                ->and($results['meetings_terminated'])->toBe(0);
        });

        it('aggregates errors from both operations', function () {
            QuranSession::factory()->ready()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'error-room',
            ]);

            $this->livekitService->shouldReceive('endMeeting')
                ->once()
                ->andThrow(new \Exception('Termination error'));

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $results = $this->service->processSessionMeetings();

            expect($results)->toHaveKey('errors');
        });
    });

    describe('getSessionTiming()', function () {
        it('returns available status for unscheduled sessions', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => null,
            ]);

            $timing = $this->service->getSessionTiming($session);

            expect($timing['is_available'])->toBeTrue()
                ->and($timing['is_scheduled'])->toBeFalse()
                ->and($timing['status'])->toBe('available');
        });

        it('returns too_early when session is far in future', function () {
            AcademySettings::factory()->create([
                'academy_id' => $this->academy->id,
                'default_preparation_minutes' => 10,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now()->addHours(2),
                'duration_minutes' => 45,
            ]);

            $timing = $this->service->getSessionTiming($session);

            expect($timing['is_available'])->toBeFalse()
                ->and($timing['status'])->toBe('too_early')
                ->and($timing)->toHaveKey('minutes_until_available');
        });

        it('returns pre_session during preparation window', function () {
            AcademySettings::factory()->create([
                'academy_id' => $this->academy->id,
                'default_preparation_minutes' => 10,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now()->addMinutes(5),
                'duration_minutes' => 45,
            ]);

            $timing = $this->service->getSessionTiming($session);

            expect($timing['is_available'])->toBeTrue()
                ->and($timing['status'])->toBe('pre_session')
                ->and($timing)->toHaveKey('minutes_until_start');
        });

        it('returns active status during session time', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now()->subMinutes(10),
                'duration_minutes' => 45,
            ]);

            $timing = $this->service->getSessionTiming($session);

            expect($timing['is_available'])->toBeTrue()
                ->and($timing['status'])->toBe('active')
                ->and($timing)->toHaveKey('minutes_remaining');
        });

        it('returns post_session during buffer period', function () {
            AcademySettings::factory()->create([
                'academy_id' => $this->academy->id,
                'default_buffer_minutes' => 5,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now()->subMinutes(50),
                'duration_minutes' => 45,
            ]);

            $timing = $this->service->getSessionTiming($session);

            expect($timing['is_available'])->toBeTrue()
                ->and($timing['status'])->toBe('post_session')
                ->and($timing)->toHaveKey('minutes_since_end');
        });

        it('returns expired status after buffer period', function () {
            AcademySettings::factory()->create([
                'academy_id' => $this->academy->id,
                'default_buffer_minutes' => 5,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => Carbon::now()->subHours(2),
                'duration_minutes' => 45,
            ]);

            $timing = $this->service->getSessionTiming($session);

            expect($timing['is_available'])->toBeFalse()
                ->and($timing['status'])->toBe('expired');
        });
    });

    describe('session persistence', function () {
        it('generates correct persistence key', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $key = $this->service->getSessionPersistenceKey($session);

            expect($key)->toContain('session_meeting')
                ->and($key)->toContain($session->id)
                ->and($key)->toContain('persistence');
        });

        it('marks session as persistent with correct expiration', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'test-room',
                'duration_minutes' => 60,
            ]);

            Log::shouldReceive('info')
                ->with('Marked quran session as persistent', Mockery::any())
                ->once();

            $this->service->markSessionPersistent($session);

            $persistenceInfo = $this->service->getSessionPersistenceInfo($session);

            expect($persistenceInfo)->not->toBeNull()
                ->and($persistenceInfo)->toHaveKey('session_id')
                ->and($persistenceInfo['session_id'])->toBe($session->id);
        });

        it('checks if session should persist based on expiration', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'test-room',
                'duration_minutes' => 60,
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();

            $this->service->markSessionPersistent($session, 30);

            expect($this->service->shouldSessionPersist($session))->toBeTrue();
        });

        it('returns false when persistence has expired', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            expect($this->service->shouldSessionPersist($session))->toBeFalse();
        });

        it('removes session persistence', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'test-room',
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();

            $this->service->markSessionPersistent($session);

            expect($this->service->shouldSessionPersist($session))->toBeTrue();

            $this->service->removeSessionPersistence($session);

            expect($this->service->shouldSessionPersist($session))->toBeFalse();
        });

        it('logs persistence removal', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'test-room',
            ]);

            Log::shouldReceive('info')
                ->with('Removed quran session persistence', Mockery::any())
                ->once();

            $this->service->removeSessionPersistence($session);
        });
    });

    describe('getRoomActivity()', function () {
        it('returns inactive status when session has no meeting room', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => null,
            ]);

            $activity = $this->service->getRoomActivity($session);

            expect($activity['exists'])->toBeFalse()
                ->and($activity['participants'])->toBe(0)
                ->and($activity['is_active'])->toBeFalse();
        });

        it('returns inactive status when room not found on server', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'missing-room',
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->with('missing-room')
                ->once()
                ->andReturn(null);

            $activity = $this->service->getRoomActivity($session);

            expect($activity['exists'])->toBeFalse()
                ->and($activity['participants'])->toBe(0);
        });

        it('returns room info when room exists', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'active-room',
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->with('active-room')
                ->once()
                ->andReturn([
                    'room_name' => 'active-room',
                    'participant_count' => 5,
                    'is_active' => true,
                ]);

            $activity = $this->service->getRoomActivity($session);

            expect($activity['exists'])->toBeTrue()
                ->and($activity['participants'])->toBe(5)
                ->and($activity['is_active'])->toBeTrue()
                ->and($activity)->toHaveKey('room_info');
        });
    });
});
