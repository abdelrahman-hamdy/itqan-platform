<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\QuranIndividualCircle;
use App\Models\User;

/**
 * QuranIndividualCircle Policy
 *
 * Authorization policy for Quran individual circle access.
 */
class QuranIndividualCirclePolicy
{
    /**
     * Determine whether the user can view the individual circle.
     */
    public function view(User $user, QuranIndividualCircle $circle): bool
    {
        // Admins and supervisors can view any circle in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $circle);
        }

        // Teacher can view their own circles
        if ($user->user_type === UserType::QURAN_TEACHER->value && (int) $circle->quran_teacher_id === (int) $user->id) {
            return true;
        }

        // Student can view their own circles
        if ($user->user_type === UserType::STUDENT->value && (int) $circle->student_id === (int) $user->id) {
            return true;
        }

        // Parents can view their children's circles
        if ($user->isParent()) {
            return $this->isParentOfCircleStudent($user, $circle);
        }

        return false;
    }

    /**
     * Determine whether the user can create individual circles.
     */
    public function create(User $user): bool
    {
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value]);
    }

    /**
     * Determine whether the user can update the individual circle.
     */
    public function update(User $user, QuranIndividualCircle $circle): bool
    {
        // Admins can update circles in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $circle);
        }

        // Teacher can update their own circles
        if ($user->user_type === UserType::QURAN_TEACHER->value && (int) $circle->quran_teacher_id === (int) $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the individual circle.
     */
    public function delete(User $user, QuranIndividualCircle $circle): bool
    {
        // Only admins can delete circles
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return $this->sameAcademy($user, $circle);
        }

        return false;
    }

    /**
     * Check if user is a parent of the circle's student.
     */
    private function isParentOfCircleStudent(User $user, QuranIndividualCircle $circle): bool
    {
        $parent = $user->parentProfile;
        if (! $parent) {
            return false;
        }

        $studentUserIds = $parent->students()->with('user')->get()->pluck('user.id')->filter()->toArray();

        return in_array($circle->student_id, $studentUserIds);
    }

    /**
     * Check if circle belongs to same academy as user.
     */
    private function sameAcademy(User $user, QuranIndividualCircle $circle): bool
    {
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            $userAcademyId = \App\Services\AcademyContextService::getCurrentAcademyId();
            if (! $userAcademyId) {
                return true;
            }

            return $circle->academy_id === $userAcademyId;
        }

        return $circle->academy_id === $user->academy_id;
    }
}
