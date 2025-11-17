<?php

namespace App\Services;

use App\Models\QuranSubscription;
use Carbon\Carbon;

/**
 * Quran Subscription Details Service
 *
 * Generates subscription widget data for students
 * Supports monthly, quarterly, and yearly billing cycles
 */
class QuranSubscriptionDetailsService
{
    /**
     * Get subscription details for widget display
     *
     * @param QuranSubscription $subscription
     * @return array
     */
    public function getSubscriptionDetails(QuranSubscription $subscription): array
    {
        return [
            // Basic info
            'subscription_type' => $subscription->subscription_type,
            'subscription_status' => $subscription->subscription_status,
            'payment_status' => $subscription->payment_status,

            // Dates
            'starts_at' => $subscription->starts_at,
            'next_payment_at' => $subscription->next_payment_at,
            'last_payment_at' => $subscription->last_payment_at,
            'paused_at' => $subscription->paused_at,
            'cancelled_at' => $subscription->cancelled_at,

            // Sessions
            'total_sessions' => $subscription->total_sessions,
            'sessions_used' => $subscription->sessions_used,
            'sessions_remaining' => $subscription->sessions_remaining,
            'sessions_percentage' => $this->calculateSessionsPercentage($subscription),

            // Billing
            'billing_cycle' => $subscription->billing_cycle,
            'billing_cycle_text' => $this->getBillingCycleText($subscription->billing_cycle),
            'billing_cycle_ar' => $this->getBillingCycleTextArabic($subscription->billing_cycle),
            'currency' => $subscription->currency,
            'total_price' => $subscription->total_price,
            'final_price' => $subscription->final_price,
            'discount_amount' => $subscription->discount_amount,

            // Status badges
            'status_badge_class' => $this->getStatusBadgeClass($subscription->subscription_status),
            'payment_status_badge_class' => $this->getPaymentStatusBadgeClass($subscription->payment_status),

            // Trial info
            'is_trial_active' => $subscription->is_trial_active,
            'trial_used' => $subscription->trial_used,

            // Auto-renew
            'auto_renew' => $subscription->auto_renew,

            // Days until next payment
            'days_until_next_payment' => $this->getDaysUntilNextPayment($subscription),

            // Progress
            'progress_percentage' => $subscription->progress_percentage,
        ];
    }

    /**
     * Calculate sessions completion percentage
     *
     * @param QuranSubscription $subscription
     * @return float
     */
    protected function calculateSessionsPercentage(QuranSubscription $subscription): float
    {
        if ($subscription->total_sessions <= 0) {
            return 0;
        }

        return min(100, round(($subscription->sessions_used / $subscription->total_sessions) * 100, 1));
    }

    /**
     * Get billing cycle text in English
     *
     * @param string|null $billingCycle
     * @return string
     */
    protected function getBillingCycleText(?string $billingCycle): string
    {
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
     * @param string|null $billingCycle
     * @return string
     */
    protected function getBillingCycleTextArabic(?string $billingCycle): string
    {
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
     * @param string|null $status
     * @return string
     */
    protected function getStatusBadgeClass(?string $status): string
    {
        return match($status) {
            'active' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'paused' => 'bg-blue-100 text-blue-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'expired' => 'bg-gray-100 text-gray-800',
            'completed' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get payment status badge CSS class
     *
     * @param string|null $paymentStatus
     * @return string
     */
    protected function getPaymentStatusBadgeClass(?string $paymentStatus): string
    {
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
     * @param QuranSubscription $subscription
     * @return int|null
     */
    protected function getDaysUntilNextPayment(QuranSubscription $subscription): ?int
    {
        if (!$subscription->next_payment_at) {
            return null;
        }

        $daysUntil = now()->diffInDays($subscription->next_payment_at, false);

        return (int) ceil($daysUntil);
    }

    /**
     * Get subscription status text in Arabic
     *
     * @param string|null $status
     * @return string
     */
    public function getStatusTextArabic(?string $status): string
    {
        return match($status) {
            'active' => 'نشط',
            'pending' => 'في الانتظار',
            'paused' => 'متوقف مؤقتاً',
            'cancelled' => 'ملغي',
            'expired' => 'منتهي',
            'completed' => 'مكتمل',
            default => 'غير معروف',
        };
    }

    /**
     * Get payment status text in Arabic
     *
     * @param string|null $paymentStatus
     * @return string
     */
    public function getPaymentStatusTextArabic(?string $paymentStatus): string
    {
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
     * @param QuranSubscription $subscription
     * @param int $daysThreshold
     * @return bool
     */
    public function needsRenewalSoon(QuranSubscription $subscription, int $daysThreshold = 7): bool
    {
        if ($subscription->subscription_status !== 'active') {
            return false;
        }

        if ($subscription->sessions_remaining <= 3) {
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
     * @param QuranSubscription $subscription
     * @return string
     */
    public function getFormattedPrice(QuranSubscription $subscription): string
    {
        return number_format($subscription->final_price, 2) . ' ' . $subscription->currency;
    }

    /**
     * Get renewal message based on sessions remaining
     *
     * @param QuranSubscription $subscription
     * @return string|null
     */
    public function getRenewalMessage(QuranSubscription $subscription): ?string
    {
        if ($subscription->subscription_status !== 'active') {
            return null;
        }

        if ($subscription->sessions_remaining === 0) {
            return 'لقد استنفذت جميع الجلسات. يرجى تجديد الاشتراك للمتابعة.';
        }

        if ($subscription->sessions_remaining <= 3) {
            return "تبقى لديك {$subscription->sessions_remaining} جلسات فقط. قد ترغب في تجديد اشتراكك قريباً.";
        }

        $daysUntilPayment = $this->getDaysUntilNextPayment($subscription);

        if ($daysUntilPayment !== null && $daysUntilPayment <= 7 && $daysUntilPayment > 0) {
            return "سيتم تجديد اشتراكك بعد {$daysUntilPayment} أيام.";
        }

        return null;
    }
}
