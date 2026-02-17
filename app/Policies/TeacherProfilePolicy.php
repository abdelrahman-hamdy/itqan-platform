<?php

namespace App\Policies;

use App\Services\AcademyContextService;
use App\Enums\UserType;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\User;

/**
 * Teacher Profile Policy
 *
 * Authorization policy for teacher profile access.
 * Handles both Quran and Academic teacher profiles.
 */
class TeacherProfilePolicy
{
    /**
     * Determine whether the user can view any teacher profiles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value, UserType::STUDENT->value]);
    }

    /**
     * Determine whether the user can view the teacher profile.
     */
    public function view(User $user, $profile): bool
    {
        // Admins can view any profile in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $profile);
        }

        // Teachers can view their own profile
        if ($this->isOwnProfile($user, $profile)) {
            return true;
        }

        // Students can view profiles of their teachers
        if ($user->hasRole(UserType::STUDENT->value)) {
            return $this->isStudentOfTeacher($user, $profile);
        }

        // Parents can view profiles of their children's teachers
        if ($user->isParent()) {
            return $this->isParentOfStudentOfTeacher($user, $profile);
        }

        return false;
    }

    /**
     * Determine whether the user can create teacher profiles.
     */
    public function create(User $user): bool
    {
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value]);
    }

    /**
     * Determine whether the user can update the teacher profile.
     */
    public function update(User $user, $profile): bool
    {
        // Admins can update any profile in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return $this->sameAcademy($user, $profile);
        }

        // Teachers can update their own profile
        return $this->isOwnProfile($user, $profile);
    }

    /**
     * Determine whether the user can delete the teacher profile.
     */
    public function delete(User $user, $profile): bool
    {
        // Only super_admin can delete teacher profiles
        return $user->hasRole(UserType::SUPER_ADMIN->value);
    }

    /**
     * Determine whether the user can view teacher's earnings.
     */
    public function viewEarnings(User $user, $profile): bool
    {
        // Only the teacher themselves or admin can view earnings
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return $this->sameAcademy($user, $profile);
        }

        return $this->isOwnProfile($user, $profile);
    }

    /**
     * Determine whether the user can view teacher's schedule.
     */
    public function viewSchedule(User $user, $profile): bool
    {
        return $this->view($user, $profile);
    }

    /**
     * Check if this is the user's own profile.
     */
    private function isOwnProfile(User $user, $profile): bool
    {
        if ($profile instanceof QuranTeacherProfile) {
            return $profile->user_id === $user->id;
        }

        if ($profile instanceof AcademicTeacherProfile) {
            return $profile->user_id === $user->id;
        }

        return false;
    }

    /**
     * Check if user is a student of this teacher.
     */
    private function isStudentOfTeacher(User $user, $profile): bool
    {
        // Subscriptions use student_id (User ID) and quran_teacher_id/academic_teacher_id (User ID)
        if ($profile instanceof QuranTeacherProfile) {
            // Check if student has subscriptions with this teacher via their user_id
            return QuranSubscription::where('student_id', $user->id)
                ->where('quran_teacher_id', $profile->user_id)
                ->exists();
        }

        if ($profile instanceof AcademicTeacherProfile) {
            // Check if student has subscriptions with this teacher via profile id
            // AcademicSubscription uses teacher_id which references AcademicTeacherProfile.id
            return AcademicSubscription::where('student_id', $user->id)
                ->where('teacher_id', $profile->id)
                ->exists();
        }

        return false;
    }

    /**
     * Check if user is a parent of a student of this teacher.
     */
    private function isParentOfStudentOfTeacher(User $user, $profile): bool
    {
        $parent = $user->parentProfile;
        if (! $parent) {
            return false;
        }

        // Get all children's User IDs through parent->students (StudentProfile) relationships
        $childUserIds = $parent->students()->with('user')->get()->pluck('user.id')->filter()->toArray();

        if (empty($childUserIds)) {
            return false;
        }

        if ($profile instanceof QuranTeacherProfile) {
            // Check if any child has subscription with this teacher
            return QuranSubscription::whereIn('student_id', $childUserIds)
                ->where('quran_teacher_id', $profile->user_id)
                ->exists();
        }

        if ($profile instanceof AcademicTeacherProfile) {
            // Check if any child has subscription with this teacher
            // AcademicSubscription uses teacher_id which references AcademicTeacherProfile.id
            return AcademicSubscription::whereIn('student_id', $childUserIds)
                ->where('teacher_id', $profile->id)
                ->exists();
        }

        return false;
    }

    /**
     * Check if profile belongs to same academy as user.
     */
    private function sameAcademy(User $user, $profile): bool
    {
        // For super_admin, use the selected academy context
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            $userAcademyId = AcademyContextService::getCurrentAcademyId();
            // If super admin is in global view (no specific academy selected), allow access
            if (! $userAcademyId) {
                return true;
            }

            return $profile->academy_id === $userAcademyId;
        }

        // For other users, use their assigned academy
        return $profile->academy_id === $user->academy_id;
    }
}
