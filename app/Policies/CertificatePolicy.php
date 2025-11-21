<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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
        if ($user->hasAnyRole(['super_admin', 'admin', 'supervisor'])) {
            return true;
        }

        // Academy staff can view certificates from their academy
        if ($user->academy_id === $certificate->academy_id &&
            $user->hasAnyRole(['admin', 'supervisor'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only teachers and admins can manually create certificates
        return $user->hasAnyRole([
            'super_admin',
            'admin',
            'quran_teacher',
            'academic_teacher'
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Certificate $certificate): bool
    {
        // Only admins can update certificates
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Certificate $certificate): bool
    {
        // Only admins can delete/revoke certificates
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Certificate $certificate): bool
    {
        // Only super admins can restore deleted certificates
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Certificate $certificate): bool
    {
        // Only super admins can permanently delete certificates
        return $user->hasRole('super_admin');
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
