<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\BaseSubscription;
use App\Enums\SessionStatus;

/**
 * Base Subscription Details Service
 *
 * Abstract base class for subscription detail services
 * Provides shared logic for all subscription types
 */
abstract class BaseSubscriptionDetailsService
{
    /**
     * Get subscription details for widget display
     * Must be implemented by child classes for type-specific logic
     *
     * @param BaseSubscription $subscription
     * @return array
     */
    abstract public function getSubscriptionDetails(BaseSubscription $subscription): array;

    /**
     * Calculate sessions completion percentage
     *
     * @param BaseSubscription $subscription
     * @return float
     */
    protected function calculateSessionsPercentage(BaseSubscription $subscription): float
    {
        $totalSessions = $subscription->getTotalSessions();

        if ($totalSessions <= 0) {
            return 0;
        }

        $sessionsUsed = $subscription->getSessionsUsed();

        return min(100, round(($sessionsUsed / $totalSessions) * 100, 1));
    }

    /**
     * Get billing cycle text in English
     */
    protected function getBillingCycleText(?BillingCycle $billingCycle): string
    {
        return $billingCycle?->labelEn() ?? 'Unknown';
    }

    /**
     * Get billing cycle text in Arabic
     */
    protected function getBillingCycleTextArabic(?BillingCycle $billingCycle): string
    {
        return $billingCycle?->label() ?? 'غير محدد';
    }

    /**
     * Get status badge CSS class
     */
    protected function getStatusBadgeClass(?SessionSubscriptionStatus $status): string
    {
        return $status?->badgeClasses() ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get payment status badge CSS class
     */
    protected function getPaymentStatusBadgeClass(?SubscriptionPaymentStatus $paymentStatus): string
    {
        return $paymentStatus?->badgeClasses() ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get days until next payment
     *
     * @param BaseSubscription $subscription
     * @return int|null
     */
    protected function getDaysUntilNextPayment(BaseSubscription $subscription): ?int
    {
        if (!$subscription->next_billing_date) {
            return null;
        }

        $daysUntil = now()->diffInDays($subscription->next_billing_date, false);

        return (int) ceil($daysUntil);
    }

    /**
     * Get subscription status text in Arabic
     */
    public function getStatusTextArabic(?SessionSubscriptionStatus $status): string
    {
        return $status?->label() ?? 'غير معروف';
    }

    /**
     * Get payment status text in Arabic
     */
    public function getPaymentStatusTextArabic(?SubscriptionPaymentStatus $paymentStatus): string
    {
        return $paymentStatus?->label() ?? 'غير معروف';
    }

    /**
     * Check if subscription needs renewal soon
     */
    public function needsRenewalSoon(BaseSubscription $subscription, int $daysThreshold = 7): bool
    {
        if ($subscription->status !== SessionSubscriptionStatus::ACTIVE) {
            return false;
        }

        $sessionsRemaining = $subscription->getSessionsRemaining();
        if ($sessionsRemaining <= 3) {
            return true;
        }

        $daysUntilPayment = $this->getDaysUntilNextPayment($subscription);

        return $daysUntilPayment !== null && $daysUntilPayment <= $daysThreshold;
    }

    /**
     * Get formatted price string
     *
     * @param BaseSubscription $subscription
     * @return string
     */
    public function getFormattedPrice(BaseSubscription $subscription): string
    {
        return number_format($subscription->final_price, 2) . ' ' . $subscription->currency;
    }

    /**
     * Get renewal message based on sessions remaining
     */
    public function getRenewalMessage(BaseSubscription $subscription): ?string
    {
        if ($subscription->status !== SessionSubscriptionStatus::ACTIVE) {
            return null;
        }

        $sessionsRemaining = $subscription->getSessionsRemaining();

        if ($sessionsRemaining === 0) {
            return 'لقد استنفذت جميع الجلسات. يرجى تجديد الاشتراك للمتابعة.';
        }

        if ($sessionsRemaining <= 3) {
            return "تبقى لديك {$sessionsRemaining} جلسات فقط. قد ترغب في تجديد اشتراكك قريباً.";
        }

        $daysUntilPayment = $this->getDaysUntilNextPayment($subscription);

        if ($daysUntilPayment !== null && $daysUntilPayment <= 7 && $daysUntilPayment > 0) {
            return "سيتم تجديد اشتراكك بعد {$daysUntilPayment} أيام.";
        }

        return null;
    }
}
