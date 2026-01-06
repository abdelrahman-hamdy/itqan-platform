<?php

namespace App\Policies;

use App\Models\QuranIndividualCircle;
use App\Models\User;

/**
 * Quran Individual Circle Policy
 *
 * Authorization policy for individual Quran circles (1-to-1 teaching).
 */
class QuranIndividualCirclePolicy
{
    /**
     * Determine whether the user can view any circles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'supervisor', 'teacher', 'quran_teacher', 'student', 'parent']);
    }

    /**
     * Determine whether the user can view the circle.
     */
    public function view(User $user, QuranIndividualCircle $circle): bool
    {
        // Admins and supervisors can view any circle in their academy
        if ($user->hasRole(['super_admin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $circle);
        }

        // Teachers can view circles they teach
        if ($user->hasRole(['teacher', 'quran_teacher'])) {
            return $this->isCircleTeacher($user, $circle);
        }

        // Students can view their own circle
        if ($user->hasRole('student')) {
            return $circle->student_id === $user->id;
        }

        // Parents can view their children's circles
        if ($user->isParent()) {
            return $this->isParentOfStudent($user, $circle);
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
    public function update(User $user, QuranIndividualCircle $circle): bool
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
    public function delete(User $user, QuranIndividualCircle $circle): bool
    {
        // Only admins can delete circles
        return $user->hasRole(['super_admin', 'admin']) && $this->sameAcademy($user, $circle);
    }

    /**
     * Determine whether the user can view the circle's report.
     */
    public function viewReport(User $user, QuranIndividualCircle $circle): bool
    {
        return $this->view($user, $circle);
    }

    /**
     * Check if user is the teacher of this circle.
     */
    private function isCircleTeacher(User $user, QuranIndividualCircle $circle): bool
    {
        $teacherProfile = $user->quranTeacherProfile;
        if (! $teacherProfile) {
            return false;
        }

        return $circle->quran_teacher_id === $teacherProfile->id;
    }

    /**
     * Check if user is parent of the student in this circle.
     */
    private function isParentOfStudent(User $user, QuranIndividualCircle $circle): bool
    {
        $parent = $user->parentProfile;
        if (! $parent) {
            return false;
        }

        // Get student user IDs through the parent-student relationship
        $studentUserIds = $parent->students()->with('user')->get()->pluck('user.id')->filter()->toArray();

        // circle.student_id references User.id
        return in_array($circle->student_id, $studentUserIds);
    }

    /**
     * Check if circle belongs to same academy as user.
     */
    private function sameAcademy(User $user, QuranIndividualCircle $circle): bool
    {
        // For super_admin, use the selected academy context
        if ($user->hasRole('super_admin')) {
            $userAcademyId = \App\Services\AcademyContextService::getCurrentAcademyId();
            // If super admin is in global view, allow access
            if (! $userAcademyId) {
                return true;
            }

            return $circle->academy_id === $userAcademyId;
        }

        // For other users, use their assigned academy
        return $circle->academy_id === $user->academy_id;
    }
}
