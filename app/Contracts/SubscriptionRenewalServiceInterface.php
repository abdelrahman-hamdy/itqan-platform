<?php

namespace App\Contracts;

use App\Models\BaseSubscription;
use Illuminate\Database\Eloquent\Collection;

/**
 * Subscription Renewal Service Interface
 *
 * Defines the contract for subscription renewal operations including:
 * - Automatic renewal processing
 * - Renewal reminder notifications
 * - Manual renewal and reactivation
 * - Renewal statistics and reporting
 *
 * Design Decisions:
 * - NO grace period: Payment failure immediately stops subscription
 * - Auto-renew default: Enabled by default with opt-out option
 * - Reminders: Sent 7 days and 3 days before renewal
 */
interface SubscriptionRenewalServiceInterface
{
    /**
     * Process all subscriptions due for renewal
     *
     * Called by scheduled command (daily). Processes automatic renewals for all
     * subscriptions that are due within the next 24 hours.
     *
     * @return array Results array with keys: processed, successful, failed, skipped, errors
     */
    public function processAllDueRenewals(): array;

    /**
     * Process renewal for a single subscription
     *
     * Attempts to process an automatic renewal for the given subscription.
     * Uses the HandlesSubscriptionRenewal trait method if available.
     *
     * @param  BaseSubscription  $subscription  The subscription to renew
     * @return bool True if renewal was successful, false otherwise
     */
    public function processRenewal(BaseSubscription $subscription): bool;

    /**
     * Send renewal reminders for upcoming renewals
     *
     * Sends reminders at 7 days and 3 days before renewal date.
     * Called by scheduled command (daily).
     *
     * @return array Results array with keys: sent, skipped, errors
     */
    public function sendRenewalReminders(): array;

    /**
     * Get all subscriptions due for renewal
     *
     * Returns subscriptions that are:
     * - Active status
     * - Auto-renew enabled
     * - Next billing date within 24 hours
     *
     * @return Collection Collection of QuranSubscription and AcademicSubscription instances
     */
    public function getDueForRenewal(): Collection;

    /**
     * Get subscriptions that failed renewal
     *
     * Returns subscriptions that have:
     * - Expired status
     * - Failed payment status
     * - Updated within the specified number of days
     *
     * @param  int  $academyId  The academy ID to filter by
     * @param  int  $days  Number of days to look back (default: 30)
     * @return Collection Collection of failed subscription renewals
     */
    public function getFailedRenewals(int $academyId, int $days = 30): Collection;

    /**
     * Manually renew a subscription after manual payment
     *
     * Used when a student makes a manual payment to renew their subscription.
     * Updates subscription dates and status accordingly.
     *
     * @param  BaseSubscription  $subscription  The subscription to renew
     * @param  float  $amount  The payment amount received
     * @return BaseSubscription The renewed subscription
     *
     * @throws \App\Exceptions\SubscriptionNotFoundException When subscription cannot be found
     * @throws \Exception When subscription cannot be renewed in current state
     */
    public function manualRenewal(BaseSubscription $subscription, float $amount): BaseSubscription;

    /**
     * Reactivate an expired subscription with new payment
     *
     * Reactivates a previously expired subscription by resetting dates and status.
     * Only works with subscriptions in expired status.
     *
     * @param  BaseSubscription  $subscription  The expired subscription to reactivate
     * @param  float  $amount  The payment amount received
     * @return BaseSubscription The reactivated subscription
     *
     * @throws \App\Exceptions\SubscriptionNotFoundException When subscription cannot be found
     * @throws \Exception When subscription is not in expired status
     */
    public function reactivate(BaseSubscription $subscription, float $amount): BaseSubscription;

    /**
     * Get renewal statistics for reporting
     *
     * Returns comprehensive statistics including:
     * - Total renewals (successful + failed)
     * - Total revenue from renewals
     * - Upcoming renewals in next 7 days
     * - Breakdown by subscription type (Quran/Academic)
     *
     * @param  int  $academyId  The academy ID to get statistics for
     * @param  int  $days  Number of days to analyze (default: 30)
     * @return array Statistics array with detailed renewal metrics
     */
    public function getRenewalStatistics(int $academyId, int $days = 30): array;
}
