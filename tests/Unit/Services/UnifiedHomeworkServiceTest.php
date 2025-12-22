<?php

use App\Models\Academy;
use App\Models\AcademicHomework;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\HomeworkSubmission;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\InteractiveCourseHomework;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use App\Models\QuranSubscription;
use App\Models\Student;
use App\Models\StudentSessionReport;
use App\Models\User;
use App\Services\UnifiedHomeworkService;
use Illuminate\Support\Facades\Route;

describe('UnifiedHomeworkService', function () {
    beforeEach(function () {
        $this->service = new UnifiedHomeworkService();
        $this->academy = Academy::factory()->create();
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();

        Route::shouldReceive('route')->andReturn('http://test.url');
    });

    describe('getStudentHomework()', function () {
        it('returns empty collection when student has no homework', function () {
            $homework = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id
            );

            expect($homework)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($homework)->toBeEmpty();
        });

        it('returns academic homework for student', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            $academicHomework = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'title' => 'Math Assignment',
            ]);

            $homework = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id
            );

            expect($homework)->not->toBeEmpty()
                ->and($homework->first()['type'])->toBe('academic')
                ->and($homework->first()['title'])->toBe('Math Assignment');
        });

        it('returns interactive course homework for student', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $teacher->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'course_id' => $course->id,
                'student_id' => $this->student->id,
            ]);

            $session = InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
            ]);

            $interactiveHomework = InteractiveCourseHomework::create([
                'academy_id' => $this->academy->id,
                'session_id' => $session->id,
                'title' => 'Course Assignment',
                'description' => 'Complete this assignment',
                'due_date' => now()->addWeek(),
            ]);

            $homework = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id
            );

            expect($homework)->not->toBeEmpty()
                ->and($homework->first()['type'])->toBe('interactive')
                ->and($homework->first()['title'])->toBe('Course Assignment');
        });

        it('returns quran homework for student', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
            ]);

            $session->update(['homework_assigned' => ['assigned' => true]]);
            $session->update(['homework_details' => 'Test homework']);

            QuranSessionHomework::factory()->create([
                'session_id' => $session->id,
                'has_new_memorization' => true,
                'new_memorization_pages' => 2,
            ]);

            $homework = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id
            );

            expect($homework)->not->toBeEmpty()
                ->and($homework->first()['type'])->toBe('quran')
                ->and($homework->first()['is_view_only'])->toBeTrue();
        });

        it('filters homework by type when specified', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            $quranTeacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $quranSession = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $quranTeacher->id,
                'student_id' => $this->student->id,
            ]);

            $quranSession->update(['homework_assigned' => ['assigned' => true]]);
            $quranSession->update(['homework_details' => 'Test homework']);

            QuranSessionHomework::factory()->create([
                'session_id' => $quranSession->id,
                'has_new_memorization' => true,
            ]);

            $academicOnly = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id,
                null,
                'academic'
            );

            $quranOnly = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id,
                null,
                'quran'
            );

            expect($academicOnly->every(fn($hw) => $hw['type'] === 'academic'))->toBeTrue()
                ->and($quranOnly->every(fn($hw) => $hw['type'] === 'quran'))->toBeTrue();
        });

        it('filters homework by status when specified', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            $hw1 = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            $hw2 = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            $submission1 = HomeworkSubmission::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'submitable_type' => AcademicHomework::class,
                'submitable_id' => $hw1->id,
                'homework_type' => 'academic',
                'submission_status' => 'not_started',
                'due_date' => now()->addWeek(),
            ]);

            $submission2 = HomeworkSubmission::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'submitable_type' => AcademicHomework::class,
                'submitable_id' => $hw2->id,
                'homework_type' => 'academic',
                'submission_status' => 'submitted',
                'submitted_at' => now(),
                'due_date' => now()->addWeek(),
            ]);

            $pendingHomework = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id,
                'pending'
            );

            $submittedHomework = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id,
                'submitted'
            );

            expect($pendingHomework->count())->toBeGreaterThanOrEqual(1)
                ->and($submittedHomework->count())->toBeGreaterThanOrEqual(1);
        });

        it('sorts homework by due date with upcoming first', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'title' => 'Future Assignment',
                'due_date' => now()->addWeek(),
            ]);

            AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'title' => 'Past Assignment',
                'due_date' => now()->subDay(),
            ]);

            AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'title' => 'Soon Assignment',
                'due_date' => now()->addDay(),
            ]);

            $homework = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id
            );

            expect($homework->count())->toBe(3)
                ->and($homework->first()['title'])->toBe('Soon Assignment');
        });

        it('creates homework submission records automatically', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            $homeworkBefore = HomeworkSubmission::count();

            $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id
            );

            $homeworkAfter = HomeworkSubmission::count();

            expect($homeworkAfter)->toBeGreaterThan($homeworkBefore);
        });
    });

    describe('getStudentHomeworkStatistics()', function () {
        it('returns correct statistics structure', function () {
            $stats = $this->service->getStudentHomeworkStatistics(
                $this->student->id,
                $this->academy->id
            );

            expect($stats)->toHaveKeys([
                'total',
                'pending',
                'submitted',
                'graded',
                'overdue',
                'late',
                'average_score',
                'completion_rate',
                'type_breakdown',
            ]);
        });

        it('returns zero counts when student has no homework', function () {
            $stats = $this->service->getStudentHomeworkStatistics(
                $this->student->id,
                $this->academy->id
            );

            expect($stats['total'])->toBe(0)
                ->and($stats['pending'])->toBe(0)
                ->and($stats['submitted'])->toBe(0)
                ->and($stats['graded'])->toBe(0)
                ->and($stats['completion_rate'])->toBe(0);
        });

        it('calculates total homework count correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            AcademicHomework::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            $stats = $this->service->getStudentHomeworkStatistics(
                $this->student->id,
                $this->academy->id
            );

            expect($stats['total'])->toBe(3);
        });

        it('counts pending homework correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            $hw = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            HomeworkSubmission::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'submitable_type' => AcademicHomework::class,
                'submitable_id' => $hw->id,
                'homework_type' => 'academic',
                'submission_status' => 'not_started',
                'due_date' => now()->addWeek(),
            ]);

            $stats = $this->service->getStudentHomeworkStatistics(
                $this->student->id,
                $this->academy->id
            );

            expect($stats['pending'])->toBeGreaterThanOrEqual(1);
        });

        it('counts submitted homework correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            $hw = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            HomeworkSubmission::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'submitable_type' => AcademicHomework::class,
                'submitable_id' => $hw->id,
                'homework_type' => 'academic',
                'submission_status' => 'submitted',
                'submitted_at' => now(),
                'due_date' => now()->addWeek(),
            ]);

            $stats = $this->service->getStudentHomeworkStatistics(
                $this->student->id,
                $this->academy->id
            );

            expect($stats['submitted'])->toBeGreaterThanOrEqual(1);
        });

        it('counts graded homework correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            $hw = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            HomeworkSubmission::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'submitable_type' => AcademicHomework::class,
                'submitable_id' => $hw->id,
                'homework_type' => 'academic',
                'submission_status' => 'graded',
                'submitted_at' => now()->subDay(),
                'graded_at' => now(),
                'score' => 85,
                'max_score' => 100,
                'score_percentage' => 85,
                'due_date' => now()->addWeek(),
            ]);

            $stats = $this->service->getStudentHomeworkStatistics(
                $this->student->id,
                $this->academy->id
            );

            expect($stats['graded'])->toBeGreaterThanOrEqual(1);
        });

        it('counts overdue homework correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            $hw = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'due_date' => now()->subDay(),
            ]);

            HomeworkSubmission::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'submitable_type' => AcademicHomework::class,
                'submitable_id' => $hw->id,
                'homework_type' => 'academic',
                'submission_status' => 'not_started',
                'due_date' => now()->subDay(),
            ]);

            $stats = $this->service->getStudentHomeworkStatistics(
                $this->student->id,
                $this->academy->id
            );

            expect($stats['overdue'])->toBeGreaterThanOrEqual(1);
        });

        it('counts late submissions correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            $hw = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'due_date' => now()->subDay(),
            ]);

            HomeworkSubmission::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'submitable_type' => AcademicHomework::class,
                'submitable_id' => $hw->id,
                'homework_type' => 'academic',
                'submission_status' => 'late',
                'submitted_at' => now(),
                'is_late' => true,
                'days_late' => 1,
                'due_date' => now()->subDay(),
            ]);

            $stats = $this->service->getStudentHomeworkStatistics(
                $this->student->id,
                $this->academy->id
            );

            expect($stats['late'])->toBeGreaterThanOrEqual(1);
        });

        it('calculates average score correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            $hw1 = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            $hw2 = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            HomeworkSubmission::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'submitable_type' => AcademicHomework::class,
                'submitable_id' => $hw1->id,
                'homework_type' => 'academic',
                'submission_status' => 'graded',
                'score_percentage' => 80,
                'due_date' => now()->addWeek(),
            ]);

            HomeworkSubmission::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'submitable_type' => AcademicHomework::class,
                'submitable_id' => $hw2->id,
                'homework_type' => 'academic',
                'submission_status' => 'graded',
                'score_percentage' => 90,
                'due_date' => now()->addWeek(),
            ]);

            $stats = $this->service->getStudentHomeworkStatistics(
                $this->student->id,
                $this->academy->id
            );

            expect($stats['average_score'])->toBe(85.0);
        });

        it('returns null average score when no graded homework exists', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            $stats = $this->service->getStudentHomeworkStatistics(
                $this->student->id,
                $this->academy->id
            );

            expect($stats['average_score'])->toBeNull();
        });

        it('calculates completion rate correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            $hw1 = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            $hw2 = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            HomeworkSubmission::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'submitable_type' => AcademicHomework::class,
                'submitable_id' => $hw1->id,
                'homework_type' => 'academic',
                'submission_status' => 'submitted',
                'submitted_at' => now(),
                'due_date' => now()->addWeek(),
            ]);

            HomeworkSubmission::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'submitable_type' => AcademicHomework::class,
                'submitable_id' => $hw2->id,
                'homework_type' => 'academic',
                'submission_status' => 'not_started',
                'due_date' => now()->addWeek(),
            ]);

            $stats = $this->service->getStudentHomeworkStatistics(
                $this->student->id,
                $this->academy->id
            );

            expect($stats['completion_rate'])->toBe(50.0);
        });

        it('provides type breakdown correctly', function () {
            $academicTeacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $academicTeacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $academicSession = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $academicTeacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            AcademicHomework::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $academicSession->id,
                'teacher_id' => $academicTeacher->id,
            ]);

            $quranTeacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $quranSession = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $quranTeacher->id,
                'student_id' => $this->student->id,
            ]);

            $quranSession->update(['homework_assigned' => ['assigned' => true]]);
            $quranSession->update(['homework_details' => 'Test homework']);

            QuranSessionHomework::factory()->create([
                'session_id' => $quranSession->id,
                'has_new_memorization' => true,
            ]);

            $stats = $this->service->getStudentHomeworkStatistics(
                $this->student->id,
                $this->academy->id
            );

            expect($stats['type_breakdown'])->toHaveKeys(['academic', 'interactive', 'quran'])
                ->and($stats['type_breakdown']['academic'])->toBe(2)
                ->and($stats['type_breakdown']['quran'])->toBe(1)
                ->and($stats['type_breakdown']['interactive'])->toBe(0);
        });
    });

    describe('formatAcademicHomework()', function () {
        it('includes all required fields in formatted homework', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $teacher->id,
                'subject_id' => 1,
                'grade_level_id' => 1,
                'subscription_type' => 'individual_lessons',
                'status' => 'active',
                'start_date' => now(),
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 10,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $subscription->id,
            ]);

            AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);

            $homework = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id,
                null,
                'academic'
            );

            $formatted = $homework->first();

            expect($formatted)->toHaveKeys([
                'id',
                'type',
                'submission_id',
                'title',
                'description',
                'instructions',
                'due_date',
                'created_at',
                'submission_status',
                'submission_status_text',
                'submitted_at',
                'is_late',
                'days_late',
                'is_overdue',
                'score',
                'max_score',
                'score_percentage',
                'grade_letter',
                'performance_level',
                'teacher_feedback',
                'graded_at',
                'progress_percentage',
                'can_submit',
                'hours_until_due',
                'session_id',
                'session_title',
                'teacher_name',
                'teacher_avatar',
                'teacher_gender',
                'teacher_type',
                'view_url',
                'submit_url',
            ]);
        });
    });

    describe('formatInteractiveHomework()', function () {
        it('includes all required fields in formatted homework', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $teacher->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'course_id' => $course->id,
                'student_id' => $this->student->id,
            ]);

            $session = InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
            ]);

            InteractiveCourseHomework::create([
                'academy_id' => $this->academy->id,
                'session_id' => $session->id,
                'title' => 'Course Assignment',
                'description' => 'Complete this assignment',
                'due_date' => now()->addWeek(),
            ]);

            $homework = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id,
                null,
                'interactive'
            );

            $formatted = $homework->first();

            expect($formatted)->toHaveKeys([
                'id',
                'type',
                'submission_id',
                'title',
                'description',
                'instructions',
                'due_date',
                'created_at',
                'submission_status',
                'submission_status_text',
                'submitted_at',
                'is_late',
                'days_late',
                'is_overdue',
                'score',
                'max_score',
                'score_percentage',
                'grade_letter',
                'performance_level',
                'teacher_feedback',
                'graded_at',
                'progress_percentage',
                'can_submit',
                'hours_until_due',
                'session_id',
                'session_title',
                'course_title',
                'teacher_name',
                'teacher_avatar',
                'teacher_gender',
                'teacher_type',
                'view_url',
                'submit_url',
            ])
                ->and($formatted['type'])->toBe('interactive');
        });
    });

    describe('formatQuranHomework()', function () {
        it('includes all required fields in formatted homework', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
            ]);

            $session->update(['homework_assigned' => ['assigned' => true]]);
            $session->update(['homework_details' => 'Test homework']);

            QuranSessionHomework::factory()->create([
                'session_id' => $session->id,
                'has_new_memorization' => true,
                'new_memorization_pages' => 2,
            ]);

            $homework = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id,
                null,
                'quran'
            );

            $formatted = $homework->first();

            expect($formatted)->toHaveKeys([
                'id',
                'type',
                'submission_id',
                'title',
                'description',
                'instructions',
                'due_date',
                'created_at',
                'submission_status',
                'submission_status_text',
                'submitted_at',
                'is_late',
                'days_late',
                'is_overdue',
                'hours_until_due',
                'score',
                'max_score',
                'score_percentage',
                'grade_letter',
                'performance_level',
                'teacher_feedback',
                'graded_at',
                'progress_percentage',
                'can_submit',
                'is_view_only',
                'session_id',
                'session_title',
                'teacher_name',
                'teacher_avatar',
                'teacher_gender',
                'teacher_type',
                'homework_details',
                'view_url',
                'submit_url',
            ])
                ->and($formatted['type'])->toBe('quran')
                ->and($formatted['is_view_only'])->toBeTrue()
                ->and($formatted['can_submit'])->toBeFalse()
                ->and($formatted['submission_id'])->toBeNull();
        });

        it('maps evaluation to score percentage correctly', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
            ]);

            $session->update(['homework_assigned' => ['assigned' => true]]);
            $session->update(['homework_details' => 'Test homework']);

            QuranSessionHomework::factory()->create([
                'session_id' => $session->id,
                'has_new_memorization' => true,
            ]);

            StudentSessionReport::factory()->create([
                'session_id' => $session->id,
                'session_type' => QuranSession::class,
                'student_id' => $this->student->id,
                'evaluation' => 'excellent',
            ]);

            $homework = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id,
                null,
                'quran'
            );

            $formatted = $homework->first();

            expect($formatted['score_percentage'])->toBe(95)
                ->and($formatted['grade_letter'])->toBe('A+')
                ->and($formatted['submission_status'])->toBe('graded');
        });

        it('includes homework details for quran homework', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
            ]);

            $session->update(['homework_assigned' => ['assigned' => true]]);
            $session->update(['homework_details' => 'Test homework']);

            QuranSessionHomework::factory()->create([
                'session_id' => $session->id,
                'has_new_memorization' => true,
                'new_memorization_pages' => 2,
                'has_review' => true,
                'review_pages' => 3,
                'difficulty_level' => 'medium',
            ]);

            $homework = $this->service->getStudentHomework(
                $this->student->id,
                $this->academy->id,
                null,
                'quran'
            );

            $formatted = $homework->first();

            expect($formatted['homework_details'])->toHaveKeys([
                'has_new_memorization',
                'new_memorization_pages',
                'new_memorization_range',
                'has_review',
                'review_pages',
                'review_range',
                'has_comprehensive_review',
                'difficulty_level',
            ])
                ->and($formatted['homework_details']['has_new_memorization'])->toBeTrue()
                ->and($formatted['homework_details']['has_review'])->toBeTrue();
        });
    });
});
