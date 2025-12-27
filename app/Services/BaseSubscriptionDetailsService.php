<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
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
     *
     * @param BillingCycle|string|null $billingCycle
     * @return string
     */
    protected function getBillingCycleText(BillingCycle|string|null $billingCycle): string
    {
        if ($billingCycle instanceof BillingCycle) {
            return $billingCycle->labelEn();
        }

        // Handle legacy string values
        return match($billingCycle) {
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            default => 'Unknown',
        };
    }

    /**
     * Get billing cycle text in Arabic
     *
     * @param BillingCycle|string|null $billingCycle
     * @return string
     */
    protected function getBillingCycleTextArabic(BillingCycle|string|null $billingCycle): string
    {
        if ($billingCycle instanceof BillingCycle) {
            return $billingCycle->label();
        }

        // Handle legacy string values
        return match($billingCycle) {
            'monthly' => 'شهرية',
            'quarterly' => 'ربع سنوية',
            'yearly' => 'سنوية',
            default => 'غير محدد',
        };
    }

    /**
     * Get status badge CSS class
     *
     * @param SubscriptionStatus|string|null $status
     * @return string
     */
    protected function getStatusBadgeClass(SubscriptionStatus|string|null $status): string
    {
        if ($status instanceof SubscriptionStatus) {
            return $status->badgeClasses();
        }

        // Handle legacy string values
        return match($status) {
            SubscriptionStatus::ACTIVE->value => 'bg-green-100 text-green-800',
            SubscriptionStatus::PENDING->value => 'bg-yellow-100 text-yellow-800',
            SubscriptionStatus::PAUSED->value => 'bg-blue-100 text-blue-800',
            SubscriptionStatus::CANCELLED->value => 'bg-red-100 text-red-800',
            SubscriptionStatus::EXPIRED->value => 'bg-gray-100 text-gray-800',
            SubscriptionStatus::COMPLETED->value => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get payment status badge CSS class
     *
     * @param SubscriptionPaymentStatus|string|null $paymentStatus
     * @return string
     */
    protected function getPaymentStatusBadgeClass(SubscriptionPaymentStatus|string|null $paymentStatus): string
    {
        if ($paymentStatus instanceof SubscriptionPaymentStatus) {
            return $paymentStatus->badgeClasses();
        }

        // Handle legacy string values
        return match($paymentStatus) {
            'paid' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'failed' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800',
        };
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
     *
     * @param SubscriptionStatus|string|null $status
     * @return string
     */
    public function getStatusTextArabic(SubscriptionStatus|string|null $status): string
    {
        if ($status instanceof SubscriptionStatus) {
            return $status->label();
        }

        // Handle legacy string values
        return match($status) {
            SubscriptionStatus::ACTIVE->value => 'نشط',
            SubscriptionStatus::PENDING->value => 'في الانتظار',
            SubscriptionStatus::PAUSED->value => 'متوقف مؤقتاً',
            SubscriptionStatus::CANCELLED->value => 'ملغي',
            SubscriptionStatus::EXPIRED->value => 'منتهي',
            SubscriptionStatus::COMPLETED->value => 'مكتمل',
            default => 'غير معروف',
        };
    }

    /**
     * Get payment status text in Arabic
     *
     * @param SubscriptionPaymentStatus|string|null $paymentStatus
     * @return string
     */
    public function getPaymentStatusTextArabic(SubscriptionPaymentStatus|string|null $paymentStatus): string
    {
        if ($paymentStatus instanceof SubscriptionPaymentStatus) {
            return $paymentStatus->label();
        }

        // Handle legacy string values
        return match($paymentStatus) {
            'paid' => 'مدفوع',
            'pending' => 'في انتظار الدفع',
            'failed' => 'فشل الدفع',
            'refunded' => 'مسترد',
            default => 'غير معروف',
        };
    }

    /**
     * Check if subscription needs renewal soon
     *
     * @param BaseSubscription $subscription
     * @param int $daysThreshold
     * @return bool
     */
    public function needsRenewalSoon(BaseSubscription $subscription, int $daysThreshold = 7): bool
    {
        // Check if status is active (handle both enum and legacy string)
        $isActive = $subscription->status instanceof SubscriptionStatus
            ? $subscription->status === SubscriptionStatus::ACTIVE
            : $subscription->status === SubscriptionStatus::ACTIVE->value;

        if (!$isActive) {
            return false;
        }

        $sessionsRemaining = $subscription->getSessionsRemaining();
        if ($sessionsRemaining <= 3) {
            return true;
        }

        $daysUntilPayment = $this->getDaysUntilNextPayment($subscription);

        if ($daysUntilPayment !== null && $daysUntilPayment <= $daysThreshold) {
            return true;
        }

        return false;
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
     *
     * @param BaseSubscription $subscription
     * @return string|null
     */
    public function getRenewalMessage(BaseSubscription $subscription): ?string
    {
        // Check if status is active (handle both enum and legacy string)
        $isActive = $subscription->status instanceof SubscriptionStatus
            ? $subscription->status === SubscriptionStatus::ACTIVE
            : $subscription->status === SubscriptionStatus::ACTIVE->value;

        if (!$isActive) {
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
