<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\TeacherEarning;
use App\Models\User;
use App\Services\AcademyContextService;

class TeacherEarningPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole([
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
            UserType::SUPERVISOR->value,
            UserType::QURAN_TEACHER->value,
            UserType::ACADEMIC_TEACHER->value,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TeacherEarning $earning): bool
    {
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $earning);
        }

        if ($user->hasRole([UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            return $this->isOwnEarning($user, $earning);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole([
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
            UserType::SUPERVISOR->value,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TeacherEarning $earning): bool
    {
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])
            && $this->sameAcademy($user, $earning);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TeacherEarning $earning): bool
    {
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])
            && $this->sameAcademy($user, $earning);
    }

    /**
     * Check if the earning belongs to the teacher.
     */
    private function isOwnEarning(User $user, TeacherEarning $earning): bool
    {
        $teacherProfile = $user->quranTeacherProfile ?? $user->academicTeacherProfile;
        if (! $teacherProfile) {
            return false;
        }

        $expectedType = $teacherProfile instanceof \App\Models\QuranTeacherProfile
            ? 'quran_teacher'
            : 'academic_teacher';

        return $earning->teacher_type === $expectedType && $earning->teacher_id === $teacherProfile->id;
    }

    /**
     * Check if earning belongs to same academy as user.
     */
    private function sameAcademy(User $user, TeacherEarning $earning): bool
    {
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            $userAcademyId = AcademyContextService::getCurrentAcademyId();
            if (! $userAcademyId) {
                return true;
            }

            return $earning->academy_id === $userAcademyId;
        }

        return $earning->academy_id === $user->academy_id;
    }
}
