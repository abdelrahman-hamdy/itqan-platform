<?php

namespace App\Policies;

use App\Enums\SessionStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserType;
use App\Models\InteractiveCourseSession;
use App\Models\User;
use App\Services\AcademyContextService;

/**
 * Interactive Course Session Policy
 *
 * Authorization policy for interactive course session access and management.
 * Controls who can view, create, update, delete, and join sessions.
 */
class InteractiveCourseSessionPolicy
{
    /**
     * Determine whether the user can view any interactive course sessions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole([
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
            UserType::SUPERVISOR->value,
            UserType::ACADEMIC_TEACHER->value,
            UserType::STUDENT->value,
            UserType::PARENT->value,
        ]);
    }

    /**
     * Determine whether the user can view the interactive course session.
     */
    public function view(User $user, InteractiveCourseSession $session): bool
    {
        // Admins can view any session in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $session);
        }

        // Course teacher can view their sessions
        if ($user->hasRole(UserType::ACADEMIC_TEACHER->value)) {
            $course = $session->course;
            if ($course && $course->assigned_teacher_id === $user->academicTeacherProfile?->id) {
                return true;
            }
        }

        // Enrolled students can view sessions
        if ($user->hasRole(UserType::STUDENT->value)) {
            return $this->isEnrolledInCourse($user, $session);
        }

        // Parents can view their children's sessions
        if ($user->isParent()) {
            return $this->hasChildEnrolledInCourse($user, $session);
        }

        return false;
    }

    /**
     * Determine whether the user can create interactive course sessions.
     */
    public function create(User $user): bool
    {
        // Only admins and teachers can create sessions
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::ACADEMIC_TEACHER->value]);
    }

    /**
     * Determine whether the user can update the interactive course session.
     */
    public function update(User $user, InteractiveCourseSession $session): bool
    {
        // Admins can update any session in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return $this->sameAcademy($user, $session);
        }

        // Course teacher can update their sessions
        if ($user->hasRole(UserType::ACADEMIC_TEACHER->value)) {
            $course = $session->course;
            if ($course && $course->assigned_teacher_id === $user->academicTeacherProfile?->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the interactive course session.
     */
    public function delete(User $user, InteractiveCourseSession $session): bool
    {
        // Only admins can delete sessions
        if (! $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return false;
        }

        // Must be same academy
        if (! $this->sameAcademy($user, $session)) {
            return false;
        }

        // Cannot delete completed sessions
        return $session->status !== SessionStatus::COMPLETED;
    }

    /**
     * Determine whether the user can restore the session.
     */
    public function restore(User $user, InteractiveCourseSession $session): bool
    {
        // Only super admins can restore sessions
        return $user->hasRole(UserType::SUPER_ADMIN->value);
    }

    /**
     * Determine whether the user can permanently delete the session.
     */
    public function forceDelete(User $user, InteractiveCourseSession $session): bool
    {
        // Only super admins can permanently delete sessions
        return $user->hasRole(UserType::SUPER_ADMIN->value);
    }

    /**
     * Determine whether the user can join the meeting for this session.
     */
    public function join(User $user, InteractiveCourseSession $session): bool
    {
        // Course teacher can join their sessions
        if ($user->hasRole(UserType::ACADEMIC_TEACHER->value)) {
            $course = $session->course;
            if ($course && $course->assigned_teacher_id === $user->academicTeacherProfile?->id) {
                return true;
            }
        }

        // Enrolled students can join sessions
        if ($user->hasRole(UserType::STUDENT->value)) {
            return $this->isEnrolledInCourse($user, $session);
        }

        // Admins can join any session in their academy (for monitoring)
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $session);
        }

        return false;
    }

    /**
     * Determine whether the user can start the session.
     */
    public function start(User $user, InteractiveCourseSession $session): bool
    {
        // Only the course teacher can start the session
        if (! $user->hasRole(UserType::ACADEMIC_TEACHER->value)) {
            return false;
        }

        $course = $session->course;
        if (! $course || $course->assigned_teacher_id !== $user->academicTeacherProfile?->id) {
            return false;
        }

        // Session must be in correct status to start
        return $session->canStart();
    }

    /**
     * Determine whether the user can complete the session.
     */
    public function complete(User $user, InteractiveCourseSession $session): bool
    {
        // Only admins and course teacher can complete sessions
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return $this->sameAcademy($user, $session);
        }

        if ($user->hasRole(UserType::ACADEMIC_TEACHER->value)) {
            $course = $session->course;

            return $course && $course->assigned_teacher_id === $user->academicTeacherProfile?->id;
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the session.
     */
    public function cancel(User $user, InteractiveCourseSession $session): bool
    {
        // Only admins and course teacher can cancel sessions
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return $this->sameAcademy($user, $session);
        }

        if ($user->hasRole(UserType::ACADEMIC_TEACHER->value)) {
            $course = $session->course;
            if ($course && $course->assigned_teacher_id === $user->academicTeacherProfile?->id) {
                return $session->canCancel();
            }
        }

        return false;
    }

    /**
     * Check if student is enrolled in the course.
     */
    private function isEnrolledInCourse(User $user, InteractiveCourseSession $session): bool
    {
        $course = $session->course;
        if (! $course || ! $user->studentProfileUnscoped) {
            return false;
        }

        return $course->enrollments()
            ->where('student_id', $user->studentProfileUnscoped->id)
            ->where('enrollment_status', EnrollmentStatus::ENROLLED)
            ->exists();
    }

    /**
     * Check if user's child is enrolled in the course.
     */
    private function hasChildEnrolledInCourse(User $user, InteractiveCourseSession $session): bool
    {
        $parent = $user->parentProfile;
        if (! $parent) {
            return false;
        }

        $course = $session->course;
        if (! $course) {
            return false;
        }

        $childIds = $parent->students()->pluck('student_profiles.id')->toArray();

        return $course->enrollments()
            ->whereIn('student_id', $childIds)
            ->where('enrollment_status', EnrollmentStatus::ENROLLED)
            ->exists();
    }

    /**
     * Check if session belongs to same academy as user.
     */
    private function sameAcademy(User $user, InteractiveCourseSession $session): bool
    {
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            $userAcademyId = AcademyContextService::getCurrentAcademyId();
            if (! $userAcademyId) {
                return true; // Super admin with no context can access all
            }
            $sessionAcademyId = $session->course?->academy_id;

            return $sessionAcademyId === $userAcademyId;
        }

        $sessionAcademyId = $session->course?->academy_id;

        return $sessionAcademyId === $user->academy_id;
    }
}
