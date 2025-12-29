<?php

namespace App\Services;

use App\Contracts\SubscriptionRenewalServiceInterface;
use App\Models\BaseSubscription;
use App\Services\Subscription\RenewalNotificationService;
use App\Services\Subscription\RenewalProcessor;
use App\Services\Subscription\RenewalReminderService;
use App\Services\Subscription\RenewalStatisticsService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionRenewalService
 *
 * Facade service that coordinates subscription renewal operations across specialized services.
 *
 * ARCHITECTURE:
 * This service acts as a facade, delegating to focused services:
 * - RenewalProcessor: Core renewal processing logic
 * - RenewalNotificationService: All notification logic
 * - RenewalReminderService: Reminder scheduling and delivery
 * - RenewalStatisticsService: Statistics and reporting
 *
 * DESIGN DECISIONS:
 * - Maintains backward compatibility with existing interface
 * - Delegates all operations to specialized services
 * - Coordinates batch operations across multiple subscriptions
 * - NO grace period: Payment failure immediately stops subscription
 *
 * USAGE:
 * - Called by scheduled Artisan commands (daily)
 * - Can be triggered manually for specific subscriptions
 */
class SubscriptionRenewalService implements SubscriptionRenewalServiceInterface
{
    public function __construct(
        private RenewalProcessor $renewalProcessor,
        private RenewalReminderService $reminderService,
        private RenewalStatisticsService $statisticsService,
        private RenewalNotificationService $notificationService
    ) {}

    /**
     * Process all subscriptions due for renewal
     *
     * Called by scheduled command (daily)
     */
    public function processAllDueRenewals(): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $dueSubscriptions = $this->statisticsService->getDueForRenewal();

        Log::info("Processing {$dueSubscriptions->count()} subscriptions due for renewal");

        foreach ($dueSubscriptions as $subscription) {
            try {
                $results['processed']++;

                $success = $this->renewalProcessor->processRenewal($subscription);

                if ($success) {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'subscription_code' => $subscription->subscription_code,
                    'error' => 'Database error: '.$e->getMessage(),
                    'type' => 'database',
                ];

                Log::error("Database error processing renewal for subscription {$subscription->id}", [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
            } catch (\InvalidArgumentException $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'subscription_code' => $subscription->subscription_code,
                    'error' => 'Invalid data: '.$e->getMessage(),
                    'type' => 'validation',
                ];

                Log::error("Invalid data processing renewal for subscription {$subscription->id}", [
                    'error' => $e->getMessage(),
                ]);
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'subscription_code' => $subscription->subscription_code,
                    'error' => $e->getMessage(),
                    'type' => 'unexpected',
                ];

                Log::critical("Unexpected error processing renewal for subscription {$subscription->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                report($e);
            }
        }

        Log::info('Renewal processing completed', $results);

        return $results;
    }

    /**
     * Process renewal for a single subscription
     *
     * Delegates to RenewalProcessor
     */
    public function processRenewal(BaseSubscription $subscription): bool
    {
        return $this->renewalProcessor->processRenewal($subscription);
    }

    /**
     * Send renewal reminders for upcoming renewals
     *
     * Delegates to RenewalReminderService
     */
    public function sendRenewalReminders(): array
    {
        return $this->reminderService->sendRenewalReminders();
    }

    /**
     * Get all subscriptions due for renewal
     *
     * Delegates to RenewalStatisticsService
     */
    public function getDueForRenewal(): Collection
    {
        return $this->statisticsService->getDueForRenewal();
    }

    /**
     * Get subscriptions that failed renewal
     *
     * Delegates to RenewalStatisticsService
     */
    public function getFailedRenewals(int $academyId, int $days = 30): Collection
    {
        return $this->statisticsService->getFailedRenewals($academyId, $days);
    }

    /**
     * Manually renew a subscription (after manual payment)
     *
     * Delegates to RenewalProcessor
     *
     * @throws \App\Exceptions\SubscriptionNotFoundException When the subscription cannot be found
     */
    public function manualRenewal(BaseSubscription $subscription, float $amount): BaseSubscription
    {
        return $this->renewalProcessor->manualRenewal($subscription, $amount);
    }

    /**
     * Reactivate an expired subscription with new payment
     *
     * Delegates to RenewalProcessor
     *
     * @throws \App\Exceptions\SubscriptionNotFoundException When the subscription cannot be found
     */
    public function reactivate(BaseSubscription $subscription, float $amount): BaseSubscription
    {
        return $this->renewalProcessor->reactivate($subscription, $amount);
    }

    /**
     * Get renewal statistics for reporting
     *
     * Delegates to RenewalStatisticsService
     */
    public function getRenewalStatistics(int $academyId, int $days = 30): array
    {
        return $this->statisticsService->getRenewalStatistics($academyId, $days);
    }
}
