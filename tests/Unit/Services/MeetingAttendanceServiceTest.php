<?php

use App\Enums\SessionStatus;
use App\Events\AttendanceUpdated;
use App\Models\Academy;
use App\Models\MeetingAttendance;
use App\Models\ParentProfile;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\MeetingAttendanceService;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
use App\Services\UnifiedSessionStatusService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

describe('MeetingAttendanceService', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->statusService = Mockery::mock(UnifiedSessionStatusService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->parentNotificationService = Mockery::mock(ParentNotificationService::class);

        $this->service = new MeetingAttendanceService(
            $this->statusService,
            $this->notificationService,
            $this->parentNotificationService
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('handleUserJoin()', function () {
        it('creates attendance record when user joins meeting', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $result = $this->service->handleUserJoin($session, $user);

            expect($result)->toBeTrue();

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first();

            expect($attendance)->not->toBeNull()
                ->and($attendance->join_count)->toBe(1)
                ->and($attendance->first_join_time)->not->toBeNull();
        });

        it('transitions session to ongoing when first user joins ready session', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::READY,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->statusService->shouldReceive('transitionToOngoing')
                ->once()
                ->with(Mockery::on(function ($arg) use ($session) {
                    return $arg->id === $session->id;
                }));

            $result = $this->service->handleUserJoin($session, $user);

            expect($result)->toBeTrue();
        });

        it('does not transition session if status is not ready', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->statusService->shouldReceive('transitionToOngoing')
                ->never();

            $result = $this->service->handleUserJoin($session, $user);

            expect($result)->toBeTrue();
        });

        it('broadcasts attendance update event', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $result = $this->service->handleUserJoin($session, $user);

            Event::assertDispatched(AttendanceUpdated::class, function ($event) use ($session, $user) {
                return $event->sessionId === $session->id
                    && $event->userId === $user->id
                    && $event->data['status'] === 'joined'
                    && $event->data['is_currently_in_meeting'] === true;
            });
        });

        it('logs info when user joins successfully', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($session, $user) {
                    return str_contains($message, 'User joined meeting successfully')
                        && $context['session_id'] === $session->id
                        && $context['user_id'] === $user->id;
                });

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $this->service->handleUserJoin($session, $user);
        });

        it('returns false and logs error on exception', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Force an exception by deleting the session
            $session->delete();

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'Failed to handle user join');
                });

            $result = $this->service->handleUserJoin($session, $user);

            expect($result)->toBeFalse();
        });

        it('handles existing attendance record on second join', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            // First join
            $this->service->handleUserJoin($session, $user);

            // Record leave
            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first();
            $attendance->recordLeave();

            // Second join
            $result = $this->service->handleUserJoin($session, $user);

            expect($result)->toBeTrue();

            $attendance->refresh();
            expect($attendance->join_count)->toBe(2);
        });
    });

    describe('handleUserLeave()', function () {
        it('records leave time when user leaves meeting', function () {
            $session = QuranSession::factory()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            // First join
            $this->service->handleUserJoin($session, $user);

            // Then leave
            $result = $this->service->handleUserLeave($session, $user);

            expect($result)->toBeTrue();

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first();

            expect($attendance->leave_count)->toBe(1)
                ->and($attendance->last_leave_time)->not->toBeNull()
                ->and($attendance->total_duration_minutes)->toBeGreaterThan(0);
        });

        it('broadcasts attendance update event on leave', function () {
            $session = QuranSession::factory()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $user);
            $result = $this->service->handleUserLeave($session, $user);

            Event::assertDispatched(AttendanceUpdated::class, function ($event) use ($session, $user) {
                return $event->sessionId === $session->id
                    && $event->userId === $user->id
                    && $event->data['status'] === 'left'
                    && $event->data['is_currently_in_meeting'] === false;
            });
        });

        it('returns false when user has no attendance record', function () {
            $session = QuranSession::factory()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('warning')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'User tried to leave meeting but no attendance record found');
                });

            $result = $this->service->handleUserLeave($session, $user);

            expect($result)->toBeFalse();
        });

        it('logs info when user leaves successfully', function () {
            $session = QuranSession::factory()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $user);

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($session, $user) {
                    return str_contains($message, 'User left meeting successfully')
                        && $context['session_id'] === $session->id
                        && $context['user_id'] === $user->id;
                });

            Log::shouldReceive('debug')
                ->zeroOrMoreTimes();

            $this->service->handleUserLeave($session, $user);
        });

        it('returns false and logs error on exception', function () {
            $session = QuranSession::factory()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Create attendance then delete session to force error
            $this->service->handleUserJoin($session, $user);
            $session->delete();

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'Failed to handle user leave');
                });

            $result = $this->service->handleUserLeave($session, $user);

            expect($result)->toBeFalse();
        });
    });

    describe('handleUserJoinPolymorphic()', function () {
        it('creates attendance record for quran session', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $result = $this->service->handleUserJoinPolymorphic($session, $user, 'quran');

            expect($result)->toBeTrue();

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first();

            expect($attendance)->not->toBeNull()
                ->and($attendance->session_type)->toBe('individual');
        });

        it('transitions ready quran session to ongoing', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::READY,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->statusService->shouldReceive('transitionToOngoing')
                ->once()
                ->with(Mockery::on(function ($arg) use ($session) {
                    return $arg->id === $session->id;
                }));

            $result = $this->service->handleUserJoinPolymorphic($session, $user, 'quran');

            expect($result)->toBeTrue();
        });

        it('logs info when user joins successfully', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($session, $user) {
                    return str_contains($message, 'User joined meeting successfully (polymorphic)')
                        && $context['session_id'] === $session->id
                        && $context['user_id'] === $user->id
                        && $context['session_type'] === 'quran';
                });

            $this->service->handleUserJoinPolymorphic($session, $user, 'quran');
        });

        it('returns false and logs error on exception', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $session->delete();

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Failed to handle user join (polymorphic)')
                        && $context['session_type'] === 'quran';
                });

            $result = $this->service->handleUserJoinPolymorphic($session, $user, 'quran');

            expect($result)->toBeFalse();
        });
    });

    describe('handleUserLeavePolymorphic()', function () {
        it('records leave for quran session', function () {
            $session = QuranSession::factory()->individual()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->handleUserJoinPolymorphic($session, $user, 'quran');
            $result = $this->service->handleUserLeavePolymorphic($session, $user, 'quran');

            expect($result)->toBeTrue();

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first();

            expect($attendance->leave_count)->toBe(1)
                ->and($attendance->last_leave_time)->not->toBeNull();
        });

        it('returns false when no attendance record exists', function () {
            $session = QuranSession::factory()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('warning')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'User tried to leave meeting but no attendance record found (polymorphic)');
                });

            $result = $this->service->handleUserLeavePolymorphic($session, $user, 'quran');

            expect($result)->toBeFalse();
        });

        it('logs info when user leaves successfully', function () {
            $session = QuranSession::factory()->individual()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->handleUserJoinPolymorphic($session, $user, 'quran');

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($session, $user) {
                    return str_contains($message, 'User left meeting successfully (polymorphic)')
                        && $context['session_id'] === $session->id
                        && $context['user_id'] === $user->id
                        && $context['session_type'] === 'quran';
                });

            $this->service->handleUserLeavePolymorphic($session, $user, 'quran');
        });

        it('returns false and logs error on exception', function () {
            $session = QuranSession::factory()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->handleUserJoinPolymorphic($session, $user, 'quran');
            $session->delete();

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'Failed to handle user leave (polymorphic)');
                });

            $result = $this->service->handleUserLeavePolymorphic($session, $user, 'quran');

            expect($result)->toBeFalse();
        });
    });

    describe('calculateFinalAttendance()', function () {
        it('calculates final attendance for session participants', function () {
            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $session = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
                'duration_minutes' => 60,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $user);
            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first();

            // Simulate attendance duration
            $attendance->update([
                'total_duration_minutes' => 55,
            ]);

            $this->notificationService->shouldReceive('sendAttendanceMarkedNotification')
                ->once();

            $this->parentNotificationService->shouldReceive('getParentsForStudent')
                ->andReturn(collect());

            $results = $this->service->calculateFinalAttendance($session);

            expect($results['calculated_count'])->toBe(1)
                ->and($results['attendances'])->toHaveCount(1)
                ->and($results['errors'])->toBeEmpty();

            $attendance->refresh();
            expect($attendance->is_calculated)->toBeTrue()
                ->and($attendance->attendance_status)->not->toBeNull()
                ->and($attendance->attendance_percentage)->toBeGreaterThan(0);
        });

        it('updates session participants count', function () {
            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $session = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
                'participants_count' => 0,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $user);

            $this->notificationService->shouldReceive('sendAttendanceMarkedNotification')
                ->zeroOrMoreTimes();

            $this->parentNotificationService->shouldReceive('getParentsForStudent')
                ->andReturn(collect());

            $this->service->calculateFinalAttendance($session);

            $session->refresh();
            expect($session->participants_count)->toBe(1);
        });

        it('sends attendance notification to student', function () {
            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $session = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $user);

            $this->notificationService->shouldReceive('sendAttendanceMarkedNotification')
                ->once()
                ->withArgs(function ($attendance, $receivedUser, $status) use ($user) {
                    return $receivedUser->id === $user->id
                        && in_array($status, ['present', 'absent', 'late']);
                });

            $this->parentNotificationService->shouldReceive('getParentsForStudent')
                ->andReturn(collect());

            $this->service->calculateFinalAttendance($session);
        });

        it('sends attendance notification to parents', function () {
            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $session = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $parent = ParentProfile::factory()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $student);

            $this->notificationService->shouldReceive('sendAttendanceMarkedNotification')
                ->once();

            $this->parentNotificationService->shouldReceive('getParentsForStudent')
                ->with(Mockery::on(function ($user) use ($student) {
                    return $user->id === $student->id;
                }))
                ->andReturn(collect([$parent]));

            $this->notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data, $url, $metadata, $important) use ($parent) {
                    return $user->id === $parent->user->id;
                });

            $this->service->calculateFinalAttendance($session);
        });

        it('does not calculate already calculated attendance', function () {
            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $session = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $user);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first();

            $attendance->update(['is_calculated' => true]);

            $this->notificationService->shouldReceive('sendAttendanceMarkedNotification')
                ->never();

            $results = $this->service->calculateFinalAttendance($session);

            expect($results['calculated_count'])->toBe(0);
        });

        it('logs errors for individual attendance calculation failures', function () {
            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $session = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $user);

            // Delete circle to cause error
            $circle->delete();

            $this->notificationService->shouldReceive('sendAttendanceMarkedNotification')
                ->never();

            $results = $this->service->calculateFinalAttendance($session);

            expect($results['errors'])->not->toBeEmpty();
        });
    });

    describe('processCompletedSessions()', function () {
        it('processes multiple completed sessions', function () {
            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $sessions = QuranSession::factory()->individual()->completed()->count(3)->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $sessions->each(function ($session) use ($user) {
                $this->service->handleUserJoin($session, $user);
            });

            $this->notificationService->shouldReceive('sendAttendanceMarkedNotification')
                ->times(3);

            $this->parentNotificationService->shouldReceive('getParentsForStudent')
                ->andReturn(collect());

            $results = $this->service->processCompletedSessions($sessions);

            expect($results['processed_sessions'])->toBe(3)
                ->and($results['total_attendances_calculated'])->toBe(3);
        });

        it('continues processing on individual session errors', function () {
            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $session1 = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
            ]);

            $session2 = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session1, $user);
            $this->service->handleUserJoin($session2, $user);

            // Delete circle after creating attendances to cause error
            $circle->delete();

            Log::shouldReceive('error')
                ->atLeast()
                ->once();

            $results = $this->service->processCompletedSessions(collect([$session1, $session2]));

            expect($results['processed_sessions'])->toBe(2)
                ->and($results['errors'])->not->toBeEmpty();
        });

        it('returns empty results for empty session collection', function () {
            $this->notificationService->shouldReceive('sendAttendanceMarkedNotification')
                ->never();

            $results = $this->service->processCompletedSessions(collect());

            expect($results['processed_sessions'])->toBe(0)
                ->and($results['total_attendances_calculated'])->toBe(0)
                ->and($results['errors'])->toBeEmpty();
        });
    });

    describe('handleReconnection()', function () {
        it('detects reconnection within threshold', function () {
            $session = QuranSession::factory()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $user);
            $this->service->handleUserLeave($session, $user);

            $result = $this->service->handleReconnection($session, $user);

            expect($result)->toBeTrue();

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first();

            expect($attendance->last_leave_time)->toBeNull();
        });

        it('returns false for new user with no attendance', function () {
            $session = QuranSession::factory()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $result = $this->service->handleReconnection($session, $user);

            expect($result)->toBeFalse();
        });

        it('returns false when user has not left', function () {
            $session = QuranSession::factory()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $user);

            $result = $this->service->handleReconnection($session, $user);

            expect($result)->toBeFalse();
        });

        it('logs info when reconnection is detected', function () {
            $session = QuranSession::factory()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $user);
            $this->service->handleUserLeave($session, $user);

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($session, $user) {
                    return str_contains($message, 'Reconnection detected and merged')
                        && $context['session_id'] === $session->id
                        && $context['user_id'] === $user->id;
                });

            $this->service->handleReconnection($session, $user);
        });
    });

    describe('getAttendanceStatistics()', function () {
        it('returns statistics for session attendance', function () {
            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $session = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
            ]);

            $users = User::factory()->student()->forAcademy($this->academy)->count(3)->create();

            Event::fake([AttendanceUpdated::class]);

            $users->each(function ($user) use ($session) {
                $this->service->handleUserJoin($session, $user);
            });

            $this->notificationService->shouldReceive('sendAttendanceMarkedNotification')
                ->times(3);

            $this->parentNotificationService->shouldReceive('getParentsForStudent')
                ->andReturn(collect());

            $this->service->calculateFinalAttendance($session);

            $stats = $this->service->getAttendanceStatistics($session);

            expect($stats)->toHaveKey('total_participants')
                ->and($stats)->toHaveKey('present')
                ->and($stats)->toHaveKey('late')
                ->and($stats)->toHaveKey('partial')
                ->and($stats)->toHaveKey('absent')
                ->and($stats)->toHaveKey('average_attendance_percentage')
                ->and($stats)->toHaveKey('total_meeting_duration')
                ->and($stats['total_participants'])->toBe(3);
        });

        it('returns zero statistics for session with no attendance', function () {
            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
            ]);

            $stats = $this->service->getAttendanceStatistics($session);

            expect($stats['total_participants'])->toBe(0)
                ->and($stats['average_attendance_percentage'])->toBe(0)
                ->and($stats['total_meeting_duration'])->toBe(0);
        });
    });

    describe('cleanupOldAttendanceRecords()', function () {
        it('deletes old uncalculated attendance records', function () {
            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subDays(10),
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'is_calculated' => false,
                'created_at' => now()->subDays(10),
            ]);

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'Cleaned up old uncalculated attendance records');
                });

            $deleted = $this->service->cleanupOldAttendanceRecords(7);

            expect($deleted)->toBe(1);
        });

        it('does not delete recent uncalculated records', function () {
            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subDays(2),
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'is_calculated' => false,
                'created_at' => now()->subDays(2),
            ]);

            $deleted = $this->service->cleanupOldAttendanceRecords(7);

            expect($deleted)->toBe(0);
        });

        it('does not delete calculated attendance records', function () {
            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subDays(10),
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_type' => 'student',
                'session_type' => 'individual',
                'is_calculated' => true,
                'created_at' => now()->subDays(10),
            ]);

            $deleted = $this->service->cleanupOldAttendanceRecords(7);

            expect($deleted)->toBe(0);
        });
    });

    describe('recalculateAttendance()', function () {
        it('resets and recalculates attendance for session', function () {
            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $session = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $user);

            $this->notificationService->shouldReceive('sendAttendanceMarkedNotification')
                ->twice();

            $this->parentNotificationService->shouldReceive('getParentsForStudent')
                ->andReturn(collect());

            // First calculation
            $this->service->calculateFinalAttendance($session);

            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first();

            expect($attendance->is_calculated)->toBeTrue();

            // Recalculate
            $results = $this->service->recalculateAttendance($session);

            $attendance->refresh();
            expect($results['calculated_count'])->toBe(1)
                ->and($attendance->is_calculated)->toBeTrue();
        });
    });

    describe('exportAttendanceData()', function () {
        it('exports attendance data for session', function () {
            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $session = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $circle->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Event::fake([AttendanceUpdated::class]);

            $this->service->handleUserJoin($session, $user);

            $this->notificationService->shouldReceive('sendAttendanceMarkedNotification')
                ->once();

            $this->parentNotificationService->shouldReceive('getParentsForStudent')
                ->andReturn(collect());

            $this->service->calculateFinalAttendance($session);

            $data = $this->service->exportAttendanceData($session);

            expect($data)->toBeArray()
                ->and($data)->toHaveCount(1)
                ->and($data[0])->toHaveKey('user_id')
                ->and($data[0])->toHaveKey('user_name')
                ->and($data[0])->toHaveKey('user_type')
                ->and($data[0])->toHaveKey('first_join_time')
                ->and($data[0])->toHaveKey('total_duration_minutes')
                ->and($data[0])->toHaveKey('attendance_status')
                ->and($data[0])->toHaveKey('attendance_percentage')
                ->and($data[0]['user_id'])->toBe($user->id);
        });

        it('returns empty array for session with no calculated attendance', function () {
            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
            ]);

            $data = $this->service->exportAttendanceData($session);

            expect($data)->toBeArray()
                ->and($data)->toBeEmpty();
        });
    });
});
