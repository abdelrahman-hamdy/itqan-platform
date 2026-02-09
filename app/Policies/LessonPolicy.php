<?php

namespace App\Policies;

use App\Enums\EnrollmentStatus;
use App\Enums\UserType;
use App\Models\Lesson;
use App\Models\User;

/**
 * Lesson Policy
 *
 * Authorization policy for lesson access in recorded courses.
 */
class LessonPolicy
{
    /**
     * Determine whether the user can view any lessons.
     */
    public function viewAny(User $user): bool
    {
        return true; // Anyone can browse lessons, enrollment checked per-lesson
    }

    /**
     * Determine whether the user can view the lesson.
     */
    public function view(User $user, Lesson $lesson): bool
    {
        // Free preview lessons are accessible to everyone
        if ($lesson->is_free_preview) {
            return true;
        }

        // Admins can view any lesson in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $lesson);
        }

        // Teachers who created the course can view lessons
        if ($user->hasRole(['teacher', UserType::ACADEMIC_TEACHER->value])) {
            $course = $lesson->recordedCourse;
            if ($course && $course->created_by === $user->id) {
                return true;
            }
        }

        // Students must be enrolled in the course
        if ($user->hasRole(UserType::STUDENT->value)) {
            return $this->isEnrolledInCourse($user, $lesson);
        }

        // Parents can view if their children are enrolled
        if ($user->isParent()) {
            return $this->isParentOfEnrolledStudent($user, $lesson);
        }

        return false;
    }

    /**
     * Determine whether the user can create lessons.
     */
    public function create(User $user): bool
    {
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, 'teacher', UserType::ACADEMIC_TEACHER->value]);
    }

    /**
     * Determine whether the user can update the lesson.
     */
    public function update(User $user, Lesson $lesson): bool
    {
        // Only admins and course creators can update lessons
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return $this->sameAcademy($user, $lesson);
        }

        // Teachers can update lessons in their courses
        if ($user->hasRole(['teacher', UserType::ACADEMIC_TEACHER->value])) {
            $course = $lesson->recordedCourse;

            return $course && $course->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the lesson.
     */
    public function delete(User $user, Lesson $lesson): bool
    {
        // Only admins can delete lessons
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value]) && $this->sameAcademy($user, $lesson);
    }

    /**
     * Determine whether the user can download lesson materials.
     */
    public function downloadMaterials(User $user, Lesson $lesson): bool
    {
        // Must be downloadable and user must have view access
        if (! $lesson->is_downloadable) {
            return false;
        }

        return $this->view($user, $lesson);
    }

    /**
     * Check if user is enrolled in the course containing this lesson.
     */
    private function isEnrolledInCourse(User $user, Lesson $lesson): bool
    {
        $course = $lesson->recordedCourse;
        if (! $course) {
            return false;
        }

        $enrollment = \App\Models\CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('status', EnrollmentStatus::ENROLLED->value)
            ->first();

        return (bool) $enrollment;
    }

    /**
     * Check if user is a parent of an enrolled student.
     */
    private function isParentOfEnrolledStudent(User $user, Lesson $lesson): bool
    {
        $parent = $user->parentProfile;
        if (! $parent) {
            return false;
        }

        $course = $lesson->recordedCourse;
        if (! $course) {
            return false;
        }

        // Get all student user IDs for this parent
        $studentUserIds = $parent->students()->with('user')->get()->pluck('user.id')->filter()->toArray();

        // Check if any of parent's children are enrolled
        return \App\Models\CourseSubscription::where('recorded_course_id', $course->id)
            ->whereIn('student_id', $studentUserIds)
            ->where('status', EnrollmentStatus::ENROLLED->value)
            ->exists();
    }

    /**
     * Check if lesson belongs to same academy as user.
     */
    private function sameAcademy(User $user, Lesson $lesson): bool
    {
        $course = $lesson->recordedCourse;
        if (! $course) {
            return false;
        }

        // For super_admin, use the selected academy context
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            $userAcademyId = \App\Services\AcademyContextService::getCurrentAcademyId();
            // If super admin is in global view, allow access
            if (! $userAcademyId) {
                return true;
            }

            return $course->academy_id === $userAcademyId;
        }

        // For other users, use their assigned academy
        return $course->academy_id === $user->academy_id;
    }
}
