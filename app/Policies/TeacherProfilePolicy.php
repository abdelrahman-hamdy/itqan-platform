<?php

namespace App\Policies;

use App\Models\AcademicTeacherProfile;
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
        return $user->hasRole(['superadmin', 'admin', 'supervisor', 'student']);
    }

    /**
     * Determine whether the user can view the teacher profile.
     */
    public function view(User $user, $profile): bool
    {
        // Admins can view any profile in their academy
        if ($user->hasRole(['superadmin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $profile);
        }

        // Teachers can view their own profile
        if ($this->isOwnProfile($user, $profile)) {
            return true;
        }

        // Students can view profiles of their teachers
        if ($user->hasRole('student')) {
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
        return $user->hasRole(['superadmin', 'admin']);
    }

    /**
     * Determine whether the user can update the teacher profile.
     */
    public function update(User $user, $profile): bool
    {
        // Admins can update any profile in their academy
        if ($user->hasRole(['superadmin', 'admin'])) {
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
        // Only superadmin can delete teacher profiles
        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can view teacher's earnings.
     */
    public function viewEarnings(User $user, $profile): bool
    {
        // Only the teacher themselves or admin can view earnings
        if ($user->hasRole(['superadmin', 'admin'])) {
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
        $studentProfile = $user->studentProfileUnscoped;
        if (!$studentProfile) {
            return false;
        }

        if ($profile instanceof QuranTeacherProfile) {
            // Check if student has subscriptions with this teacher
            return $studentProfile->quranSubscriptions()
                ->where('quran_teacher_profile_id', $profile->id)
                ->exists();
        }

        if ($profile instanceof AcademicTeacherProfile) {
            // Check if student has subscriptions with this teacher
            return $studentProfile->academicSubscriptions()
                ->where('academic_teacher_profile_id', $profile->id)
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
        if (!$parent) {
            return false;
        }

        foreach ($parent->students as $student) {
            if ($profile instanceof QuranTeacherProfile) {
                if ($student->quranSubscriptions()->where('quran_teacher_profile_id', $profile->id)->exists()) {
                    return true;
                }
            }

            if ($profile instanceof AcademicTeacherProfile) {
                if ($student->academicSubscriptions()->where('academic_teacher_profile_id', $profile->id)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if profile belongs to same academy as user.
     */
    private function sameAcademy(User $user, $profile): bool
    {
        $userAcademyId = $user->getCurrentAcademyId();
        if (!$userAcademyId) {
            return false;
        }

        return $profile->academy_id === $userAcademyId;
    }
}
