<?php

namespace App\Policies;

use App\Services\AcademyContextService;
use App\Enums\UserType;
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
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value, 'teacher', UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value, UserType::STUDENT->value, UserType::PARENT->value]);
    }

    /**
     * Determine whether the user can view the subscription.
     */
    public function view(User $user, $subscription): bool
    {
        // Admins and supervisors can view any subscription in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $subscription);
        }

        // Teachers can view subscriptions for their students
        if ($user->hasRole(['teacher', UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            return $this->isTeacherOfSubscription($user, $subscription);
        }

        // Students can view their own subscriptions
        if ($user->hasRole(UserType::STUDENT->value)) {
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
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value]);
    }

    /**
     * Determine whether the user can enroll themselves in a course/subscription.
     * This is different from create - students can enroll themselves.
     */
    public function enroll(User $user): bool
    {
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value, UserType::STUDENT->value]);
    }

    /**
     * Determine whether the user can update the subscription.
     */
    public function update(User $user, $subscription): bool
    {
        // Only admins can update subscriptions
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return $this->sameAcademy($user, $subscription);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the subscription.
     */
    public function delete(User $user, $subscription): bool
    {
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            return true;
        }

        return $user->hasRole(UserType::ADMIN->value) && $this->sameAcademy($user, $subscription);
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
        // Owners can renew their own subscriptions (scoped to same academy)
        if ($this->isSubscriptionOwner($user, $subscription) && $this->sameAcademy($user, $subscription)) {
            return true;
        }

        // Parents can renew their children's subscriptions (scoped to same academy)
        if ($this->isParentOfSubscriptionOwner($user, $subscription) && $this->sameAcademy($user, $subscription)) {
            return true;
        }

        // SuperAdmins can renew any subscription regardless of academy
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            return true;
        }

        // Admins can only renew subscriptions in their own academy
        if ($user->hasRole(UserType::ADMIN->value)) {
            return $this->sameAcademy($user, $subscription);
        }

        return false;
    }

    /**
     * Check if user owns this subscription.
     */
    private function isSubscriptionOwner(User $user, $subscription): bool
    {
        // QuranSubscription and AcademicSubscription use student_id (user ID), not student_profile_id
        if ($subscription instanceof QuranSubscription || $subscription instanceof AcademicSubscription) {
            return $subscription->student_id === $user->id;
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
        // QuranSubscription uses quran_teacher_id (user ID), not profile ID
        if ($subscription instanceof QuranSubscription) {
            return $subscription->quran_teacher_id === $user->id;
        }

        // AcademicSubscription uses academic_teacher_id (user ID), not profile ID
        if ($subscription instanceof AcademicSubscription) {
            return $subscription->academic_teacher_id === $user->id;
        }

        if ($subscription instanceof CourseSubscription) {
            return $subscription->recordedCourse?->academic_teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Check if user is a parent of the subscription owner.
     * Uses exists() queries to avoid loading full collections (N+1 prevention).
     */
    private function isParentOfSubscriptionOwner(User $user, $subscription): bool
    {
        $parent = $user->parentProfile;
        if (! $parent) {
            return false;
        }

        if ($subscription instanceof QuranSubscription || $subscription instanceof AcademicSubscription) {
            // These subscriptions use student_id (user ID)
            // Check using exists() to avoid loading all children
            return $parent->students()
                ->whereHas('user', fn ($q) => $q->where('users.id', $subscription->student_id))
                ->exists();
        }

        if ($subscription instanceof CourseSubscription) {
            return $parent->students()
                ->whereHas('user', fn ($q) => $q->where('users.id', $subscription->user_id))
                ->exists();
        }

        return false;
    }

    /**
     * Check if subscription belongs to same academy as user.
     */
    private function sameAcademy(User $user, $subscription): bool
    {
        // For super_admin, use the selected academy context
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            $userAcademyId = AcademyContextService::getCurrentAcademyId();
            // If super admin is in global view (no specific academy selected), allow access
            if (! $userAcademyId) {
                return true;
            }

            return $subscription->academy_id === $userAcademyId;
        }

        // For other users, use their assigned academy
        return $subscription->academy_id === $user->academy_id;
    }
}
