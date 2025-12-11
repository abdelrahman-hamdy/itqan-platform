<?php

namespace App\Policies;

use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use App\Models\User;

/**
 * Subscription Policy
 *
 * Authorization policy for subscription access across all subscription types.
 * Handles Quran, Academic, and Course subscriptions.
 */
class SubscriptionPolicy
{
    /**
     * Determine whether the user can view any subscriptions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['superadmin', 'admin', 'supervisor', 'teacher', 'quran_teacher', 'academic_teacher', 'student']);
    }

    /**
     * Determine whether the user can view the subscription.
     */
    public function view(User $user, $subscription): bool
    {
        // Admins and supervisors can view any subscription in their academy
        if ($user->hasRole(['superadmin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $subscription);
        }

        // Teachers can view subscriptions for their students
        if ($user->hasRole(['teacher', 'quran_teacher', 'academic_teacher'])) {
            return $this->isTeacherOfSubscription($user, $subscription);
        }

        // Students can view their own subscriptions
        if ($user->hasRole('student')) {
            return $this->isSubscriptionOwner($user, $subscription);
        }

        // Parents can view their children's subscriptions
        if ($user->isParent()) {
            return $this->isParentOfSubscriptionOwner($user, $subscription);
        }

        return false;
    }

    /**
     * Determine whether the user can create subscriptions.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['superadmin', 'admin', 'supervisor']);
    }

    /**
     * Determine whether the user can update the subscription.
     */
    public function update(User $user, $subscription): bool
    {
        // Only admins can update subscriptions
        if ($user->hasRole(['superadmin', 'admin'])) {
            return $this->sameAcademy($user, $subscription);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the subscription.
     */
    public function delete(User $user, $subscription): bool
    {
        // Only superadmin can delete subscriptions
        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can pause the subscription.
     */
    public function pause(User $user, $subscription): bool
    {
        return $this->update($user, $subscription);
    }

    /**
     * Determine whether the user can resume the subscription.
     */
    public function resume(User $user, $subscription): bool
    {
        return $this->update($user, $subscription);
    }

    /**
     * Determine whether the user can cancel the subscription.
     */
    public function cancel(User $user, $subscription): bool
    {
        return $this->update($user, $subscription);
    }

    /**
     * Determine whether the user can renew the subscription.
     */
    public function renew(User $user, $subscription): bool
    {
        // Owners can renew their own subscriptions
        if ($this->isSubscriptionOwner($user, $subscription)) {
            return true;
        }

        // Parents can renew their children's subscriptions
        if ($this->isParentOfSubscriptionOwner($user, $subscription)) {
            return true;
        }

        // Admins can renew any subscription
        return $user->hasRole(['superadmin', 'admin']);
    }

    /**
     * Check if user owns this subscription.
     */
    private function isSubscriptionOwner(User $user, $subscription): bool
    {
        $studentProfile = $user->studentProfileUnscoped;
        if (!$studentProfile) {
            return false;
        }

        if ($subscription instanceof QuranSubscription || $subscription instanceof AcademicSubscription) {
            return $subscription->student_profile_id === $studentProfile->id;
        }

        if ($subscription instanceof CourseSubscription) {
            return $subscription->user_id === $user->id;
        }

        return false;
    }

    /**
     * Check if user is the teacher of this subscription.
     */
    private function isTeacherOfSubscription(User $user, $subscription): bool
    {
        if ($subscription instanceof QuranSubscription) {
            return $subscription->quran_teacher_profile_id === $user->quranTeacherProfile?->id;
        }

        if ($subscription instanceof AcademicSubscription) {
            return $subscription->academic_teacher_profile_id === $user->academicTeacherProfile?->id;
        }

        if ($subscription instanceof CourseSubscription) {
            return $subscription->recordedCourse?->academic_teacher_profile_id === $user->academicTeacherProfile?->id;
        }

        return false;
    }

    /**
     * Check if user is a parent of the subscription owner.
     */
    private function isParentOfSubscriptionOwner(User $user, $subscription): bool
    {
        $parent = $user->parentProfile;
        if (!$parent) {
            return false;
        }

        $studentIds = $parent->students()->pluck('student_profiles.id')->toArray();

        if ($subscription instanceof QuranSubscription || $subscription instanceof AcademicSubscription) {
            return in_array($subscription->student_profile_id, $studentIds);
        }

        if ($subscription instanceof CourseSubscription) {
            $userIds = $parent->students()->with('user')->get()->pluck('user.id')->filter()->toArray();
            return in_array($subscription->user_id, $userIds);
        }

        return false;
    }

    /**
     * Check if subscription belongs to same academy as user.
     */
    private function sameAcademy(User $user, $subscription): bool
    {
        $userAcademyId = $user->getCurrentAcademyId();
        if (!$userAcademyId) {
            return false;
        }

        return $subscription->academy_id === $userAcademyId;
    }
}
