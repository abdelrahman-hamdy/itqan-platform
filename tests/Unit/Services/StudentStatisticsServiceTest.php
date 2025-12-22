<?php

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\InteractiveCourseSession;
use App\Models\QuizAssignment;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentStatisticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

describe('StudentStatisticsService', function () {
    beforeEach(function () {
        $this->service = new StudentStatisticsService();
        $this->academy = Academy::factory()->create();
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();

        // Create grade level for student profile
        $gradeLevel = \App\Models\AcademicGradeLevel::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        $this->studentProfile = StudentProfile::factory()->create([
            'user_id' => $this->student->id,
            'grade_level_id' => $gradeLevel->id,
        ]);
    });

    describe('calculate()', function () {
        it('returns complete statistics structure', function () {
            $stats = $this->service->calculate($this->student);

            expect($stats)->toBeArray()
                ->and($stats)->toHaveKeys([
                    'nextSessionText',
                    'nextSessionIcon',
                    'nextSessionDate',
                    'pendingHomework',
                    'pendingQuizzes',
                    'todayLearningHours',
                    'todayLearningMinutes',
                    'attendanceRate',
                    'totalCompletedSessions',
                    'activeCourses',
                    'activeInteractiveCourses',
                    'activeRecordedCourses',
                    'quranProgress',
                    'quranPages',
                    'quranTrialRequestsCount',
                    'activeQuranSubscriptions',
                    'quranCirclesCount',
                ]);
        });

        it('returns zeros when student has no data', function () {
            $stats = $this->service->calculate($this->student);

            expect($stats['pendingHomework'])->toBe(0)
                ->and($stats['pendingQuizzes'])->toBe(0)
                ->and($stats['todayLearningHours'])->toBe(0.0)
                ->and($stats['todayLearningMinutes'])->toBe(0)
                ->and($stats['attendanceRate'])->toBe(0)
                ->and($stats['totalCompletedSessions'])->toBe(0)
                ->and($stats['activeCourses'])->toBe(0)
                ->and($stats['quranProgress'])->toBe(0.0)
                ->and($stats['quranPages'])->toBe(0);
        });
    });

    describe('getNextSessionInfo()', function () {
        it('returns no sessions message when no upcoming sessions exist', function () {
            $stats = $this->service->calculate($this->student);

            expect($stats['nextSessionText'])->toBe('لا توجد جلسات قادمة')
                ->and($stats['nextSessionIcon'])->toBe('heroicon-o-calendar')
                ->and($stats['nextSessionDate'])->toBeNull();
        });

        it('returns quran session as next when it is soonest', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addHour(),
                'status' => SessionStatus::SCHEDULED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['nextSessionIcon'])->toBe('heroicon-o-book-open')
                ->and($stats['nextSessionDate'])->not->toBeNull();
        });

        it('returns academic session as next when it is soonest', function () {
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addMinutes(30),
                'status' => SessionStatus::SCHEDULED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['nextSessionIcon'])->toBe('heroicon-o-academic-cap')
                ->and($stats['nextSessionDate'])->not->toBeNull();
        });

        it('returns interactive course session as next when it is soonest', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'course_id' => $course->id,
                'student_id' => $this->studentProfile->id,
                'enrollment_status' => 'enrolled',
            ]);

            InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addMinutes(15),
                'status' => SessionStatus::SCHEDULED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['nextSessionIcon'])->toBe('heroicon-o-video-camera')
                ->and($stats['nextSessionDate'])->not->toBeNull();
        });

        it('returns soonest session when multiple sessions exist', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addHours(3),
                'status' => SessionStatus::SCHEDULED,
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addMinutes(30),
                'status' => SessionStatus::SCHEDULED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['nextSessionIcon'])->toBe('heroicon-o-academic-cap');
        });

        it('ignores past sessions', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->subHour(),
                'status' => SessionStatus::COMPLETED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['nextSessionText'])->toBe('لا توجد جلسات قادمة');
        });

        it('only includes scheduled and ready sessions', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addHour(),
                'status' => SessionStatus::ONGOING,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addHours(2),
                'status' => SessionStatus::READY,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['nextSessionIcon'])->toBe('heroicon-o-book-open');
        });
    });

    describe('formatSessionTimeText()', function () {
        it('formats time in minutes when less than 1 hour', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addMinutes(30),
                'status' => SessionStatus::SCHEDULED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['nextSessionText'])->toContain('دقيقة');
        });

        it('formats time in hours when less than 24 hours', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addHours(5),
                'status' => SessionStatus::SCHEDULED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['nextSessionText'])->toContain('ساعة');
        });

        it('formats time in days when more than 24 hours', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addDays(2),
                'status' => SessionStatus::SCHEDULED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['nextSessionText'])->toContain('يوم');
        });
    });

    describe('countPendingHomework()', function () {
        it('counts pending quran homework with homework_assigned', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::COMPLETED,
                'homework_assigned' => json_encode(['memorization' => true]),
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['pendingHomework'])->toBe(1);
        });

        it('counts pending academic homework', function () {
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::COMPLETED,
                'homework_assigned' => true,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['pendingHomework'])->toBe(1);
        });

        it('counts quran homework with homework_details field', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::COMPLETED,
                'homework_details' => 'حفظ سورة الفاتحة',
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['pendingHomework'])->toBe(1);
        });

        it('counts academic homework with description field', function () {
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::COMPLETED,
                'homework_description' => 'حل التمارين',
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['pendingHomework'])->toBe(1);
        });

        it('does not count homework from non-completed sessions', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
                'homework_assigned' => json_encode(['memorization' => true]),
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['pendingHomework'])->toBe(0);
        });

        it('combines quran and academic homework counts', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::COMPLETED,
                'homework_assigned' => json_encode(['memorization' => true]),
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::COMPLETED,
                'homework_assigned' => true,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['pendingHomework'])->toBe(2);
        });
    });

    describe('countPendingQuizzes()', function () {
        it('returns zero when student has no profile', function () {
            $studentWithoutProfile = User::factory()->student()->forAcademy($this->academy)->create();

            $stats = $this->service->calculate($studentWithoutProfile);

            expect($stats['pendingQuizzes'])->toBe(0);
        });

        it('returns zero when no quizzes exist', function () {
            $stats = $this->service->calculate($this->student);

            expect($stats['pendingQuizzes'])->toBe(0);
        });

        it('returns zero when exception occurs', function () {
            Log::shouldReceive('warning')->once();

            $stats = $this->service->calculate($this->student);

            expect($stats['pendingQuizzes'])->toBeInt();
        });
    });

    describe('calculateAttendanceRate()', function () {
        it('returns zero when no attendance records exist', function () {
            $stats = $this->service->calculate($this->student);

            expect($stats['attendanceRate'])->toBe(0);
        });

        it('counts late status as present', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
            ]);

            \App\Models\QuranSessionAttendance::create([
                'student_id' => $this->student->id,
                'attendanceable_id' => $session->id,
                'attendanceable_type' => QuranSession::class,
                'attendance_status' => AttendanceStatus::LATE,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['attendanceRate'])->toBe(100);
        });

        it('counts leaved status as present', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
            ]);

            \App\Models\QuranSessionAttendance::create([
                'student_id' => $this->student->id,
                'attendanceable_id' => $session->id,
                'attendanceable_type' => QuranSession::class,
                'attendance_status' => AttendanceStatus::LEAVED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['attendanceRate'])->toBe(100);
        });
    });

    describe('calculateTodayLearning()', function () {
        it('returns zero hours and minutes when no sessions today', function () {
            $stats = $this->service->calculate($this->student);

            expect($stats['todayLearningHours'])->toBe(0.0)
                ->and($stats['todayLearningMinutes'])->toBe(0);
        });

        it('calculates quran session time today', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now(),
                'duration_minutes' => 45,
                'status' => SessionStatus::COMPLETED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['todayLearningMinutes'])->toBe(45)
                ->and($stats['todayLearningHours'])->toBe(0.8);
        });

        it('calculates academic session time today', function () {
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::COMPLETED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['todayLearningMinutes'])->toBe(60)
                ->and($stats['todayLearningHours'])->toBe(1.0);
        });

        it('calculates interactive course session time today', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'course_id' => $course->id,
                'student_id' => $this->studentProfile->id,
                'enrollment_status' => 'enrolled',
            ]);

            InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now(),
                'duration_minutes' => 90,
                'status' => SessionStatus::COMPLETED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['todayLearningMinutes'])->toBe(90)
                ->and($stats['todayLearningHours'])->toBe(1.5);
        });

        it('sums all session types for today', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'course_id' => $course->id,
                'student_id' => $this->studentProfile->id,
                'enrollment_status' => 'enrolled',
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now(),
                'duration_minutes' => 45,
                'status' => SessionStatus::COMPLETED,
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now(),
                'duration_minutes' => 60,
                'status' => SessionStatus::COMPLETED,
            ]);

            InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now(),
                'duration_minutes' => 30,
                'status' => SessionStatus::COMPLETED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['todayLearningMinutes'])->toBe(135)
                ->and($stats['todayLearningHours'])->toBe(2.3);
        });

        it('ignores sessions from yesterday', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->subDay(),
                'duration_minutes' => 45,
                'status' => SessionStatus::COMPLETED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['todayLearningMinutes'])->toBe(0);
        });

        it('includes scheduled, ready, ongoing and completed sessions', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now(),
                'duration_minutes' => 45,
                'status' => SessionStatus::SCHEDULED,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now(),
                'duration_minutes' => 45,
                'status' => SessionStatus::READY,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now(),
                'duration_minutes' => 45,
                'status' => SessionStatus::ONGOING,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now(),
                'duration_minutes' => 45,
                'status' => SessionStatus::COMPLETED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['todayLearningMinutes'])->toBe(180);
        });

        it('excludes cancelled sessions', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now(),
                'duration_minutes' => 45,
                'status' => SessionStatus::CANCELLED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['todayLearningMinutes'])->toBe(0);
        });

        it('rounds hours to one decimal place', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now(),
                'duration_minutes' => 50, // 0.833... hours
                'status' => SessionStatus::COMPLETED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['todayLearningHours'])->toBe(0.8);
        });
    });

    describe('countCompletedSessions()', function () {
        it('returns zero for all types when no completed sessions exist', function () {
            $stats = $this->service->calculate($this->student);

            expect($stats['totalCompletedSessions'])->toBe(0);
        });

        it('counts completed quran sessions', function () {
            QuranSession::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['totalCompletedSessions'])->toBe(3);
        });

        it('counts completed academic sessions', function () {
            AcademicSession::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['totalCompletedSessions'])->toBe(2);
        });

        it('counts completed interactive sessions', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'course_id' => $course->id,
                'student_id' => $this->studentProfile->id,
                'enrollment_status' => 'enrolled',
            ]);

            InteractiveCourseSession::factory()->count(4)->create([
                'course_id' => $course->id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['totalCompletedSessions'])->toBe(4);
        });

        it('sums all completed session types', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'course_id' => $course->id,
                'student_id' => $this->studentProfile->id,
                'enrollment_status' => 'enrolled',
            ]);

            QuranSession::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            AcademicSession::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            InteractiveCourseSession::factory()->count(1)->create([
                'course_id' => $course->id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['totalCompletedSessions'])->toBe(6);
        });

        it('excludes non-completed sessions', function () {
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::ONGOING,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::CANCELLED,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['totalCompletedSessions'])->toBe(0);
        });
    });

    describe('countActiveCourses()', function () {
        it('returns zero when no active courses exist', function () {
            $stats = $this->service->calculate($this->student);

            expect($stats['activeCourses'])->toBe(0)
                ->and($stats['activeInteractiveCourses'])->toBe(0)
                ->and($stats['activeRecordedCourses'])->toBe(0);
        });

        it('counts active interactive courses', function () {
            $course1 = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $course2 = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'course_id' => $course1->id,
                'student_id' => $this->studentProfile->id,
                'enrollment_status' => 'enrolled',
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'course_id' => $course2->id,
                'student_id' => $this->studentProfile->id,
                'enrollment_status' => 'enrolled',
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['activeInteractiveCourses'])->toBe(2);
        });

        it('counts completed interactive courses', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'course_id' => $course->id,
                'student_id' => $this->studentProfile->id,
                'enrollment_status' => 'completed',
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['activeInteractiveCourses'])->toBe(1);
        });

        it('sums interactive and recorded courses', function () {
            $interactiveCourse = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'course_id' => $interactiveCourse->id,
                'student_id' => $this->studentProfile->id,
                'enrollment_status' => 'enrolled',
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['activeCourses'])->toBeGreaterThanOrEqual(1);
        });
    });

    describe('calculateQuranProgress()', function () {
        it('returns zeros when no quran data exists', function () {
            $stats = $this->service->calculate($this->student);

            expect($stats['quranProgress'])->toBe(0.0)
                ->and($stats['quranPages'])->toBe(0)
                ->and($stats['quranTrialRequestsCount'])->toBe(0)
                ->and($stats['activeQuranSubscriptions'])->toBe(0)
                ->and($stats['quranCirclesCount'])->toBe(0);
        });

        it('calculates average progress from subscriptions', function () {
            QuranSubscription::factory()->create([
                'student_id' => $this->student->id,
                'academy_id' => $this->academy->id,
                'progress_percentage' => 50,
            ]);

            QuranSubscription::factory()->create([
                'student_id' => $this->student->id,
                'academy_id' => $this->academy->id,
                'progress_percentage' => 70,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['quranProgress'])->toBe(60.0);
        });

        it('counts active quran subscriptions', function () {
            QuranSubscription::factory()->create([
                'student_id' => $this->student->id,
                'academy_id' => $this->academy->id,
                'status' => SubscriptionStatus::ACTIVE->value,
            ]);

            QuranSubscription::factory()->create([
                'student_id' => $this->student->id,
                'academy_id' => $this->academy->id,
                'status' => SubscriptionStatus::ACTIVE->value,
            ]);

            QuranSubscription::factory()->create([
                'student_id' => $this->student->id,
                'academy_id' => $this->academy->id,
                'status' => SubscriptionStatus::EXPIRED->value,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['activeQuranSubscriptions'])->toBe(2);
        });

        it('counts quran circles student is enrolled in', function () {
            $circle1 = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle2 = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle1->students()->attach($this->student->id, ['enrolled_at' => now()]);
            $circle2->students()->attach($this->student->id, ['enrolled_at' => now()]);

            $stats = $this->service->calculate($this->student);

            expect($stats['quranCirclesCount'])->toBe(2);
        });

        it('rounds progress to one decimal place', function () {
            QuranSubscription::factory()->create([
                'student_id' => $this->student->id,
                'academy_id' => $this->academy->id,
                'progress_percentage' => 33.333,
            ]);

            QuranSubscription::factory()->create([
                'student_id' => $this->student->id,
                'academy_id' => $this->academy->id,
                'progress_percentage' => 66.666,
            ]);

            $stats = $this->service->calculate($this->student);

            expect($stats['quranProgress'])->toBe(50.0);
        });
    });
});
