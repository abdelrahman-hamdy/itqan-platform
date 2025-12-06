<?php

namespace App\Services;

use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;

/**
 * Academic Subscription Details Service
 *
 * Generates subscription widget data for academic students
 * Handles individual academic tutoring subscriptions
 */
class AcademicSubscriptionDetailsService extends BaseSubscriptionDetailsService
{
    /**
     * Get subscription details for widget display
     *
     * @param BaseSubscription $subscription
     * @return array
     */
    public function getSubscriptionDetails(BaseSubscription $subscription): array
    {
        return [
            // Basic info
            'subscription_type' => 'academic',
            'status' => $subscription->status,
            'payment_status' => $subscription->payment_status,

            // Dates
            'starts_at' => $subscription->starts_at,
            'next_payment_at' => $subscription->next_payment_at,
            'last_payment_at' => $subscription->last_payment_at,
            'paused_at' => $subscription->paused_at,
            'cancelled_at' => $subscription->cancelled_at,

            // Sessions
            'total_sessions' => $subscription->getTotalSessions(),
            'sessions_used' => $subscription->getSessionsUsed(),
            'sessions_remaining' => $subscription->getSessionsRemaining(),
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
            'status_badge_class' => $this->getStatusBadgeClass($subscription->status),
            'payment_status_badge_class' => $this->getPaymentStatusBadgeClass($subscription->payment_status),

            // Trial info (usually not applicable for academic)
            'is_trial_active' => false,
            'trial_used' => false,

            // Auto-renew
            'auto_renew' => $subscription->auto_renew ?? false,

            // Days until next payment
            'days_until_next_payment' => $this->getDaysUntilNextPayment($subscription),

            // Progress
            'progress_percentage' => $this->calculateProgressPercentage($subscription),
        ];
    }

    /**
     * Calculate progress percentage based on time elapsed
     *
     * @param BaseSubscription $subscription
     * @return float
     */
    protected function calculateProgressPercentage(BaseSubscription $subscription): float
    {
        if (!$subscription->starts_at || !$subscription->ends_at) {
            return 0;
        }

        $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);
        if ($totalDays <= 0) {
            return 0;
        }

        $elapsedDays = $subscription->starts_at->diffInDays(now());
        $progressPercentage = ($elapsedDays / $totalDays) * 100;

        return min(100, round($progressPercentage, 1));
    }
}
