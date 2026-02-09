<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\TeacherPayout;
use App\Models\User;
use App\Services\AcademyContextService;

/**
 * Teacher Payout Policy
 *
 * Authorization policy for teacher payout access and management.
 * Controls who can view, create, approve, and process payouts.
 */
class TeacherPayoutPolicy
{
    /**
     * Determine whether the user can view any teacher payouts.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole([
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
            UserType::SUPERVISOR->value,
            'teacher',
            UserType::ACADEMIC_TEACHER->value,
        ]);
    }

    /**
     * Determine whether the user can view the teacher payout.
     */
    public function view(User $user, TeacherPayout $payout): bool
    {
        // Admins can view any payout in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $payout);
        }

        // Teachers can view their own payouts
        if ($user->hasRole(['teacher', UserType::ACADEMIC_TEACHER->value])) {
            return $this->isPayoutOwner($user, $payout);
        }

        return false;
    }

    /**
     * Determine whether the user can create teacher payouts.
     */
    public function create(User $user): bool
    {
        // Only admins can create payouts
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value]);
    }

    /**
     * Determine whether the user can update the teacher payout.
     */
    public function update(User $user, TeacherPayout $payout): bool
    {
        // Only admins can update payouts
        if (! $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return false;
        }

        // Must be same academy
        if (! $this->sameAcademy($user, $payout)) {
            return false;
        }

        // Can only update pending payouts
        return $payout->status === \App\Enums\PayoutStatus::PENDING;
    }

    /**
     * Determine whether the user can delete the teacher payout.
     */
    public function delete(User $user, TeacherPayout $payout): bool
    {
        // Only admins can delete payouts
        if (! $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return false;
        }

        // Must be same academy
        if (! $this->sameAcademy($user, $payout)) {
            return false;
        }

        // Can only delete pending or rejected payouts
        return in_array($payout->status, [
            \App\Enums\PayoutStatus::PENDING,
            \App\Enums\PayoutStatus::REJECTED,
        ]);
    }

    /**
     * Determine whether the user can approve the teacher payout.
     */
    public function approve(User $user, TeacherPayout $payout): bool
    {
        // Only admins can approve payouts
        if (! $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return false;
        }

        // Must be same academy
        if (! $this->sameAcademy($user, $payout)) {
            return false;
        }

        // Can only approve pending payouts
        return $payout->canApprove();
    }

    /**
     * Determine whether the user can reject the teacher payout.
     */
    public function reject(User $user, TeacherPayout $payout): bool
    {
        // Only admins can reject payouts
        if (! $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return false;
        }

        // Must be same academy
        if (! $this->sameAcademy($user, $payout)) {
            return false;
        }

        // Can reject pending or approved payouts
        return $payout->canReject();
    }

    /**
     * Determine whether the user can restore the payout.
     */
    public function restore(User $user, TeacherPayout $payout): bool
    {
        // Only super admins can restore payouts
        return $user->hasRole(UserType::SUPER_ADMIN->value);
    }

    /**
     * Determine whether the user can permanently delete the payout.
     */
    public function forceDelete(User $user, TeacherPayout $payout): bool
    {
        // Only super admins can permanently delete payouts
        return $user->hasRole(UserType::SUPER_ADMIN->value);
    }

    /**
     * Check if user is the owner of this payout.
     */
    private function isPayoutOwner(User $user, TeacherPayout $payout): bool
    {
        // For Quran teacher payouts
        if ($payout->teacher_type === 'App\Models\QuranTeacherProfile') {
            $teacherProfile = $user->quranTeacherProfile;

            return $teacherProfile && $payout->teacher_id === $teacherProfile->id;
        }

        // For Academic teacher payouts
        if ($payout->teacher_type === 'App\Models\AcademicTeacherProfile') {
            $teacherProfile = $user->academicTeacherProfile;

            return $teacherProfile && $payout->teacher_id === $teacherProfile->id;
        }

        return false;
    }

    /**
     * Check if payout belongs to same academy as user.
     */
    private function sameAcademy(User $user, TeacherPayout $payout): bool
    {
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            $userAcademyId = AcademyContextService::getCurrentAcademyId();
            if (! $userAcademyId) {
                return true; // Super admin with no context can access all
            }

            return $payout->academy_id === $userAcademyId;
        }

        return $payout->academy_id === $user->academy_id;
    }
}
