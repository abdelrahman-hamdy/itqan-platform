<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Students can view their own certificates
        // Teachers and admins can view all certificates
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Certificate $certificate): bool
    {
        // Student can view their own certificate
        if ($user->id === $certificate->student_id) {
            return true;
        }

        // Teacher can view if they issued it
        if ($user->id === $certificate->teacher_id) {
            return true;
        }

        // Admins and super admins can view all
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return true;
        }

        // Academy staff can view certificates from their academy
        if ($user->academy_id === $certificate->academy_id &&
            $user->hasRole([UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return true;
        }

        // Parents can view their children's certificates
        if ($user->isParent()) {
            return $this->isParentOfCertificateOwner($user, $certificate);
        }

        return false;
    }

    /**
     * Check if user is a parent of the certificate owner.
     */
    private function isParentOfCertificateOwner(User $user, Certificate $certificate): bool
    {
        $parent = $user->parentProfile;
        if (! $parent) {
            return false;
        }

        // Get student user IDs through the parent-student relationship
        $studentUserIds = $parent->students()->with('user')->get()->pluck('user.id')->filter()->toArray();

        // Certificate.student_id references User.id
        return in_array($certificate->student_id, $studentUserIds);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only teachers and admins can manually create certificates
        return $user->hasRole([
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
            UserType::QURAN_TEACHER->value,
            UserType::ACADEMIC_TEACHER->value,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Certificate $certificate): bool
    {
        // Only admins can update certificates
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Certificate $certificate): bool
    {
        // Only admins can delete/revoke certificates
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Certificate $certificate): bool
    {
        // Only super admins can restore deleted certificates
        return $user->hasRole([UserType::SUPER_ADMIN->value]);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Certificate $certificate): bool
    {
        // Only super admins can permanently delete certificates
        return $user->hasRole([UserType::SUPER_ADMIN->value]);
    }

    /**
     * Determine whether the user can download the certificate.
     */
    public function download(User $user, Certificate $certificate): bool
    {
        // Same as view permission
        return $this->view($user, $certificate);
    }
}
