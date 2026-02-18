<?php

namespace App\Policies;

use App\Models\QuranSubscription;
use App\Models\AcademicSubscription;
use App\Services\AcademyContextService;
use App\Enums\UserType;
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
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value, 'teacher', UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value]);
    }

    /**
     * Determine whether the user can view the student profile.
     */
    public function view(User $user, StudentProfile $profile): bool
    {
        // Admins can view any profile in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $profile);
        }

        // Students can view their own profile
        if ($this->isOwnProfile($user, $profile)) {
            return true;
        }

        // Teachers can view profiles of their students
        if ($user->hasRole(['teacher', UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
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
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value]);
    }

    /**
     * Determine whether the user can update the student profile.
     */
    public function update(User $user, StudentProfile $profile): bool
    {
        // Admins can update any profile in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
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
        // Admins can delete student profiles in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return $this->sameAcademy($user, $profile);
        }

        return false;
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
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
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
        // Get the student's User ID
        $studentUserId = $profile->user_id;

        // Check Quran teacher - subscriptions use student_id (User ID) and quran_teacher_id (User ID)
        if ($user->hasRole([UserType::QURAN_TEACHER->value])) {
            if (QuranSubscription::where('student_id', $studentUserId)
                ->where('quran_teacher_id', $user->id)
                ->exists()) {
                return true;
            }
        }

        // Check Academic teacher - subscriptions use student_id (User ID) and teacher_id (Profile ID)
        if ($user->academicTeacherProfile) {
            if (AcademicSubscription::where('student_id', $studentUserId)
                ->where('teacher_id', $user->academicTeacherProfile->id)
                ->exists()) {
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
        if (! $parent) {
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
        // Load grade level relationship if not loaded to access academy_id
        if (! $profile->relationLoaded('gradeLevel')) {
            $profile->load('gradeLevel');
        }

        // Get profile's academy ID through the accessor
        $profileAcademyId = $profile->academy_id;

        // For super_admin, use the selected academy context
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            $userAcademyId = AcademyContextService::getCurrentAcademyId();
            // If super admin is in global view (no specific academy selected), allow access
            if (! $userAcademyId) {
                return true;
            }

            return $profileAcademyId === $userAcademyId;
        }

        // For other users, use their assigned academy
        return $profileAcademyId === $user->academy_id;
    }
}
