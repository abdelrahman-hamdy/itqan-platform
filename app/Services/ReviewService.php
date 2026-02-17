<?php

namespace App\Services;

use InvalidArgumentException;
use App\Enums\EnrollmentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\CourseReview;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Models\TeacherReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReviewService
{
    /**
     * Check if a student can review a Quran teacher
     */
    public function canReviewQuranTeacher(User $student, QuranTeacherProfile $teacher): array
    {
        // Check if already reviewed
        if ($teacher->hasReviewFrom($student->id)) {
            return [
                'can_review' => false,
                'reason' => 'لقد قمت بتقييم هذا المعلم مسبقاً',
            ];
        }

        // Check if student has subscription with this teacher
        $hasSubscription = QuranSubscription::where('student_id', $student->id)
            ->where('quran_teacher_id', $teacher->user_id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->exists();

        if (! $hasSubscription) {
            return [
                'can_review' => false,
                'reason' => 'يجب أن يكون لديك اشتراك نشط مع هذا المعلم',
            ];
        }

        return [
            'can_review' => true,
            'reason' => null,
        ];
    }

    /**
     * Check if a student can review an Academic teacher
     */
    public function canReviewAcademicTeacher(User $student, AcademicTeacherProfile $teacher): array
    {
        // Check if already reviewed
        if ($teacher->hasReviewFrom($student->id)) {
            return [
                'can_review' => false,
                'reason' => 'لقد قمت بتقييم هذا المعلم مسبقاً',
            ];
        }

        // Check if student has subscription with this teacher
        $hasSubscription = AcademicSubscription::where('student_id', $student->id)
            ->where('teacher_id', $teacher->id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->exists();

        if (! $hasSubscription) {
            return [
                'can_review' => false,
                'reason' => 'يجب أن يكون لديك اشتراك نشط مع هذا المعلم',
            ];
        }

        return [
            'can_review' => true,
            'reason' => null,
        ];
    }

    /**
     * Check if a student can review a teacher (generic)
     */
    public function canReviewTeacher(User $student, Model $teacher): array
    {
        if ($teacher instanceof QuranTeacherProfile) {
            return $this->canReviewQuranTeacher($student, $teacher);
        }

        if ($teacher instanceof AcademicTeacherProfile) {
            return $this->canReviewAcademicTeacher($student, $teacher);
        }

        return [
            'can_review' => false,
            'reason' => 'نوع المعلم غير صالح',
        ];
    }

    /**
     * Check if a student can review a recorded course
     */
    public function canReviewRecordedCourse(User $student, RecordedCourse $course): array
    {
        // Check if already reviewed
        if ($course->hasReviewFrom($student->id)) {
            return [
                'can_review' => false,
                'reason' => 'لقد قمت بتقييم هذه الدورة مسبقاً',
            ];
        }

        // Check if student has subscription to this course
        $hasSubscription = CourseSubscription::where('student_id', $student->id)
            ->where('recorded_course_id', $course->id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->exists();

        if (! $hasSubscription) {
            return [
                'can_review' => false,
                'reason' => 'يجب أن تكون مشتركاً في هذه الدورة',
            ];
        }

        return [
            'can_review' => true,
            'reason' => null,
        ];
    }

    /**
     * Check if a student can review an interactive course
     */
    public function canReviewInteractiveCourse(User $student, InteractiveCourse $course): array
    {
        // Check if already reviewed
        if ($course->hasReviewFrom($student->id)) {
            return [
                'can_review' => false,
                'reason' => 'لقد قمت بتقييم هذه الدورة مسبقاً',
            ];
        }

        // Check CourseSubscription first
        $hasSubscription = CourseSubscription::where('student_id', $student->id)
            ->where('interactive_course_id', $course->id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->exists();

        // Also check InteractiveCourseEnrollment
        if (! $hasSubscription && $student->studentProfile) {
            $hasSubscription = InteractiveCourseEnrollment::where('student_id', $student->studentProfile->id)
                ->where('course_id', $course->id)
                ->where('enrollment_status', EnrollmentStatus::ENROLLED)
                ->exists();
        }

        if (! $hasSubscription) {
            return [
                'can_review' => false,
                'reason' => 'يجب أن تكون مسجلاً في هذه الدورة',
            ];
        }

        return [
            'can_review' => true,
            'reason' => null,
        ];
    }

    /**
     * Check if a student can review a course (generic)
     */
    public function canReviewCourse(User $student, Model $course): array
    {
        if ($course instanceof RecordedCourse) {
            return $this->canReviewRecordedCourse($student, $course);
        }

        if ($course instanceof InteractiveCourse) {
            return $this->canReviewInteractiveCourse($student, $course);
        }

        return [
            'can_review' => false,
            'reason' => 'نوع الدورة غير صالح',
        ];
    }

    /**
     * Submit a teacher review
     */
    public function submitTeacherReview(
        User $student,
        Model $teacher,
        int $rating,
        ?string $comment = null
    ): TeacherReview {
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('التقييم يجب أن يكون بين 1 و 5');
        }

        // Get academy ID
        $academyId = $teacher->academy_id;

        // Check auto-approve setting
        $isApproved = $this->shouldAutoApprove($academyId);

        return TeacherReview::create([
            'academy_id' => $academyId,
            'reviewable_type' => get_class($teacher),
            'reviewable_id' => $teacher->id,
            'student_id' => $student->id,
            'rating' => $rating,
            'comment' => $comment,
            'is_approved' => $isApproved,
            'approved_at' => $isApproved ? now() : null,
        ]);
    }

    /**
     * Submit a course review
     */
    public function submitCourseReview(
        User $student,
        Model $course,
        int $rating,
        ?string $comment = null
    ): CourseReview {
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('التقييم يجب أن يكون بين 1 و 5');
        }

        // Get academy ID
        $academyId = $course->academy_id;

        // Check auto-approve setting
        $isApproved = $this->shouldAutoApprove($academyId);

        return CourseReview::create([
            'academy_id' => $academyId,
            'reviewable_type' => get_class($course),
            'reviewable_id' => $course->id,
            'user_id' => $student->id,
            'rating' => $rating,
            'review' => $comment,
            'is_approved' => $isApproved,
            'approved_at' => $isApproved ? now() : null,
        ]);
    }

    /**
     * Check if reviews should be auto-approved for this academy
     */
    public function shouldAutoApprove(?int $academyId): bool
    {
        if (! $academyId) {
            return true;
        }

        $academy = Academy::find($academyId);
        if (! $academy) {
            return true;
        }

        $settings = $academy->academic_settings ?? [];

        return $settings['auto_approve_reviews'] ?? true;
    }

    /**
     * Get existing review for a teacher
     */
    public function getTeacherReview(User $student, Model $teacher): ?TeacherReview
    {
        return TeacherReview::where('student_id', $student->id)
            ->where('reviewable_type', get_class($teacher))
            ->where('reviewable_id', $teacher->id)
            ->first();
    }

    /**
     * Get existing review for a course
     */
    public function getCourseReview(User $student, Model $course): ?CourseReview
    {
        return CourseReview::where('user_id', $student->id)
            ->where('reviewable_type', get_class($course))
            ->where('reviewable_id', $course->id)
            ->first();
    }
}
