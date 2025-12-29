<?php

namespace App\Policies;

use App\Models\InteractiveCourse;
use App\Models\User;
use App\Services\AcademyContextService;

/**
 * Interactive Course Policy
 *
 * Authorization policy for interactive course access and management.
 * Controls who can view, create, update, and delete interactive courses.
 */
class InteractiveCoursePolicy
{
    /**
     * Determine whether the user can view any interactive courses.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view courses
        return $user->hasRole([
            'super_admin',
            'admin',
            'supervisor',
            'academic_teacher',
            'student',
            'parent'
        ]);
    }

    /**
     * Determine whether the user can view the interactive course.
     */
    public function view(User $user, InteractiveCourse $course): bool
    {
        // Admins can view any course in their academy
        if ($user->hasRole(['super_admin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $course);
        }

        // Assigned teacher can view their course
        if ($user->hasRole('academic_teacher') && $course->assigned_teacher_id === $user->academicTeacherProfile?->id) {
            return true;
        }

        // Students can view courses they're enrolled in or published courses
        if ($user->hasRole('student')) {
            // Enrolled students can always view
            if ($this->isEnrolledInCourse($user, $course)) {
                return true;
            }

            // Anyone can view published courses for browsing
            return $course->is_published;
        }

        // Parents can view courses their children are enrolled in or published courses
        if ($user->isParent()) {
            if ($this->hasChildEnrolledInCourse($user, $course)) {
                return true;
            }

            // Parents can browse published courses
            return $course->is_published;
        }

        return false;
    }

    /**
     * Determine whether the user can create interactive courses.
     */
    public function create(User $user): bool
    {
        // Only admins can create courses
        return $user->hasRole(['super_admin', 'admin']);
    }

    /**
     * Determine whether the user can update the interactive course.
     */
    public function update(User $user, InteractiveCourse $course): bool
    {
        // Admins can update any course in their academy
        if ($user->hasRole(['super_admin', 'admin'])) {
            return $this->sameAcademy($user, $course);
        }

        // Assigned teacher can update their course details (not pricing/settings)
        if ($user->hasRole('academic_teacher') && $course->assigned_teacher_id === $user->academicTeacherProfile?->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the interactive course.
     */
    public function delete(User $user, InteractiveCourse $course): bool
    {
        // Only admins can delete courses
        if (!$user->hasRole(['super_admin', 'admin'])) {
            return false;
        }

        // Must be same academy
        if (!$this->sameAcademy($user, $course)) {
            return false;
        }

        // Cannot delete if course has active enrollments
        return $course->getCurrentEnrollmentCount() === 0;
    }

    /**
     * Determine whether the user can restore the interactive course.
     */
    public function restore(User $user, InteractiveCourse $course): bool
    {
        // Only super admins can restore courses
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can permanently delete the interactive course.
     */
    public function forceDelete(User $user, InteractiveCourse $course): bool
    {
        // Only super admins can permanently delete courses
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can enroll in the course.
     */
    public function enroll(User $user, InteractiveCourse $course): bool
    {
        // Only students can enroll
        if (!$user->hasRole('student')) {
            return false;
        }

        // Course must be published and enrollment must be open
        if (!$course->isEnrollmentOpen()) {
            return false;
        }

        // Student must not already be enrolled
        if ($this->isEnrolledInCourse($user, $course)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can manage course enrollments.
     */
    public function manageEnrollments(User $user, InteractiveCourse $course): bool
    {
        // Admins can manage enrollments
        if ($user->hasRole(['super_admin', 'admin'])) {
            return $this->sameAcademy($user, $course);
        }

        // Assigned teacher can view (but not modify) enrollments
        if ($user->hasRole('academic_teacher') && $course->assigned_teacher_id === $user->academicTeacherProfile?->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if student is enrolled in the course.
     */
    private function isEnrolledInCourse(User $user, InteractiveCourse $course): bool
    {
        if (!$user->studentProfileUnscoped) {
            return false;
        }

        return $course->enrollments()
            ->where('student_id', $user->studentProfileUnscoped->id)
            ->where('enrollment_status', 'enrolled')
            ->exists();
    }

    /**
     * Check if user's child is enrolled in the course.
     */
    private function hasChildEnrolledInCourse(User $user, InteractiveCourse $course): bool
    {
        $parent = $user->parentProfile;
        if (!$parent) {
            return false;
        }

        $childIds = $parent->students()->pluck('student_profiles.id')->toArray();

        return $course->enrollments()
            ->whereIn('student_id', $childIds)
            ->where('enrollment_status', 'enrolled')
            ->exists();
    }

    /**
     * Check if course belongs to same academy as user.
     */
    private function sameAcademy(User $user, InteractiveCourse $course): bool
    {
        if ($user->hasRole('super_admin')) {
            $userAcademyId = AcademyContextService::getCurrentAcademyId();
            if (!$userAcademyId) {
                return true; // Super admin with no context can access all
            }
            return $course->academy_id === $userAcademyId;
        }

        return $course->academy_id === $user->academy_id;
    }
}
