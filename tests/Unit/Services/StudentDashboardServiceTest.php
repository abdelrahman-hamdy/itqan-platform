<?php

use App\Models\Academy;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\RecordedCourse;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentDashboardService;
use Illuminate\Support\Collection;

describe('StudentDashboardService', function () {
    beforeEach(function () {
        $this->service = new StudentDashboardService();
        $this->academy = Academy::factory()->create();
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
        $this->studentProfile = StudentProfile::factory()->create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->student->id,
        ]);
    });

    describe('loadDashboardData()', function () {
        it('returns all required dashboard data keys', function () {
            $data = $this->service->loadDashboardData($this->student);

            expect($data)->toBeArray()
                ->and($data)->toHaveKeys([
                    'circles',
                    'privateSessions',
                    'trialRequests',
                    'interactiveCourses',
                    'recordedCourses',
                ]);
        });

        it('returns collections for all data keys', function () {
            $data = $this->service->loadDashboardData($this->student);

            expect($data['circles'])->toBeInstanceOf(Collection::class)
                ->and($data['privateSessions'])->toBeInstanceOf(Collection::class)
                ->and($data['trialRequests'])->toBeInstanceOf(Collection::class)
                ->and($data['interactiveCourses'])->toBeInstanceOf(Collection::class)
                ->and($data['recordedCourses'])->toBeInstanceOf(Collection::class);
        });

        it('returns empty collections when no data exists', function () {
            $data = $this->service->loadDashboardData($this->student);

            expect($data['circles'])->toBeEmpty()
                ->and($data['privateSessions'])->toBeEmpty()
                ->and($data['trialRequests'])->toBeEmpty()
                ->and($data['interactiveCourses'])->toBeEmpty()
                ->and($data['recordedCourses'])->toBeEmpty();
        });

        it('aggregates all data types when they exist', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            // Create a circle
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);
            $circle->students()->attach($this->student->id);

            // Create a private subscription
            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'individual',
                'status' => 'active',
                'quran_individual_circle_id' => $individualCircle->id,
                'quran_teacher_id' => $teacherProfile->id,
            ]);

            // Create a trial request
            QuranTrialRequest::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
            ]);

            // Create an interactive course enrollment
            $interactiveCourse = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->studentProfile->id,
                'interactive_course_id' => $interactiveCourse->id,
                'enrollment_status' => 'enrolled',
            ]);

            $data = $this->service->loadDashboardData($this->student);

            expect($data['circles'])->not->toBeEmpty()
                ->and($data['privateSessions'])->not->toBeEmpty()
                ->and($data['trialRequests'])->not->toBeEmpty()
                ->and($data['interactiveCourses'])->not->toBeEmpty();
        });
    });

    describe('getQuranCircles()', function () {
        it('returns empty collection when student has no circles', function () {
            $circles = $this->service->getQuranCircles($this->student, $this->academy);

            expect($circles)->toBeInstanceOf(Collection::class)
                ->and($circles)->toBeEmpty();
        });

        it('returns circles where student is enrolled', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);
            $circle->students()->attach($this->student->id);

            $circles = $this->service->getQuranCircles($this->student, $this->academy);

            expect($circles)->toHaveCount(1)
                ->and($circles->first()->id)->toBe($circle->id);
        });

        it('attaches teacher data to circles', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);
            $circle->students()->attach($this->student->id);

            $circles = $this->service->getQuranCircles($this->student, $this->academy);

            expect($circles->first()->teacherData)->toBeInstanceOf(User::class)
                ->and($circles->first()->teacherData->id)->toBe($teacher->id);
        });

        it('only returns circles from the specified academy', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $otherAcademy = Academy::factory()->create();
            $otherTeacher = User::factory()->quranTeacher()->forAcademy($otherAcademy)->create();

            // Create circle in current academy
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);
            $circle->students()->attach($this->student->id);

            // Create circle in other academy
            $otherCircle = QuranCircle::factory()->create([
                'academy_id' => $otherAcademy->id,
                'quran_teacher_id' => $otherTeacher->id,
            ]);
            $otherCircle->students()->attach($this->student->id);

            $circles = $this->service->getQuranCircles($this->student, $this->academy);

            expect($circles)->toHaveCount(1)
                ->and($circles->first()->academy_id)->toBe($this->academy->id);
        });

        it('eager loads students relationship', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);
            $circle->students()->attach($this->student->id);

            $circles = $this->service->getQuranCircles($this->student, $this->academy);

            expect($circles->first()->relationLoaded('students'))->toBeTrue();
        });
    });

    describe('getQuranPrivateSessions()', function () {
        it('returns empty collection when no private sessions exist', function () {
            $sessions = $this->service->getQuranPrivateSessions($this->student, $this->academy);

            expect($sessions)->toBeInstanceOf(Collection::class)
                ->and($sessions)->toBeEmpty();
        });

        it('returns active individual subscriptions', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'individual',
                'status' => 'active',
                'quran_individual_circle_id' => $individualCircle->id,
                'quran_teacher_id' => $teacherProfile->id,
            ]);

            $sessions = $this->service->getQuranPrivateSessions($this->student, $this->academy);

            expect($sessions)->toHaveCount(1)
                ->and($sessions->first()->subscription_type)->toBe('individual')
                ->and($sessions->first()->status)->toBe('active');
        });

        it('excludes inactive subscriptions', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'individual',
                'status' => 'expired',
                'quran_individual_circle_id' => $individualCircle->id,
                'quran_teacher_id' => $teacherProfile->id,
            ]);

            $sessions = $this->service->getQuranPrivateSessions($this->student, $this->academy);

            expect($sessions)->toBeEmpty();
        });

        it('excludes circle subscriptions', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'circle',
                'status' => 'active',
                'quran_teacher_id' => $teacherProfile->id,
            ]);

            $sessions = $this->service->getQuranPrivateSessions($this->student, $this->academy);

            expect($sessions)->toBeEmpty();
        });

        it('attaches teacher data to subscriptions', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'individual',
                'status' => 'active',
                'quran_individual_circle_id' => $individualCircle->id,
                'quran_teacher_id' => $teacherProfile->id,
            ]);

            $sessions = $this->service->getQuranPrivateSessions($this->student, $this->academy);

            expect($sessions->first()->teacherData)->toBeInstanceOf(User::class)
                ->and($sessions->first()->teacherData->id)->toBe($teacher->id);
        });

        it('eager loads package, individualCircle, and sessions relationships', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'individual',
                'status' => 'active',
                'quran_individual_circle_id' => $individualCircle->id,
                'quran_teacher_id' => $teacherProfile->id,
            ]);

            $sessions = $this->service->getQuranPrivateSessions($this->student, $this->academy);

            expect($sessions->first()->relationLoaded('package'))->toBeTrue()
                ->and($sessions->first()->relationLoaded('individualCircle'))->toBeTrue()
                ->and($sessions->first()->relationLoaded('sessions'))->toBeTrue();
        });

        it('limits sessions to 5 most recent', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'individual',
                'status' => 'active',
                'quran_individual_circle_id' => $individualCircle->id,
                'quran_teacher_id' => $teacherProfile->id,
            ]);

            // Create 7 sessions
            for ($i = 0; $i < 7; $i++) {
                QuranSession::factory()->create([
                    'academy_id' => $this->academy->id,
                    'quran_subscription_id' => $subscription->id,
                    'student_id' => $this->student->id,
                    'scheduled_at' => now()->addDays($i),
                ]);
            }

            $sessions = $this->service->getQuranPrivateSessions($this->student, $this->academy);

            expect($sessions->first()->sessions)->toHaveCount(5);
        });

        it('only returns subscriptions from specified academy', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'individual',
                'status' => 'active',
                'quran_individual_circle_id' => $individualCircle->id,
                'quran_teacher_id' => $teacherProfile->id,
            ]);

            $otherAcademy = Academy::factory()->create();
            $otherTeacher = User::factory()->quranTeacher()->forAcademy($otherAcademy)->create();
            $otherTeacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $otherAcademy->id,
                'user_id' => $otherTeacher->id,
            ]);

            $otherIndividualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $otherAcademy->id,
                'quran_teacher_id' => $otherTeacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $otherAcademy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'individual',
                'status' => 'active',
                'quran_individual_circle_id' => $otherIndividualCircle->id,
                'quran_teacher_id' => $otherTeacherProfile->id,
            ]);

            $sessions = $this->service->getQuranPrivateSessions($this->student, $this->academy);

            expect($sessions)->toHaveCount(1)
                ->and($sessions->first()->academy_id)->toBe($this->academy->id);
        });

        it('excludes subscriptions with soft-deleted individual circles', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacherProfile->id,
                'student_id' => $this->student->id,
                'deleted_at' => now(),
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'individual',
                'status' => 'active',
                'quran_individual_circle_id' => $individualCircle->id,
                'quran_teacher_id' => $teacherProfile->id,
            ]);

            $sessions = $this->service->getQuranPrivateSessions($this->student, $this->academy);

            expect($sessions)->toBeEmpty();
        });
    });

    describe('getQuranTrialRequests()', function () {
        it('returns empty collection when no trial requests exist', function () {
            $requests = $this->service->getQuranTrialRequests($this->student, $this->academy);

            expect($requests)->toBeInstanceOf(Collection::class)
                ->and($requests)->toBeEmpty();
        });

        it('returns trial requests for the student', function () {
            QuranTrialRequest::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
            ]);

            $requests = $this->service->getQuranTrialRequests($this->student, $this->academy);

            expect($requests)->toHaveCount(1)
                ->and($requests->first()->student_id)->toBe($this->student->id);
        });

        it('eager loads teacher and trialSession relationships', function () {
            QuranTrialRequest::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
            ]);

            $requests = $this->service->getQuranTrialRequests($this->student, $this->academy);

            expect($requests->first()->relationLoaded('teacher'))->toBeTrue()
                ->and($requests->first()->relationLoaded('trialSession'))->toBeTrue();
        });

        it('orders by created_at descending', function () {
            $old = QuranTrialRequest::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'created_at' => now()->subDays(5),
            ]);

            $recent = QuranTrialRequest::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'created_at' => now(),
            ]);

            $requests = $this->service->getQuranTrialRequests($this->student, $this->academy);

            expect($requests->first()->id)->toBe($recent->id)
                ->and($requests->last()->id)->toBe($old->id);
        });

        it('limits results to 5 requests', function () {
            for ($i = 0; $i < 7; $i++) {
                QuranTrialRequest::factory()->create([
                    'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'created_at' => now()->subDays($i),
                ]);
            }

            $requests = $this->service->getQuranTrialRequests($this->student, $this->academy);

            expect($requests)->toHaveCount(5);
        });

        it('only returns requests from specified academy', function () {
            QuranTrialRequest::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
            ]);

            $otherAcademy = Academy::factory()->create();
            QuranTrialRequest::factory()->create([
                'academy_id' => $otherAcademy->id,
                'student_id' => $this->student->id,
            ]);

            $requests = $this->service->getQuranTrialRequests($this->student, $this->academy);

            expect($requests)->toHaveCount(1)
                ->and($requests->first()->academy_id)->toBe($this->academy->id);
        });
    });

    describe('getInteractiveCourses()', function () {
        it('returns empty collection when student profile is null', function () {
            $courses = $this->service->getInteractiveCourses(null, $this->academy);

            expect($courses)->toBeInstanceOf(Collection::class)
                ->and($courses)->toBeEmpty();
        });

        it('returns empty collection when student profile has no id', function () {
            $invalidProfile = new \stdClass();
            $courses = $this->service->getInteractiveCourses($invalidProfile, $this->academy);

            expect($courses)->toBeEmpty();
        });

        it('returns empty collection when no enrollments exist', function () {
            $courses = $this->service->getInteractiveCourses($this->studentProfile, $this->academy);

            expect($courses)->toBeInstanceOf(Collection::class)
                ->and($courses)->toBeEmpty();
        });

        it('returns enrolled interactive courses', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->studentProfile->id,
                'interactive_course_id' => $course->id,
                'enrollment_status' => 'enrolled',
            ]);

            $courses = $this->service->getInteractiveCourses($this->studentProfile, $this->academy);

            expect($courses)->toHaveCount(1)
                ->and($courses->first()->id)->toBe($course->id);
        });

        it('returns completed courses', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->studentProfile->id,
                'interactive_course_id' => $course->id,
                'enrollment_status' => 'completed',
            ]);

            $courses = $this->service->getInteractiveCourses($this->studentProfile, $this->academy);

            expect($courses)->toHaveCount(1);
        });

        it('excludes courses with dropped enrollments', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->studentProfile->id,
                'interactive_course_id' => $course->id,
                'enrollment_status' => 'dropped',
            ]);

            $courses = $this->service->getInteractiveCourses($this->studentProfile, $this->academy);

            expect($courses)->toBeEmpty();
        });

        it('excludes courses with pending enrollments', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->studentProfile->id,
                'interactive_course_id' => $course->id,
                'enrollment_status' => 'pending',
            ]);

            $courses = $this->service->getInteractiveCourses($this->studentProfile, $this->academy);

            expect($courses)->toBeEmpty();
        });

        it('eager loads assignedTeacher and enrollments relationships', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->studentProfile->id,
                'interactive_course_id' => $course->id,
                'enrollment_status' => 'enrolled',
            ]);

            $courses = $this->service->getInteractiveCourses($this->studentProfile, $this->academy);

            expect($courses->first()->relationLoaded('assignedTeacher'))->toBeTrue()
                ->and($courses->first()->relationLoaded('enrollments'))->toBeTrue();
        });

        it('only loads enrollments for current student', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->studentProfile->id,
                'interactive_course_id' => $course->id,
                'enrollment_status' => 'enrolled',
            ]);

            $otherStudent = User::factory()->student()->forAcademy($this->academy)->create();
            $otherStudentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $otherStudent->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $otherStudentProfile->id,
                'interactive_course_id' => $course->id,
                'enrollment_status' => 'enrolled',
            ]);

            $courses = $this->service->getInteractiveCourses($this->studentProfile, $this->academy);

            expect($courses->first()->enrollments)->toHaveCount(1)
                ->and($courses->first()->enrollments->first()->student_id)->toBe($this->studentProfile->id);
        });

        it('only returns courses from specified academy', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->studentProfile->id,
                'interactive_course_id' => $course->id,
                'enrollment_status' => 'enrolled',
            ]);

            $otherAcademy = Academy::factory()->create();
            $otherCourse = InteractiveCourse::factory()->create([
                'academy_id' => $otherAcademy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'academy_id' => $otherAcademy->id,
                'student_id' => $this->studentProfile->id,
                'interactive_course_id' => $otherCourse->id,
                'enrollment_status' => 'enrolled',
            ]);

            $courses = $this->service->getInteractiveCourses($this->studentProfile, $this->academy);

            expect($courses)->toHaveCount(1)
                ->and($courses->first()->academy_id)->toBe($this->academy->id);
        });
    });

    describe('getRecordedCourses()', function () {
        it('returns empty collection when no enrollments exist', function () {
            $courses = $this->service->getRecordedCourses($this->student, $this->academy);

            expect($courses)->toBeInstanceOf(Collection::class)
                ->and($courses)->toBeEmpty();
        });

        it('returns enrolled recorded courses', function () {
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $course->enrollments()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'enrollment_status' => 'enrolled',
                'enrolled_at' => now(),
            ]);

            $courses = $this->service->getRecordedCourses($this->student, $this->academy);

            expect($courses)->toHaveCount(1)
                ->and($courses->first()->id)->toBe($course->id);
        });

        it('eager loads enrollments, instructor, and chapters.lessons relationships', function () {
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $course->enrollments()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'enrollment_status' => 'enrolled',
                'enrolled_at' => now(),
            ]);

            $courses = $this->service->getRecordedCourses($this->student, $this->academy);

            expect($courses->first()->relationLoaded('enrollments'))->toBeTrue()
                ->and($courses->first()->relationLoaded('instructor'))->toBeTrue()
                ->and($courses->first()->relationLoaded('chapters'))->toBeTrue();
        });

        it('only loads enrollments for current student', function () {
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $course->enrollments()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'enrollment_status' => 'enrolled',
                'enrolled_at' => now(),
            ]);

            $otherStudent = User::factory()->student()->forAcademy($this->academy)->create();
            $course->enrollments()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $otherStudent->id,
                'enrollment_status' => 'enrolled',
                'enrolled_at' => now(),
            ]);

            $courses = $this->service->getRecordedCourses($this->student, $this->academy);

            expect($courses->first()->enrollments)->toHaveCount(1)
                ->and($courses->first()->enrollments->first()->student_id)->toBe($this->student->id);
        });

        it('only returns courses from specified academy', function () {
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $course->enrollments()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'enrollment_status' => 'enrolled',
                'enrolled_at' => now(),
            ]);

            $otherAcademy = Academy::factory()->create();
            $otherCourse = RecordedCourse::factory()->create([
                'academy_id' => $otherAcademy->id,
            ]);

            $otherCourse->enrollments()->create([
                'academy_id' => $otherAcademy->id,
                'student_id' => $this->student->id,
                'enrollment_status' => 'enrolled',
                'enrolled_at' => now(),
            ]);

            $courses = $this->service->getRecordedCourses($this->student, $this->academy);

            expect($courses)->toHaveCount(1)
                ->and($courses->first()->academy_id)->toBe($this->academy->id);
        });

        it('includes all enrollments regardless of status', function () {
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $course->enrollments()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'enrollment_status' => 'completed',
                'enrolled_at' => now(),
            ]);

            $courses = $this->service->getRecordedCourses($this->student, $this->academy);

            expect($courses)->toHaveCount(1);
        });
    });
});
