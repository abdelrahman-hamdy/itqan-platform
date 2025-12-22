<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use App\Services\SessionNotificationService;
use App\Services\SessionSettingsService;
use App\Services\UnifiedSessionStatusService;

describe('UnifiedSessionStatusService', function () {
    beforeEach(function () {
        $this->settingsService = Mockery::mock(SessionSettingsService::class);
        $this->notificationService = Mockery::mock(SessionNotificationService::class);
        $this->service = new UnifiedSessionStatusService(
            $this->settingsService,
            $this->notificationService
        );

        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = QuranTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
    });

    describe('transitionToReady', function () {
        it('transitions scheduled session to ready', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');
            $this->notificationService->shouldReceive('sendReadyNotifications')->once();

            $result = $this->service->transitionToReady($session);

            expect($result)->toBeTrue()
                ->and($session->fresh()->status)->toBe(SessionStatus::READY);
        });

        it('returns false for non-scheduled session', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::ONGOING,
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');

            $result = $this->service->transitionToReady($session);

            expect($result)->toBeFalse();
        });

        it('sets preparation_completed_at timestamp', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');
            $this->notificationService->shouldReceive('sendReadyNotifications')->once();

            $this->service->transitionToReady($session);

            expect($session->fresh()->preparation_completed_at)->not->toBeNull();
        });
    });

    describe('transitionToOngoing', function () {
        it('transitions ready session to ongoing', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::READY,
                'scheduled_at' => now()->subMinutes(5),
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');
            $this->settingsService->shouldReceive('getEarlyJoinMinutes')->andReturn(10);
            $this->settingsService->shouldReceive('getMaxFutureHoursOngoing')->andReturn(24);
            $this->notificationService->shouldReceive('sendStartedNotifications')->once();

            $result = $this->service->transitionToOngoing($session);

            expect($result)->toBeTrue()
                ->and($session->fresh()->status)->toBe(SessionStatus::ONGOING);
        });

        it('returns false for non-ready session', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');

            $result = $this->service->transitionToOngoing($session);

            expect($result)->toBeFalse();
        });

        it('sets started_at timestamp', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::READY,
                'scheduled_at' => now()->subMinutes(5),
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');
            $this->settingsService->shouldReceive('getEarlyJoinMinutes')->andReturn(10);
            $this->settingsService->shouldReceive('getMaxFutureHoursOngoing')->andReturn(24);
            $this->notificationService->shouldReceive('sendStartedNotifications')->once();

            $this->service->transitionToOngoing($session);

            expect($session->fresh()->started_at)->not->toBeNull();
        });

        it('validates early join time window', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::READY,
                'scheduled_at' => now()->addHours(2), // Too far in future
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');
            $this->settingsService->shouldReceive('getEarlyJoinMinutes')->andReturn(10);

            $result = $this->service->transitionToOngoing($session);

            expect($result)->toBeFalse();
        });
    });

    describe('transitionToCompleted', function () {
        it('transitions ongoing session to completed', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::ONGOING,
                'started_at' => now()->subHour(),
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');
            $this->settingsService->shouldReceive('isIndividualSession')->andReturn(true);
            $this->notificationService->shouldReceive('sendCompletedNotifications')->once();

            $result = $this->service->transitionToCompleted($session);

            expect($result)->toBeTrue()
                ->and($session->fresh()->status)->toBe(SessionStatus::COMPLETED);
        });

        it('transitions ready session to completed', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::READY,
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');
            $this->settingsService->shouldReceive('isIndividualSession')->andReturn(true);
            $this->notificationService->shouldReceive('sendCompletedNotifications')->once();

            $result = $this->service->transitionToCompleted($session);

            expect($result)->toBeTrue();
        });

        it('returns false for scheduled session', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');

            $result = $this->service->transitionToCompleted($session);

            expect($result)->toBeFalse();
        });

        it('sets ended_at timestamp', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::ONGOING,
                'started_at' => now()->subHour(),
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');
            $this->settingsService->shouldReceive('isIndividualSession')->andReturn(true);
            $this->notificationService->shouldReceive('sendCompletedNotifications')->once();

            $this->service->transitionToCompleted($session);

            expect($session->fresh()->ended_at)->not->toBeNull();
        });

        it('calculates actual duration', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::ONGOING,
                'started_at' => now()->subMinutes(45),
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');
            $this->settingsService->shouldReceive('isIndividualSession')->andReturn(true);
            $this->notificationService->shouldReceive('sendCompletedNotifications')->once();

            $this->service->transitionToCompleted($session);

            expect($session->fresh()->actual_duration_minutes)->toBeGreaterThanOrEqual(44)
                ->and($session->fresh()->actual_duration_minutes)->toBeLessThanOrEqual(46);
        });
    });

    describe('transitionToCancelled', function () {
        it('transitions scheduled session to cancelled', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');

            $result = $this->service->transitionToCancelled($session, 'Test reason', $this->teacher->id);

            expect($result)->toBeTrue()
                ->and($session->fresh()->status)->toBe(SessionStatus::CANCELLED)
                ->and($session->fresh()->cancellation_reason)->toBe('Test reason')
                ->and($session->fresh()->cancelled_by)->toBe($this->teacher->id);
        });

        it('transitions ready session to cancelled', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::READY,
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');

            $result = $this->service->transitionToCancelled($session);

            expect($result)->toBeTrue();
        });

        it('returns false for ongoing session', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::ONGOING,
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');

            $result = $this->service->transitionToCancelled($session);

            expect($result)->toBeFalse();
        });

        it('sets cancelled_at timestamp', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');

            $this->service->transitionToCancelled($session);

            expect($session->fresh()->cancelled_at)->not->toBeNull();
        });
    });

    describe('transitionToAbsent', function () {
        it('transitions ready individual session to absent', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::READY,
                'session_type' => 'individual',
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');
            $this->settingsService->shouldReceive('isIndividualSession')->andReturn(true);
            $this->notificationService->shouldReceive('sendAbsentNotifications')->once();

            $result = $this->service->transitionToAbsent($session);

            expect($result)->toBeTrue()
                ->and($session->fresh()->status)->toBe(SessionStatus::ABSENT);
        });

        it('returns false for group session', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_profile_id' => $this->teacherProfile->id,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'circle_id' => $circle->id,
                'status' => SessionStatus::READY,
                'session_type' => 'circle',
            ]);

            $this->settingsService->shouldReceive('isIndividualSession')->andReturn(false);
            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');

            $result = $this->service->transitionToAbsent($session);

            expect($result)->toBeFalse();
        });

        it('returns false for scheduled session', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $this->settingsService->shouldReceive('isIndividualSession')->andReturn(true);
            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');

            $result = $this->service->transitionToAbsent($session);

            expect($result)->toBeFalse();
        });
    });

    describe('shouldTransitionToReady', function () {
        it('returns true when preparation time has arrived', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addMinutes(10), // 10 mins from now
            ]);

            $this->settingsService->shouldReceive('getPreparationMinutes')->andReturn(15);
            $this->settingsService->shouldReceive('getMaxFutureHours')->andReturn(24);

            $result = $this->service->shouldTransitionToReady($session);

            expect($result)->toBeTrue();
        });

        it('returns false when session not scheduled', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::ONGOING,
            ]);

            $result = $this->service->shouldTransitionToReady($session);

            expect($result)->toBeFalse();
        });

        it('returns false for sessions too far in future', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(5),
            ]);

            $this->settingsService->shouldReceive('getPreparationMinutes')->andReturn(15);
            $this->settingsService->shouldReceive('getMaxFutureHours')->andReturn(24);

            $result = $this->service->shouldTransitionToReady($session);

            expect($result)->toBeFalse();
        });

        it('returns false for old sessions', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->subDays(2),
            ]);

            $this->settingsService->shouldReceive('getPreparationMinutes')->andReturn(15);
            $this->settingsService->shouldReceive('getMaxFutureHours')->andReturn(24);

            $result = $this->service->shouldTransitionToReady($session);

            expect($result)->toBeFalse();
        });
    });

    describe('shouldTransitionToAbsent', function () {
        it('returns true when grace period passed with no student participation', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::READY,
                'session_type' => 'individual',
                'scheduled_at' => now()->subMinutes(20),
            ]);

            $this->settingsService->shouldReceive('isIndividualSession')->andReturn(true);
            $this->settingsService->shouldReceive('getGracePeriodMinutes')->andReturn(15);

            $result = $this->service->shouldTransitionToAbsent($session);

            expect($result)->toBeTrue();
        });

        it('returns false for group sessions', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'status' => SessionStatus::READY,
                'session_type' => 'circle',
            ]);

            $this->settingsService->shouldReceive('isIndividualSession')->andReturn(false);

            $result = $this->service->shouldTransitionToAbsent($session);

            expect($result)->toBeFalse();
        });

        it('returns false when student has participated', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::ONGOING,
                'session_type' => 'individual',
                'scheduled_at' => now()->subMinutes(20),
            ]);

            // Create attendance record
            MeetingAttendance::create([
                'session_id' => $session->id,
                'session_type' => 'quran',
                'user_id' => $this->student->id,
                'user_type' => 'student',
                'total_duration_minutes' => 15,
            ]);

            $this->settingsService->shouldReceive('isIndividualSession')->andReturn(true);
            $this->settingsService->shouldReceive('getGracePeriodMinutes')->andReturn(15);

            $result = $this->service->shouldTransitionToAbsent($session);

            expect($result)->toBeFalse();
        });
    });

    describe('shouldAutoComplete', function () {
        it('returns true when session time has exceeded duration plus buffer', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::ONGOING,
                'scheduled_at' => now()->subMinutes(90),
                'duration_minutes' => 60,
            ]);

            $this->settingsService->shouldReceive('getBufferMinutes')->andReturn(15);

            $result = $this->service->shouldAutoComplete($session);

            expect($result)->toBeTrue();
        });

        it('returns false when session is still within time', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::ONGOING,
                'scheduled_at' => now()->subMinutes(30),
                'duration_minutes' => 60,
            ]);

            $this->settingsService->shouldReceive('getBufferMinutes')->andReturn(15);

            $result = $this->service->shouldAutoComplete($session);

            expect($result)->toBeFalse();
        });

        it('returns false for scheduled session', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $result = $this->service->shouldAutoComplete($session);

            expect($result)->toBeFalse();
        });
    });

    describe('processStatusTransitions', function () {
        it('processes collection of sessions', function () {
            $session1 = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addMinutes(5),
            ]);

            $session2 = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::ONGOING,
                'scheduled_at' => now()->subMinutes(90),
                'duration_minutes' => 60,
            ]);

            $this->settingsService->shouldReceive('getPreparationMinutes')->andReturn(10);
            $this->settingsService->shouldReceive('getMaxFutureHours')->andReturn(24);
            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');
            $this->settingsService->shouldReceive('isIndividualSession')->andReturn(true);
            $this->settingsService->shouldReceive('getBufferMinutes')->andReturn(15);
            $this->settingsService->shouldReceive('getGracePeriodMinutes')->andReturn(15);
            $this->notificationService->shouldReceive('sendReadyNotifications');
            $this->notificationService->shouldReceive('sendCompletedNotifications');

            $sessions = collect([$session1, $session2]);
            $results = $this->service->processStatusTransitions($sessions);

            expect($results)->toBeArray()
                ->and($results)->toHaveKey('transitions_to_ready')
                ->and($results)->toHaveKey('transitions_to_absent')
                ->and($results)->toHaveKey('transitions_to_completed')
                ->and($results)->toHaveKey('errors');
        });

        it('returns error info for failed transitions', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addMinutes(5),
            ]);

            $this->settingsService->shouldReceive('getPreparationMinutes')->andThrow(new \Exception('Test error'));
            $this->settingsService->shouldReceive('getSessionType')->andReturn('quran');

            $results = $this->service->processStatusTransitions(collect([$session]));

            expect($results['errors'])->not->toBeEmpty();
        });
    });

    describe('works with academic sessions', function () {
        it('handles academic session transitions', function () {
            $academicTeacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $academicTeacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $academicTeacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $academicTeacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('academic');
            $this->notificationService->shouldReceive('sendReadyNotifications')->once();

            $result = $this->service->transitionToReady($session);

            expect($result)->toBeTrue()
                ->and($session->fresh()->status)->toBe(SessionStatus::READY);
        });
    });

    describe('works with interactive course sessions', function () {
        it('handles interactive course session transitions', function () {
            $academicTeacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $academicTeacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $academicTeacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_profile_id' => $academicTeacherProfile->id,
            ]);

            $session = InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $this->settingsService->shouldReceive('getSessionType')->andReturn('interactive');
            $this->notificationService->shouldReceive('sendReadyNotifications')->once();

            $result = $this->service->transitionToReady($session);

            expect($result)->toBeTrue();
        });
    });
});
