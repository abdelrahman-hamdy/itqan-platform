<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\InteractiveCourseHomework;
use App\Models\User;
use App\Services\AcademyContextService;

class InteractiveCourseHomeworkPolicy
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
            UserType::ACADEMIC_TEACHER->value,
            UserType::QURAN_TEACHER->value,
            UserType::STUDENT->value,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InteractiveCourseHomework $homework): bool
    {
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $homework);
        }

        if ($user->hasRole([UserType::ACADEMIC_TEACHER->value, UserType::QURAN_TEACHER->value])) {
            return $homework->teacher_id === $user->id;
        }

        if ($user->hasRole(UserType::STUDENT->value)) {
            return $this->isEnrolledInCourse($user, $homework);
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
            UserType::ACADEMIC_TEACHER->value,
            UserType::QURAN_TEACHER->value,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InteractiveCourseHomework $homework): bool
    {
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $homework);
        }

        if ($user->hasRole([UserType::ACADEMIC_TEACHER->value, UserType::QURAN_TEACHER->value])) {
            return $homework->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InteractiveCourseHomework $homework): bool
    {
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return $this->sameAcademy($user, $homework);
        }

        return false;
    }

    /**
     * Check if student is enrolled in the course.
     */
    private function isEnrolledInCourse(User $user, InteractiveCourseHomework $homework): bool
    {
        $course = $homework->session?->course;
        if (! $course) {
            return false;
        }

        return $course->enrollments()
            ->where('student_id', $user->studentProfileUnscoped?->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Check if homework belongs to same academy as user.
     */
    private function sameAcademy(User $user, InteractiveCourseHomework $homework): bool
    {
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            $userAcademyId = AcademyContextService::getCurrentAcademyId();
            if (! $userAcademyId) {
                return true;
            }

            return $homework->academy_id === $userAcademyId;
        }

        return $homework->academy_id === $user->academy_id;
    }
}
