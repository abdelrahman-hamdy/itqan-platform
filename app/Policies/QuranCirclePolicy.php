<?php

namespace App\Policies;

use App\Models\QuranCircle;
use App\Models\User;

/**
 * Quran Circle Policy
 *
 * Authorization policy for Quran Circle management.
 * Controls access to circle creation, viewing, enrollment, and administration.
 */
class QuranCirclePolicy
{
    /**
     * Determine whether the user can view any circles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'supervisor', 'teacher', 'quran_teacher']);
    }

    /**
     * Determine whether the user can view the circle.
     */
    public function view(User $user, QuranCircle $circle): bool
    {
        // Admins and supervisors can view any circle in their academy
        if ($user->hasRole(['super_admin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $circle);
        }

        // Teachers can view circles they teach
        if ($user->hasRole(['teacher', 'quran_teacher'])) {
            return $this->isCircleTeacher($user, $circle);
        }

        // Students can view circles they're enrolled in
        if ($user->hasRole('student')) {
            return $this->isEnrolledStudent($user, $circle);
        }

        // Parents can view their children's circles
        if ($user->isParent()) {
            return $this->isParentOfEnrolledStudent($user, $circle);
        }

        return false;
    }

    /**
     * Determine whether the user can create circles.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'supervisor']);
    }

    /**
     * Determine whether the user can update the circle.
     */
    public function update(User $user, QuranCircle $circle): bool
    {
        // Admins and supervisors can update any circle in their academy
        if ($user->hasRole(['super_admin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $circle);
        }

        // Teachers can update their own circles
        if ($user->hasRole(['teacher', 'quran_teacher'])) {
            return $this->isCircleTeacher($user, $circle);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the circle.
     */
    public function delete(User $user, QuranCircle $circle): bool
    {
        // Only admins can delete circles
        if ($user->hasRole(['super_admin', 'admin'])) {
            return $this->sameAcademy($user, $circle);
        }

        return false;
    }

    /**
     * Determine whether the user can publish the circle.
     */
    public function publish(User $user, QuranCircle $circle): bool
    {
        return $this->update($user, $circle);
    }

    /**
     * Determine whether the user can start the circle.
     */
    public function start(User $user, QuranCircle $circle): bool
    {
        return $this->update($user, $circle);
    }

    /**
     * Determine whether the user can complete the circle.
     */
    public function complete(User $user, QuranCircle $circle): bool
    {
        return $this->update($user, $circle);
    }

    /**
     * Determine whether the user can cancel the circle.
     */
    public function cancel(User $user, QuranCircle $circle): bool
    {
        return $this->update($user, $circle);
    }

    /**
     * Determine whether the user can enroll students in the circle.
     */
    public function enroll(User $user, QuranCircle $circle): bool
    {
        // Admins and supervisors can enroll students
        if ($user->hasRole(['super_admin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $circle);
        }

        // Teachers can enroll students in their circles
        if ($user->hasRole(['teacher', 'quran_teacher'])) {
            return $this->isCircleTeacher($user, $circle);
        }

        return false;
    }

    /**
     * Determine whether the user can enroll a specific student.
     */
    public function enrollStudent(User $user, QuranCircle $circle, User $student): bool
    {
        // First check if user can enroll in this circle
        if (!$this->enroll($user, $circle)) {
            return false;
        }

        // Student must belong to the same academy
        $studentProfile = $student->studentProfile;
        if (!$studentProfile) {
            return false;
        }

        return $studentProfile->academy_id === $circle->academy_id;
    }

    /**
     * Determine whether the user can view available circles.
     */
    public function viewAvailable(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'supervisor', 'teacher', 'quran_teacher', 'student', 'parent']);
    }

    /**
     * Check if user is the teacher of this circle.
     */
    private function isCircleTeacher(User $user, QuranCircle $circle): bool
    {
        $teacherProfile = $user->quranTeacherProfile;
        if (!$teacherProfile) {
            return false;
        }

        return $circle->quran_teacher_id === $teacherProfile->id;
    }

    /**
     * Check if user is enrolled in this circle.
     */
    private function isEnrolledStudent(User $user, QuranCircle $circle): bool
    {
        return $circle->enrollments()
            ->where('student_id', $user->id)
            ->exists();
    }

    /**
     * Check if user is parent of an enrolled student.
     */
    private function isParentOfEnrolledStudent(User $user, QuranCircle $circle): bool
    {
        $parent = $user->parentProfile;
        if (!$parent) {
            return false;
        }

        $childIds = $parent->students()->pluck('users.id')->toArray();

        return $circle->enrollments()
            ->whereIn('student_id', $childIds)
            ->exists();
    }

    /**
     * Check if circle belongs to same academy as user.
     */
    private function sameAcademy(User $user, QuranCircle $circle): bool
    {
        // For super_admin, use the selected academy context
        if ($user->hasRole('super_admin')) {
            $userAcademyId = \App\Services\AcademyContextService::getCurrentAcademyId();
            // If super admin is in global view (no specific academy selected), allow access
            if (!$userAcademyId) {
                return true;
            }
            return $circle->academy_id === $userAcademyId;
        }

        // For other users, use their assigned academy
        return $circle->academy_id === $user->academy_id;
    }
}
