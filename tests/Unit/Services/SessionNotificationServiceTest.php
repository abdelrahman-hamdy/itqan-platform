<?php

use App\Enums\NotificationType;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\ParentProfile;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
use App\Services\SessionNotificationService;
use App\Services\SessionSettingsService;
use Illuminate\Support\Facades\Log;

describe('SessionNotificationService', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->settingsService = Mockery::mock(SessionSettingsService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->parentNotificationService = Mockery::mock(ParentNotificationService::class);

        $this->service = new SessionNotificationService(
            $this->settingsService,
            $this->notificationService,
            $this->parentNotificationService
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('sendReadyNotifications()', function () {
        it('sends ready notifications for individual quran session', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
            ]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة قرآنية');

            $this->notificationService->shouldReceive('sendSessionReminderNotification')
                ->once();

            $this->parentNotificationService->shouldReceive('sendSessionReminder')
                ->once();

            $this->notificationService->shouldReceive('send')
                ->zeroOrMoreTimes();

            $this->service->sendReadyNotifications($session);

            expect(true)->toBeTrue();
        });

        it('handles group quran session gracefully', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->group()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'circle_id' => null,
            ]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة قرآنية');

            $this->notificationService->shouldReceive('sendSessionReminderNotification')
                ->zeroOrMoreTimes();

            $this->notificationService->shouldReceive('send')
                ->zeroOrMoreTimes();

            $this->service->sendReadyNotifications($session);

            expect(true)->toBeTrue();
        });

        it('sends ready notifications for academic session', function () {
            $academicTeacher = AcademicTeacherProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $academicTeacher->id,
                'student_id' => $student->id,
            ]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة أكاديمية');

            $this->notificationService->shouldReceive('sendSessionReminderNotification')
                ->once();

            $this->parentNotificationService->shouldReceive('sendSessionReminder')
                ->once();

            $this->notificationService->shouldReceive('send')
                ->zeroOrMoreTimes();

            $this->service->sendReadyNotifications($session);

            expect(true)->toBeTrue();
        });

        it('handles interactive course session gracefully', function () {
            $course = InteractiveCourse::factory()->create();
            $session = InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
                'session_number' => 1,
            ]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn($course->title);

            $this->notificationService->shouldReceive('send')
                ->zeroOrMoreTimes();

            $this->service->sendReadyNotifications($session);

            expect(true)->toBeTrue();
        });

        it('logs error when notification fails', function () {
            $session = QuranSession::factory()->create(['academy_id' => $this->academy->id]);

            $this->settingsService->shouldReceive('getSessionType')
                ->andReturn('quran');

            $this->notificationService->shouldReceive('sendSessionReminderNotification')
                ->andThrow(new \Exception('Notification failed'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) use ($session) {
                    return str_contains($message, 'Failed to send session ready notifications')
                        || str_contains($message, 'Failed to send Quran session ready notifications');
                });

            $this->service->sendReadyNotifications($session);

            expect(true)->toBeTrue();
        });

        it('handles session with null student gracefully', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => null,
            ]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة قرآنية');

            $this->notificationService->shouldReceive('send')
                ->zeroOrMoreTimes();

            $this->service->sendReadyNotifications($session);

            expect(true)->toBeTrue();
        });

        it('handles session with null circle gracefully', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->group()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'circle_id' => null,
            ]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة قرآنية');

            $this->notificationService->shouldReceive('send')
                ->zeroOrMoreTimes();

            $this->service->sendReadyNotifications($session);

            expect(true)->toBeTrue();
        });
    });

    describe('sendStartedNotifications()', function () {
        it('sends started notification for individual session', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة قرآنية');

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(true);

            $this->notificationService->shouldReceive('send')
                ->once();

            $this->service->sendStartedNotifications($session);

            expect(true)->toBeTrue();
        });

        it('handles group quran session gracefully', function () {
            $session = QuranSession::factory()->group()->create([
                'academy_id' => $this->academy->id,
                'circle_id' => null,
            ]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة قرآنية');

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(false);

            $this->notificationService->shouldReceive('send')
                ->zeroOrMoreTimes();

            $this->service->sendStartedNotifications($session);

            expect(true)->toBeTrue();
        });

        it('handles interactive course session gracefully', function () {
            $course = InteractiveCourse::factory()->create();
            $session = InteractiveCourseSession::factory()->create(['course_id' => $course->id]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn($course->title);

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(false);

            $this->notificationService->shouldReceive('send')
                ->zeroOrMoreTimes();

            $this->service->sendStartedNotifications($session);

            expect(true)->toBeTrue();
        });

        it('logs error when notification fails', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة قرآنية');

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(true);

            $this->settingsService->shouldReceive('getSessionType')
                ->andReturn('quran');

            $this->notificationService->shouldReceive('send')
                ->andThrow(new \Exception('Notification error'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'Failed to send session started notifications');
                });

            $this->service->sendStartedNotifications($session);

            expect(true)->toBeTrue();
        });
    });

    describe('sendCompletedNotifications()', function () {
        it('sends completed notification for individual session', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة قرآنية');

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(true);

            $this->notificationService->shouldReceive('send')
                ->once();

            $this->service->sendCompletedNotifications($session);

            expect(true)->toBeTrue();
        });

        it('handles group quran session gracefully', function () {
            $session = QuranSession::factory()->group()->create([
                'academy_id' => $this->academy->id,
                'circle_id' => null,
            ]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة قرآنية');

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(false);

            $this->notificationService->shouldReceive('send')
                ->zeroOrMoreTimes();

            $this->service->sendCompletedNotifications($session);

            expect(true)->toBeTrue();
        });

        it('handles interactive course session gracefully', function () {
            $course = InteractiveCourse::factory()->create();
            $session = InteractiveCourseSession::factory()->create(['course_id' => $course->id]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn($course->title);

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(false);

            $this->notificationService->shouldReceive('send')
                ->zeroOrMoreTimes();

            $this->service->sendCompletedNotifications($session);

            expect(true)->toBeTrue();
        });

        it('logs error when notification fails', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة أكاديمية');

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(true);

            $this->settingsService->shouldReceive('getSessionType')
                ->andReturn('academic');

            $this->notificationService->shouldReceive('send')
                ->andThrow(new \Exception('Send failed'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'Failed to send session completed notifications');
                });

            $this->service->sendCompletedNotifications($session);

            expect(true)->toBeTrue();
        });
    });

    describe('sendAbsentNotifications()', function () {
        it('sends absent notification to student and parents', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $parentProfile = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);

            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(true);

            $this->settingsService->shouldReceive('getSessionType')
                ->andReturn('quran');

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة قرآنية');

            $this->parentNotificationService->shouldReceive('getParentsForStudent')
                ->andReturn(collect([$parentProfile]));

            $this->notificationService->shouldReceive('send')
                ->atLeast()->once();

            $this->service->sendAbsentNotifications($session);

            expect(true)->toBeTrue();
        });

        it('does not send notifications for non-individual sessions', function () {
            $session = QuranSession::factory()->group()->create([
                'academy_id' => $this->academy->id,
            ]);

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(false);

            $this->notificationService->shouldNotReceive('send');

            $this->service->sendAbsentNotifications($session);

            expect(true)->toBeTrue();
        });

        it('does not send notifications when student is null', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'student_id' => null,
            ]);

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(true);

            $this->notificationService->shouldNotReceive('send');

            $this->service->sendAbsentNotifications($session);

            expect(true)->toBeTrue();
        });

        it('logs error when notification fails', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(true);

            $this->settingsService->shouldReceive('getSessionType')
                ->andReturn('quran');

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة قرآنية');

            $this->notificationService->shouldReceive('send')
                ->andThrow(new \Exception('Failed to send'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) use ($session, $student) {
                    return str_contains($message, 'Failed to send absent notifications')
                        && $context['session_id'] === $session->id
                        && $context['student_id'] === $student->id;
                });

            $this->service->sendAbsentNotifications($session);

            expect(true)->toBeTrue();
        });

        it('sends notifications to multiple parents', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $parents = ParentProfile::factory()->count(2)->create(['academy_id' => $this->academy->id]);

            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $this->settingsService->shouldReceive('isIndividualSession')
                ->andReturn(true);

            $this->settingsService->shouldReceive('getSessionType')
                ->andReturn('quran');

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn('جلسة قرآنية');

            $this->parentNotificationService->shouldReceive('getParentsForStudent')
                ->andReturn($parents);

            $this->notificationService->shouldReceive('send')
                ->atLeast()->once();

            $this->service->sendAbsentNotifications($session);

            expect(true)->toBeTrue();
        });
    });

    describe('protected method behaviors', function () {
        it('handles interactive course without enrollments gracefully', function () {
            $course = InteractiveCourse::factory()->create();
            $session = InteractiveCourseSession::factory()->create(['course_id' => $course->id]);

            $this->settingsService->shouldReceive('getSessionTitle')
                ->andReturn($course->title);

            $this->notificationService->shouldReceive('send')
                ->zeroOrMoreTimes();

            $this->service->sendReadyNotifications($session);

            expect(true)->toBeTrue();
        });

        it('handles all session types without errors', function () {
            $quranSession = QuranSession::factory()->create(['academy_id' => $this->academy->id]);
            $academicSession = AcademicSession::factory()->create(['academy_id' => $this->academy->id]);

            $this->settingsService->shouldReceive('getSessionTitle')->andReturn('Test Session');
            $this->settingsService->shouldReceive('isIndividualSession')->andReturn(true);
            $this->notificationService->shouldReceive('send')->zeroOrMoreTimes();
            $this->parentNotificationService->shouldReceive('sendSessionReminder')->zeroOrMoreTimes();
            $this->notificationService->shouldReceive('sendSessionReminderNotification')->zeroOrMoreTimes();

            $this->service->sendReadyNotifications($quranSession);
            $this->service->sendReadyNotifications($academicSession);
            $this->service->sendStartedNotifications($quranSession);
            $this->service->sendCompletedNotifications($quranSession);

            expect(true)->toBeTrue();
        });
    });
});
