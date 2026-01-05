<?php

namespace App\Services;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Enums\SessionStatus;

/**
 * Service for managing student subscriptions.
 *
 * Extracted from StudentProfileController to reduce controller size.
 * Handles subscription listing, auto-renewal toggling, and cancellation.
 */
class StudentSubscriptionService
{
    /**
     * Get all subscriptions for a student.
     *
     * @param  User  $user  The student user
     * @return array Contains different subscription types
     */
    public function getSubscriptions(User $user): array
    {
        $academy = $user->academy;

        return [
            'individual_quran' => $this->getIndividualQuranSubscriptions($user, $academy),
            'group_quran' => $this->getGroupQuranSubscriptions($user, $academy),
            'academic' => $this->getAcademicSubscriptions($user, $academy),
        ];
    }

    /**
     * Get individual Quran subscriptions.
     */
    public function getIndividualQuranSubscriptions(User $user, $academy): Collection
    {
        return QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('subscription_type', 'individual')
            ->with(['quranTeacher', 'package', 'individualCircle', 'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get group Quran subscriptions.
     */
    public function getGroupQuranSubscriptions(User $user, $academy): Collection
    {
        return QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('subscription_type', ['group', 'circle'])
            ->with(['quranTeacher', 'package', 'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get academic subscriptions.
     */
    public function getAcademicSubscriptions(User $user, $academy): Collection
    {
        return AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher.user', 'subject', 'gradeLevel', 'academicPackage'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Toggle auto-renewal for a subscription.
     *
     * @param  User  $user  The student user
     * @param  string  $type  Subscription type ('quran' or 'academic')
     * @param  string  $id  Subscription ID
     * @return array Result with success status and message
     */
    public function toggleAutoRenew(User $user, string $type, string $id): array
    {
        $subscription = $this->findSubscription($user, $type, $id);

        if (!$subscription) {
            Log::warning('Subscription not found for toggle', [
                'type' => $type,
                'id' => $id,
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'error' => 'الاشتراك غير موجود',
            ];
        }

        $oldValue = $subscription->auto_renew;
        $subscription->auto_renew = !$subscription->auto_renew;
        $subscription->save();

        Log::info('Auto-renew toggled', [
            'subscription_id' => $subscription->id,
            'old_value' => $oldValue,
            'new_value' => $subscription->auto_renew,
        ]);

        $message = $subscription->auto_renew
            ? 'تم تفعيل التجديد التلقائي بنجاح'
            : 'تم إيقاف التجديد التلقائي بنجاح';

        return [
            'success' => true,
            'message' => $message,
            'auto_renew' => $subscription->auto_renew,
        ];
    }

    /**
     * Cancel a subscription.
     *
     * @param  User  $user  The student user
     * @param  string  $type  Subscription type ('quran' or 'academic')
     * @param  string  $id  Subscription ID
     * @return array Result with success status and message
     */
    public function cancelSubscription(User $user, string $type, string $id): array
    {
        $subscription = $this->findSubscription($user, $type, $id);

        if (!$subscription) {
            return [
                'success' => false,
                'error' => 'الاشتراك غير موجود',
            ];
        }

        // Update subscription status to cancelled
        $subscription->status = SessionSubscriptionStatus::CANCELLED;
        $subscription->cancelled_at = now();
        $subscription->cancellation_reason = 'إلغاء من قبل الطالب';
        $subscription->auto_renew = false;
        $subscription->save();

        Log::info('Student cancelled subscription', [
            'subscription_id' => $subscription->id,
            'subscription_type' => $type,
            'student_id' => $user->id,
        ]);

        return [
            'success' => true,
            'message' => 'تم إلغاء الاشتراك بنجاح',
        ];
    }

    /**
     * Find a subscription by type and ID for a user.
     *
     * @return QuranSubscription|AcademicSubscription|null
     */
    protected function findSubscription(User $user, string $type, string $id)
    {
        $academy = $user->academy;

        return match ($type) {
            'quran' => QuranSubscription::where('id', $id)
                ->where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->first(),
            'academic' => AcademicSubscription::where('id', $id)
                ->where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->first(),
            default => null,
        };
    }

    /**
     * Get active subscriptions count for a student.
     */
    public function getActiveSubscriptionsCount(User $user): array
    {
        $academy = $user->academy;

        return [
            'quran' => QuranSubscription::where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->count(),
            'academic' => AcademicSubscription::where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->count(),
        ];
    }
}
