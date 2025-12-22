<?php

use App\Models\Academy;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\CourseReview;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Models\Student;
use App\Models\TeacherReview;
use App\Models\User;
use App\Services\ReviewService;

describe('ReviewService', function () {
    beforeEach(function () {
        $this->service = new ReviewService();
        $this->academy = Academy::factory()->create();
    });

    describe('canReviewQuranTeacher()', function () {
        it('returns false when student has already reviewed the teacher', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            TeacherReview::create([
                'academy_id' => $this->academy->id,
                'reviewable_type' => QuranTeacherProfile::class,
                'reviewable_id' => $teacher->id,
                'student_id' => $student->id,
                'rating' => 5,
                'is_approved' => true,
            ]);

            $result = $this->service->canReviewQuranTeacher($student, $teacher);

            expect($result['can_review'])->toBeFalse()
                ->and($result['reason'])->toBe('لقد قمت بتقييم هذا المعلم مسبقاً');
        });

        it('returns false when student has no active subscription with teacher', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $result = $this->service->canReviewQuranTeacher($student, $teacher);

            expect($result['can_review'])->toBeFalse()
                ->and($result['reason'])->toBe('يجب أن يكون لديك اشتراك نشط مع هذا المعلم');
        });

        it('returns true when student has active subscription and has not reviewed', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            QuranSubscription::factory()->create([
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->user_id,
                'status' => 'active',
            ]);

            $result = $this->service->canReviewQuranTeacher($student, $teacher);

            expect($result['can_review'])->toBeTrue()
                ->and($result['reason'])->toBeNull();
        });
    });

    describe('canReviewAcademicTeacher()', function () {
        it('returns false when student has already reviewed the teacher', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            TeacherReview::create([
                'academy_id' => $this->academy->id,
                'reviewable_type' => AcademicTeacherProfile::class,
                'reviewable_id' => $teacher->id,
                'student_id' => $student->id,
                'rating' => 5,
                'is_approved' => true,
            ]);

            $result = $this->service->canReviewAcademicTeacher($student, $teacher);

            expect($result['can_review'])->toBeFalse()
                ->and($result['reason'])->toBe('لقد قمت بتقييم هذا المعلم مسبقاً');
        });

        it('returns false when student has no active subscription with teacher', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $result = $this->service->canReviewAcademicTeacher($student, $teacher);

            expect($result['can_review'])->toBeFalse()
                ->and($result['reason'])->toBe('يجب أن يكون لديك اشتراك نشط مع هذا المعلم');
        });

        it('returns true when student has active subscription and has not reviewed', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            AcademicSubscription::factory()->create([
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'status' => 'active',
            ]);

            $result = $this->service->canReviewAcademicTeacher($student, $teacher);

            expect($result['can_review'])->toBeTrue()
                ->and($result['reason'])->toBeNull();
        });
    });

    describe('canReviewTeacher()', function () {
        it('delegates to canReviewQuranTeacher for QuranTeacherProfile', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            QuranSubscription::factory()->create([
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->user_id,
                'status' => 'active',
            ]);

            $result = $this->service->canReviewTeacher($student, $teacher);

            expect($result['can_review'])->toBeTrue();
        });

        it('delegates to canReviewAcademicTeacher for AcademicTeacherProfile', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            AcademicSubscription::factory()->create([
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'status' => 'active',
            ]);

            $result = $this->service->canReviewTeacher($student, $teacher);

            expect($result['can_review'])->toBeTrue();
        });

        it('returns false for invalid teacher type', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $invalidTeacher = new \stdClass();

            $result = $this->service->canReviewTeacher($student, $invalidTeacher);

            expect($result['can_review'])->toBeFalse()
                ->and($result['reason'])->toBe('نوع المعلم غير صالح');
        });
    });

    describe('canReviewRecordedCourse()', function () {
        it('returns false when student has already reviewed the course', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            CourseReview::create([
                'academy_id' => $this->academy->id,
                'reviewable_type' => RecordedCourse::class,
                'reviewable_id' => $course->id,
                'user_id' => $student->id,
                'rating' => 5,
                'is_approved' => true,
            ]);

            $result = $this->service->canReviewRecordedCourse($student, $course);

            expect($result['can_review'])->toBeFalse()
                ->and($result['reason'])->toBe('لقد قمت بتقييم هذه الدورة مسبقاً');
        });

        it('returns false when student has no active subscription to course', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $result = $this->service->canReviewRecordedCourse($student, $course);

            expect($result['can_review'])->toBeFalse()
                ->and($result['reason'])->toBe('يجب أن تكون مشتركاً في هذه الدورة');
        });

        it('returns true when student has active subscription and has not reviewed', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            CourseSubscription::factory()->create([
                'student_id' => $student->id,
                'recorded_course_id' => $course->id,
                'status' => 'active',
            ]);

            $result = $this->service->canReviewRecordedCourse($student, $course);

            expect($result['can_review'])->toBeTrue()
                ->and($result['reason'])->toBeNull();
        });
    });

    describe('canReviewInteractiveCourse()', function () {
        it('returns false when student has already reviewed the course', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = InteractiveCourse::factory()->create();

            CourseReview::create([
                'academy_id' => $this->academy->id,
                'reviewable_type' => InteractiveCourse::class,
                'reviewable_id' => $course->id,
                'user_id' => $student->id,
                'rating' => 5,
                'is_approved' => true,
            ]);

            $result = $this->service->canReviewInteractiveCourse($student, $course);

            expect($result['can_review'])->toBeFalse()
                ->and($result['reason'])->toBe('لقد قمت بتقييم هذه الدورة مسبقاً');
        });

        it('returns false when student has no subscription or enrollment', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = InteractiveCourse::factory()->create();

            $result = $this->service->canReviewInteractiveCourse($student, $course);

            expect($result['can_review'])->toBeFalse()
                ->and($result['reason'])->toBe('يجب أن تكون مسجلاً في هذه الدورة');
        });

        it('returns true when student has active CourseSubscription', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = InteractiveCourse::factory()->create();

            CourseSubscription::factory()->create([
                'student_id' => $student->id,
                'interactive_course_id' => $course->id,
                'status' => 'active',
            ]);

            $result = $this->service->canReviewInteractiveCourse($student, $course);

            expect($result['can_review'])->toBeTrue()
                ->and($result['reason'])->toBeNull();
        });

        it('returns true when student has active InteractiveCourseEnrollment', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = InteractiveCourse::factory()->create();
            $studentProfile = Student::factory()->create([
                'user_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'student_id' => $studentProfile->id,
                'course_id' => $course->id,
                'enrollment_status' => 'enrolled',
            ]);

            $result = $this->service->canReviewInteractiveCourse($student, $course);

            expect($result['can_review'])->toBeTrue()
                ->and($result['reason'])->toBeNull();
        });

        it('returns false when InteractiveCourseEnrollment status is not enrolled', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = InteractiveCourse::factory()->create();
            $studentProfile = Student::factory()->create([
                'user_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourseEnrollment::factory()->create([
                'student_id' => $studentProfile->id,
                'course_id' => $course->id,
                'enrollment_status' => 'pending',
            ]);

            $result = $this->service->canReviewInteractiveCourse($student, $course);

            expect($result['can_review'])->toBeFalse()
                ->and($result['reason'])->toBe('يجب أن تكون مسجلاً في هذه الدورة');
        });
    });

    describe('canReviewCourse()', function () {
        it('delegates to canReviewRecordedCourse for RecordedCourse', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            CourseSubscription::factory()->create([
                'student_id' => $student->id,
                'recorded_course_id' => $course->id,
                'status' => 'active',
            ]);

            $result = $this->service->canReviewCourse($student, $course);

            expect($result['can_review'])->toBeTrue();
        });

        it('delegates to canReviewInteractiveCourse for InteractiveCourse', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = InteractiveCourse::factory()->create();

            CourseSubscription::factory()->create([
                'student_id' => $student->id,
                'interactive_course_id' => $course->id,
                'status' => 'active',
            ]);

            $result = $this->service->canReviewCourse($student, $course);

            expect($result['can_review'])->toBeTrue();
        });

        it('returns false for invalid course type', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $invalidCourse = new \stdClass();

            $result = $this->service->canReviewCourse($student, $invalidCourse);

            expect($result['can_review'])->toBeFalse()
                ->and($result['reason'])->toBe('نوع الدورة غير صالح');
        });
    });

    describe('submitTeacherReview()', function () {
        it('creates a teacher review with valid data', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $review = $this->service->submitTeacherReview(
                $student,
                $teacher,
                5,
                'ممتاز'
            );

            expect($review)->toBeInstanceOf(TeacherReview::class)
                ->and($review->student_id)->toBe($student->id)
                ->and($review->reviewable_type)->toBe(QuranTeacherProfile::class)
                ->and($review->reviewable_id)->toBe($teacher->id)
                ->and($review->rating)->toBe(5)
                ->and($review->comment)->toBe('ممتاز')
                ->and($review->academy_id)->toBe($this->academy->id);
        });

        it('throws exception when rating is less than 1', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            expect(fn () => $this->service->submitTeacherReview($student, $teacher, 0))
                ->toThrow(\InvalidArgumentException::class, 'التقييم يجب أن يكون بين 1 و 5');
        });

        it('throws exception when rating is greater than 5', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            expect(fn () => $this->service->submitTeacherReview($student, $teacher, 6))
                ->toThrow(\InvalidArgumentException::class, 'التقييم يجب أن يكون بين 1 و 5');
        });

        it('auto-approves review when academy setting is true', function () {
            $this->academy->update([
                'academic_settings' => ['auto_approve_reviews' => true],
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $review = $this->service->submitTeacherReview($student, $teacher, 5);

            expect($review->is_approved)->toBeTrue()
                ->and($review->approved_at)->not->toBeNull();
        });

        it('does not auto-approve review when academy setting is false', function () {
            $this->academy->update([
                'academic_settings' => ['auto_approve_reviews' => false],
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $review = $this->service->submitTeacherReview($student, $teacher, 5);

            expect($review->is_approved)->toBeFalse()
                ->and($review->approved_at)->toBeNull();
        });

        it('creates review without comment when comment is null', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $review = $this->service->submitTeacherReview($student, $teacher, 4);

            expect($review->comment)->toBeNull()
                ->and($review->rating)->toBe(4);
        });
    });

    describe('submitCourseReview()', function () {
        it('creates a course review with valid data', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $review = $this->service->submitCourseReview(
                $student,
                $course,
                5,
                'دورة رائعة'
            );

            expect($review)->toBeInstanceOf(CourseReview::class)
                ->and($review->user_id)->toBe($student->id)
                ->and($review->reviewable_type)->toBe(RecordedCourse::class)
                ->and($review->reviewable_id)->toBe($course->id)
                ->and($review->rating)->toBe(5)
                ->and($review->review)->toBe('دورة رائعة')
                ->and($review->academy_id)->toBe($this->academy->id);
        });

        it('throws exception when rating is less than 1', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            expect(fn () => $this->service->submitCourseReview($student, $course, 0))
                ->toThrow(\InvalidArgumentException::class, 'التقييم يجب أن يكون بين 1 و 5');
        });

        it('throws exception when rating is greater than 5', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            expect(fn () => $this->service->submitCourseReview($student, $course, 6))
                ->toThrow(\InvalidArgumentException::class, 'التقييم يجب أن يكون بين 1 و 5');
        });

        it('auto-approves review when academy setting is true', function () {
            $this->academy->update([
                'academic_settings' => ['auto_approve_reviews' => true],
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $review = $this->service->submitCourseReview($student, $course, 5);

            expect($review->is_approved)->toBeTrue()
                ->and($review->approved_at)->not->toBeNull();
        });

        it('does not auto-approve review when academy setting is false', function () {
            $this->academy->update([
                'academic_settings' => ['auto_approve_reviews' => false],
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $review = $this->service->submitCourseReview($student, $course, 5);

            expect($review->is_approved)->toBeFalse()
                ->and($review->approved_at)->toBeNull();
        });

        it('creates review without comment when comment is null', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $review = $this->service->submitCourseReview($student, $course, 4);

            expect($review->review)->toBeNull()
                ->and($review->rating)->toBe(4);
        });
    });

    describe('shouldAutoApprove()', function () {
        it('returns true when academy id is null', function () {
            $result = $this->service->shouldAutoApprove(null);

            expect($result)->toBeTrue();
        });

        it('returns true when academy is not found', function () {
            $result = $this->service->shouldAutoApprove(99999);

            expect($result)->toBeTrue();
        });

        it('returns true when academy has auto_approve_reviews set to true', function () {
            $this->academy->update([
                'academic_settings' => ['auto_approve_reviews' => true],
            ]);

            $result = $this->service->shouldAutoApprove($this->academy->id);

            expect($result)->toBeTrue();
        });

        it('returns false when academy has auto_approve_reviews set to false', function () {
            $this->academy->update([
                'academic_settings' => ['auto_approve_reviews' => false],
            ]);

            $result = $this->service->shouldAutoApprove($this->academy->id);

            expect($result)->toBeFalse();
        });

        it('returns true when academy has no auto_approve_reviews setting', function () {
            $this->academy->update([
                'academic_settings' => [],
            ]);

            $result = $this->service->shouldAutoApprove($this->academy->id);

            expect($result)->toBeTrue();
        });

        it('returns true when academy has null academic_settings', function () {
            $this->academy->update([
                'academic_settings' => null,
            ]);

            $result = $this->service->shouldAutoApprove($this->academy->id);

            expect($result)->toBeTrue();
        });
    });

    describe('getTeacherReview()', function () {
        it('returns existing teacher review for student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $review = TeacherReview::create([
                'academy_id' => $this->academy->id,
                'reviewable_type' => QuranTeacherProfile::class,
                'reviewable_id' => $teacher->id,
                'student_id' => $student->id,
                'rating' => 5,
                'is_approved' => true,
            ]);

            $result = $this->service->getTeacherReview($student, $teacher);

            expect($result)->not->toBeNull()
                ->and($result->id)->toBe($review->id);
        });

        it('returns null when no review exists', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $result = $this->service->getTeacherReview($student, $teacher);

            expect($result)->toBeNull();
        });

        it('returns only review for specific teacher type', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $quranTeacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $academicTeacher = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            TeacherReview::create([
                'academy_id' => $this->academy->id,
                'reviewable_type' => QuranTeacherProfile::class,
                'reviewable_id' => $quranTeacher->id,
                'student_id' => $student->id,
                'rating' => 5,
                'is_approved' => true,
            ]);

            $result = $this->service->getTeacherReview($student, $academicTeacher);

            expect($result)->toBeNull();
        });
    });

    describe('getCourseReview()', function () {
        it('returns existing course review for student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $review = CourseReview::create([
                'academy_id' => $this->academy->id,
                'reviewable_type' => RecordedCourse::class,
                'reviewable_id' => $course->id,
                'user_id' => $student->id,
                'rating' => 5,
                'is_approved' => true,
            ]);

            $result = $this->service->getCourseReview($student, $course);

            expect($result)->not->toBeNull()
                ->and($result->id)->toBe($review->id);
        });

        it('returns null when no review exists', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $result = $this->service->getCourseReview($student, $course);

            expect($result)->toBeNull();
        });

        it('returns only review for specific course type', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $recordedCourse = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $interactiveCourse = InteractiveCourse::factory()->create();

            CourseReview::create([
                'academy_id' => $this->academy->id,
                'reviewable_type' => RecordedCourse::class,
                'reviewable_id' => $recordedCourse->id,
                'user_id' => $student->id,
                'rating' => 5,
                'is_approved' => true,
            ]);

            $result = $this->service->getCourseReview($student, $interactiveCourse);

            expect($result)->toBeNull();
        });

        it('returns review for interactive course', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = InteractiveCourse::factory()->create();

            $review = CourseReview::create([
                'academy_id' => $this->academy->id,
                'reviewable_type' => InteractiveCourse::class,
                'reviewable_id' => $course->id,
                'user_id' => $student->id,
                'rating' => 4,
                'is_approved' => true,
            ]);

            $result = $this->service->getCourseReview($student, $course);

            expect($result)->not->toBeNull()
                ->and($result->id)->toBe($review->id);
        });
    });
});
