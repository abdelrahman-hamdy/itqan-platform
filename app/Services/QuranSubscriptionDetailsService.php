<?php

namespace App\Services;

use App\Models\BaseSubscription;
use App\Models\QuranSubscription;
use App\Enums\SessionStatus;

/**
 * Quran Subscription Details Service
 *
 * Generates subscription widget data for students
 * Supports monthly, quarterly, and yearly billing cycles
 */
class QuranSubscriptionDetailsService extends BaseSubscriptionDetailsService
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
            'subscription_type' => $subscription->subscription_type,
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

}
