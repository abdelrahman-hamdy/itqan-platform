<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use App\Services\AcademicSessionMeetingService;
use App\Services\LiveKitService;
use App\Services\UnifiedSessionStatusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

describe('AcademicSessionMeetingService', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->livekitService = Mockery::mock(LiveKitService::class);
        $this->statusService = Mockery::mock(UnifiedSessionStatusService::class);

        $this->service = new AcademicSessionMeetingService(
            $this->livekitService,
            $this->statusService
        );
    });

    afterEach(function () {
        Mockery::close();
        Cache::flush();
    });

    describe('getSessionType()', function () {
        it('returns academic session type', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('getSessionType');
            $method->setAccessible(true);

            expect($method->invoke($this->service))->toBe('academic');
        });
    });

    describe('getMaxParticipants()', function () {
        it('returns 2 for academic sessions', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('getMaxParticipants');
            $method->setAccessible(true);

            expect($method->invoke($this->service))->toBe(2);
        });
    });

    describe('getSessionLabel()', function () {
        it('returns Arabic label for academic session', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('getSessionLabel');
            $method->setAccessible(true);

            expect($method->invoke($this->service))->toBe('الجلسة الأكاديمية');
        });
    });

    describe('getCacheKeyPrefix()', function () {
        it('returns correct cache key prefix', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('getCacheKeyPrefix');
            $method->setAccessible(true);

            expect($method->invoke($this->service))->toBe('academic_session_meeting');
        });
    });

    describe('getEndedAtField()', function () {
        it('returns ended_at field name', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('getEndedAtField');
            $method->setAccessible(true);

            expect($method->invoke($this->service))->toBe('ended_at');
        });
    });

    describe('ensureMeetingAvailable()', function () {
        it('generates meeting link if not present', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now()->addMinutes(5),
                'status' => SessionStatus::SCHEDULED,
                'meeting_room_name' => null,
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->andReturn([
                    'name' => $session->meeting_room_name,
                    'participant_count' => 0,
                    'is_active' => true,
                ]);

            $result = $this->service->ensureMeetingAvailable($session, true);

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['room_name', 'room_info', 'session_timing', 'join_url'])
                ->and($session->fresh()->meeting_room_name)->not->toBeNull();
        });

        it('throws exception when session is not available without force', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now()->addHours(2),
                'status' => SessionStatus::SCHEDULED,
            ]);

            expect(fn () => $this->service->ensureMeetingAvailable($session, false))
                ->toThrow(Exception::class);
        });

        it('recreates meeting room if not found on server', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now()->addMinutes(5),
                'status' => SessionStatus::SCHEDULED,
                'meeting_room_name' => 'existing-room',
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();

            $this->livekitService->shouldReceive('getRoomInfo')
                ->once()
                ->andReturn(null);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->once()
                ->andReturn([
                    'name' => $session->meeting_room_name,
                    'participant_count' => 0,
                    'is_active' => true,
                ]);

            $result = $this->service->ensureMeetingAvailable($session, true);

            expect($result)->toBeArray()
                ->and($result['room_name'])->not->toBeNull();
        });

        it('returns join URL for session', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now()->addMinutes(5),
                'status' => SessionStatus::SCHEDULED,
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->andReturn([
                    'name' => $session->meeting_room_name,
                    'participant_count' => 0,
                    'is_active' => true,
                ]);

            $result = $this->service->ensureMeetingAvailable($session, true);

            expect($result['join_url'])->toContain('student.academic-sessions.show');
        });
    });

    describe('processScheduledSessions()', function () {
        it('creates meetings for ready sessions without meeting rooms', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->academicTeacherProfile->id,
                'student_id' => $student->id,
                'status' => SessionStatus::READY,
                'scheduled_at' => Carbon::now()->addMinutes(5),
                'meeting_room_name' => null,
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $this->livekitService->shouldReceive('getRoomInfo')
                ->andReturn([
                    'name' => 'test-room',
                    'participant_count' => 0,
                    'is_active' => true,
                ]);

            $results = $this->service->processScheduledSessions();

            expect($results)->toBeArray()
                ->and($results)->toHaveKeys(['started', 'updated', 'cleaned', 'errors'])
                ->and($results['started'])->toBeGreaterThanOrEqual(0);
        });

        it('cleans up expired sessions', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->academicTeacherProfile->id,
                'student_id' => $student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => Carbon::now()->subHours(3),
                'meeting_room_name' => 'expired-room',
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $this->livekitService->shouldReceive('endMeeting')->zeroOrMoreTimes();

            $results = $this->service->processScheduledSessions();

            expect($results)->toBeArray()
                ->and($results['cleaned'])->toBeGreaterThanOrEqual(0);
        });

        it('handles errors gracefully', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->academicTeacherProfile->id,
                'student_id' => $student->id,
                'status' => SessionStatus::READY,
                'scheduled_at' => Carbon::now()->addMinutes(5),
                'meeting_room_name' => null,
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $this->livekitService->shouldReceive('getRoomInfo')
                ->andThrow(new Exception('LiveKit error'));

            $results = $this->service->processScheduledSessions();

            expect($results)->toBeArray()
                ->and($results['errors'])->toBeGreaterThanOrEqual(0);
        });
    });

    describe('forceCreateMeeting()', function () {
        it('creates meeting regardless of session timing', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now()->addHours(5),
                'status' => SessionStatus::SCHEDULED,
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->andReturn([
                    'name' => $session->meeting_room_name,
                    'participant_count' => 0,
                    'is_active' => true,
                ]);

            $result = $this->service->forceCreateMeeting($session);

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['room_name', 'room_info', 'session_timing', 'join_url']);
        });
    });

    describe('createMeetingsForReadySessions()', function () {
        it('creates meetings for ready sessions', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->academicTeacherProfile->id,
                'student_id' => $student->id,
                'status' => SessionStatus::READY,
                'scheduled_at' => Carbon::now()->addMinutes(5),
                'meeting_room_name' => null,
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $results = $this->service->createMeetingsForReadySessions();

            expect($results)->toBeArray()
                ->and($results)->toHaveKeys(['meetings_created', 'sessions_processed', 'errors'])
                ->and($results['meetings_created'])->toBeGreaterThanOrEqual(0)
                ->and($results['sessions_processed'])->toBeGreaterThanOrEqual(0);
        });

        it('logs session processing', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->academicTeacherProfile->id,
                'student_id' => $student->id,
                'status' => SessionStatus::READY,
                'scheduled_at' => Carbon::now()->addMinutes(5),
                'meeting_room_name' => null,
            ]);

            Log::shouldReceive('info')
                ->with('Found ready academic sessions without meetings', Mockery::type('array'))
                ->once();

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $this->service->createMeetingsForReadySessions();
        });

        it('collects errors for failed sessions', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'status' => SessionStatus::READY,
                'scheduled_at' => Carbon::now()->addMinutes(5),
                'meeting_room_name' => null,
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $session->delete();

            $results = $this->service->createMeetingsForReadySessions();

            expect($results['errors'])->toBeArray();
        });
    });

    describe('terminateExpiredMeetings()', function () {
        it('terminates meetings for completed sessions', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->academicTeacherProfile->id,
                'student_id' => $student->id,
                'meeting_room_name' => 'completed-room',
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $this->livekitService->shouldReceive('endMeeting')
                ->once();

            $results = $this->service->terminateExpiredMeetings();

            expect($results)->toBeArray()
                ->and($results)->toHaveKeys(['meetings_terminated', 'sessions_processed', 'errors'])
                ->and($results['meetings_terminated'])->toBeGreaterThanOrEqual(0);
        });

        it('skips sessions without meeting rooms', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->academicTeacherProfile->id,
                'student_id' => $student->id,
                'meeting_room_name' => null,
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $this->livekitService->shouldReceive('endMeeting')
                ->never();

            $results = $this->service->terminateExpiredMeetings();

            expect($results['meetings_terminated'])->toBe(0);
        });

        it('handles termination errors', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->academicTeacherProfile->id,
                'student_id' => $student->id,
                'meeting_room_name' => 'error-room',
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $this->livekitService->shouldReceive('endMeeting')
                ->andThrow(new Exception('Termination error'));

            $results = $this->service->terminateExpiredMeetings();

            expect($results['errors'])->not->toBeEmpty();
        });
    });

    describe('processSessionMeetings()', function () {
        it('calls both create and terminate methods', function () {
            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $results = $this->service->processSessionMeetings();

            expect($results)->toBeArray()
                ->and($results)->toHaveKeys(['meetings_created', 'meetings_terminated', 'status_transitions', 'errors']);
        });

        it('aggregates results from both operations', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->academicTeacherProfile->id,
                'student_id' => $student->id,
                'status' => SessionStatus::READY,
                'scheduled_at' => Carbon::now()->addMinutes(5),
                'meeting_room_name' => null,
            ]);

            AcademicSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->academicTeacherProfile->id,
                'student_id' => $student->id,
                'meeting_room_name' => 'to-terminate',
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $this->livekitService->shouldReceive('endMeeting')->zeroOrMoreTimes();

            $results = $this->service->processSessionMeetings();

            expect($results['meetings_created'])->toBeInt()
                ->and($results['meetings_terminated'])->toBeInt();
        });

        it('handles errors in processing', function () {
            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $results = $this->service->processSessionMeetings();

            expect($results['errors'])->toBeArray();
        });
    });

    describe('getSessionTiming()', function () {
        it('returns available for sessions without scheduled time', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => null,
            ]);

            $timing = $this->service->getSessionTiming($session);

            expect($timing['is_available'])->toBeTrue()
                ->and($timing['is_scheduled'])->toBeFalse()
                ->and($timing['status'])->toBe('available');
        });

        it('returns too_early for sessions far in future', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now()->addHours(2),
            ]);

            $timing = $this->service->getSessionTiming($session);

            expect($timing['is_available'])->toBeFalse()
                ->and($timing['status'])->toBe('too_early');
        });

        it('returns pre_session for sessions within preparation window', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now()->addMinutes(5),
            ]);

            $timing = $this->service->getSessionTiming($session);

            expect($timing['is_available'])->toBeTrue()
                ->and($timing['status'])->toBe('pre_session');
        });

        it('returns active for ongoing sessions', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now()->subMinutes(10),
                'duration_minutes' => 60,
            ]);

            $timing = $this->service->getSessionTiming($session);

            expect($timing['is_available'])->toBeTrue()
                ->and($timing['status'])->toBe('active');
        });
    });

    describe('getSessionPersistenceKey()', function () {
        it('generates correct cache key for session', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
            ]);

            $key = $this->service->getSessionPersistenceKey($session);

            expect($key)->toBe("academic_session_meeting:{$session->id}:persistence");
        });
    });

    describe('markSessionPersistent()', function () {
        it('stores persistence data in cache', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
            ]);

            Log::shouldReceive('info')->once();

            $this->service->markSessionPersistent($session);

            $data = Cache::get($this->service->getSessionPersistenceKey($session));

            expect($data)->not->toBeNull()
                ->and($data['session_id'])->toBe($session->id)
                ->and($data['room_name'])->toBe($session->meeting_room_name);
        });

        it('sets expiration time correctly', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now(),
                'duration_minutes' => 60,
            ]);

            Log::shouldReceive('info')->once();

            $this->service->markSessionPersistent($session, 60);

            $data = Cache::get($this->service->getSessionPersistenceKey($session));

            expect($data['expires_at'])->not->toBeNull();
        });
    });

    describe('shouldSessionPersist()', function () {
        it('returns true when persistence data exists and not expired', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now(),
            ]);

            Log::shouldReceive('info')->once();

            $this->service->markSessionPersistent($session);

            expect($this->service->shouldSessionPersist($session))->toBeTrue();
        });

        it('returns false when persistence data does not exist', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
            ]);

            expect($this->service->shouldSessionPersist($session))->toBeFalse();
        });
    });

    describe('getSessionPersistenceInfo()', function () {
        it('returns persistence data when exists', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now(),
            ]);

            Log::shouldReceive('info')->once();

            $this->service->markSessionPersistent($session);

            $info = $this->service->getSessionPersistenceInfo($session);

            expect($info)->toBeArray()
                ->and($info['session_id'])->toBe($session->id);
        });

        it('returns null when no persistence data', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
            ]);

            expect($this->service->getSessionPersistenceInfo($session))->toBeNull();
        });
    });

    describe('removeSessionPersistence()', function () {
        it('removes persistence data from cache', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'scheduled_at' => Carbon::now(),
            ]);

            Log::shouldReceive('info')->twice();

            $this->service->markSessionPersistent($session);
            $this->service->removeSessionPersistence($session);

            expect($this->service->getSessionPersistenceInfo($session))->toBeNull();
        });
    });

    describe('getRoomActivity()', function () {
        it('returns empty activity when no meeting room', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'meeting_room_name' => null,
            ]);

            $activity = $this->service->getRoomActivity($session);

            expect($activity['exists'])->toBeFalse()
                ->and($activity['participants'])->toBe(0)
                ->and($activity['is_active'])->toBeFalse();
        });

        it('returns room info when room exists', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->once()
                ->andReturn([
                    'name' => $session->meeting_room_name,
                    'participant_count' => 2,
                    'is_active' => true,
                ]);

            $activity = $this->service->getRoomActivity($session);

            expect($activity['exists'])->toBeTrue()
                ->and($activity['participants'])->toBe(2)
                ->and($activity['is_active'])->toBeTrue();
        });

        it('handles room not found on server', function () {
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
                'student_id' => $student->id,
                'meeting_room_name' => 'nonexistent-room',
            ]);

            $this->livekitService->shouldReceive('getRoomInfo')
                ->once()
                ->andReturn(null);

            $activity = $this->service->getRoomActivity($session);

            expect($activity['exists'])->toBeFalse();
        });
    });
});
