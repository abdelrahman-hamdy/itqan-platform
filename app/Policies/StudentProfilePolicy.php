<?php

namespace App\Policies;

use App\Models\StudentProfile;
use App\Models\User;

/**
 * Student Profile Policy
 *
 * Authorization policy for student profile access.
 */
class StudentProfilePolicy
{
    /**
     * Determine whether the user can view any student profiles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['superadmin', 'admin', 'supervisor', 'teacher', 'quran_teacher', 'academic_teacher']);
    }

    /**
     * Determine whether the user can view the student profile.
     */
    public function view(User $user, StudentProfile $profile): bool
    {
        // Admins can view any profile in their academy
        if ($user->hasRole(['superadmin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $profile);
        }

        // Students can view their own profile
        if ($this->isOwnProfile($user, $profile)) {
            return true;
        }

        // Teachers can view profiles of their students
        if ($user->hasRole(['teacher', 'quran_teacher', 'academic_teacher'])) {
            return $this->isTeacherOfStudent($user, $profile);
        }

        // Parents can view their children's profiles
        if ($user->isParent()) {
            return $this->isParentOfStudent($user, $profile);
        }

        return false;
    }

    /**
     * Determine whether the user can create student profiles.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['superadmin', 'admin', 'supervisor']);
    }

    /**
     * Determine whether the user can update the student profile.
     */
    public function update(User $user, StudentProfile $profile): bool
    {
        // Admins can update any profile in their academy
        if ($user->hasRole(['superadmin', 'admin'])) {
            return $this->sameAcademy($user, $profile);
        }

        // Students can update their own profile
        return $this->isOwnProfile($user, $profile);
    }

    /**
     * Determine whether the user can delete the student profile.
     */
    public function delete(User $user, StudentProfile $profile): bool
    {
        // Only superadmin can delete student profiles
        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can view student's progress.
     */
    public function viewProgress(User $user, StudentProfile $profile): bool
    {
        return $this->view($user, $profile);
    }

    /**
     * Determine whether the user can view student's certificates.
     */
    public function viewCertificates(User $user, StudentProfile $profile): bool
    {
        return $this->view($user, $profile);
    }

    /**
     * Determine whether the user can view student's payments.
     */
    public function viewPayments(User $user, StudentProfile $profile): bool
    {
        // Only the student, their parent, or admin can view payments
        if ($user->hasRole(['superadmin', 'admin'])) {
            return $this->sameAcademy($user, $profile);
        }

        if ($this->isOwnProfile($user, $profile)) {
            return true;
        }

        return $this->isParentOfStudent($user, $profile);
    }

    /**
     * Check if this is the user's own profile.
     */
    private function isOwnProfile(User $user, StudentProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    /**
     * Check if user is a teacher of this student.
     */
    private function isTeacherOfStudent(User $user, StudentProfile $profile): bool
    {
        // Check Quran teacher
        if ($user->quranTeacherProfile) {
            if ($profile->quranSubscriptions()->where('quran_teacher_profile_id', $user->quranTeacherProfile->id)->exists()) {
                return true;
            }
        }

        // Check Academic teacher
        if ($user->academicTeacherProfile) {
            if ($profile->academicSubscriptions()->where('academic_teacher_profile_id', $user->academicTeacherProfile->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is a parent of this student.
     */
    private function isParentOfStudent(User $user, StudentProfile $profile): bool
    {
        $parent = $user->parentProfile;
        if (!$parent) {
            return false;
        }

        return $parent->students()
            ->where('student_profiles.id', $profile->id)
            ->exists();
    }

    /**
     * Check if profile belongs to same academy as user.
     */
    private function sameAcademy(User $user, StudentProfile $profile): bool
    {
        $userAcademyId = $user->getCurrentAcademyId();
        if (!$userAcademyId) {
            return false;
        }

        return $profile->academy_id === $userAcademyId;
    }
}
