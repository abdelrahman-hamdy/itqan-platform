<?php

namespace App\Services;

use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\QuranSubscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionRenewalService
 *
 * Handles automatic subscription renewals and renewal reminders.
 *
 * KEY RESPONSIBILITIES:
 * - Processing automatic renewals for due subscriptions
 * - Sending renewal reminder notifications
 * - Handling payment failures (NO grace period)
 * - Generating renewal reports
 *
 * DESIGN DECISIONS:
 * - NO grace period: Payment failure immediately stops subscription
 * - Auto-renew default: Enabled by default with opt-out option
 * - Reminders: Sent 7 days and 3 days before renewal
 * - Only Quran and Academic subscriptions support auto-renewal
 *   (Course subscriptions are one-time purchases)
 *
 * USAGE:
 * - Called by scheduled Artisan commands (daily)
 * - Can be triggered manually for specific subscriptions
 */
class SubscriptionRenewalService
{
    protected PaymentService $paymentService;
    protected NotificationService $notificationService;

    public function __construct(
        PaymentService $paymentService,
        ?NotificationService $notificationService = null
    ) {
        $this->paymentService = $paymentService;
        // NotificationService is optional for now
        $this->notificationService = $notificationService ?? app(NotificationService::class);
    }

    // ========================================
    // AUTOMATIC RENEWAL PROCESSING
    // ========================================

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

        // Get all subscriptions due for renewal
        $dueSubscriptions = $this->getDueForRenewal();

        Log::info("Processing {$dueSubscriptions->count()} subscriptions due for renewal");

        foreach ($dueSubscriptions as $subscription) {
            try {
                $results['processed']++;

                $success = $this->processRenewal($subscription);

                if ($success) {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'subscription_code' => $subscription->subscription_code,
                    'error' => $e->getMessage(),
                ];

                Log::error("Error processing renewal for subscription {$subscription->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info("Renewal processing completed", $results);

        return $results;
    }

    /**
     * Process renewal for a single subscription
     *
     * Uses the HandlesSubscriptionRenewal trait method
     */
    public function processRenewal(BaseSubscription $subscription): bool
    {
        // Verify subscription can be renewed
        if (!$this->canProcessRenewal($subscription)) {
            Log::info("Subscription {$subscription->id} skipped - not eligible for renewal");
            return false;
        }

        // Use the trait method for actual renewal processing
        if (method_exists($subscription, 'attemptAutoRenewal')) {
            return $subscription->attemptAutoRenewal();
        }

        // Fallback manual processing
        return $this->processRenewalManually($subscription);
    }

    /**
     * Manual renewal processing (fallback)
     */
    protected function processRenewalManually(BaseSubscription $subscription): bool
    {
        return DB::transaction(function () use ($subscription) {
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            if (!$subscription || !$this->canProcessRenewal($subscription)) {
                return false;
            }

            try {
                $renewalPrice = $subscription->calculateRenewalPrice();

                // Process payment
                $paymentResult = $this->paymentService->processSubscriptionRenewal(
                    $subscription,
                    $renewalPrice
                );

                if ($paymentResult['success'] ?? false) {
                    $this->handleSuccessfulRenewal($subscription, $renewalPrice);
                    return true;
                } else {
                    // NO GRACE PERIOD - Stop immediately
                    $this->handleFailedRenewal($subscription, $paymentResult['error'] ?? 'فشل الدفع');
                    return false;
                }
            } catch (\Exception $e) {
                $this->handleFailedRenewal($subscription, $e->getMessage());
                return false;
            }
        });
    }

    /**
     * Check if subscription can be processed for renewal
     */
    protected function canProcessRenewal(BaseSubscription $subscription): bool
    {
        // Must be active
        if ($subscription->status !== SubscriptionStatus::ACTIVE) {
            return false;
        }

        // Must have auto-renew enabled
        if (!$subscription->auto_renew) {
            return false;
        }

        // Must have a billing cycle that supports auto-renewal
        if (!$subscription->billing_cycle?->supportsAutoRenewal()) {
            return false;
        }

        // Must be due for renewal (within 3 days)
        if ($subscription->next_billing_date && $subscription->next_billing_date->isAfter(now()->addDays(3))) {
            return false;
        }

        return true;
    }

    /**
     * Handle successful renewal
     */
    protected function handleSuccessfulRenewal(BaseSubscription $subscription, float $amount): void
    {
        $newBillingDate = $subscription->calculateNextBillingDate();
        $newEndDate = $subscription->billing_cycle->calculateEndDate($subscription->ends_at ?? now());

        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'last_payment_date' => now(),
            'next_billing_date' => $newBillingDate,
            'ends_at' => $newEndDate,
            'renewal_reminder_sent_at' => null,
        ]);

        // Extend sessions if applicable
        if (method_exists($subscription, 'extendSessionsOnRenewal')) {
            $subscription->extendSessionsOnRenewal();
        }

        // Send success notification
        $this->sendRenewalSuccessNotification($subscription, $amount);

        Log::info("Subscription {$subscription->id} renewed successfully", [
            'amount' => $amount,
            'new_billing_date' => $newBillingDate->toDateString(),
        ]);
    }

    /**
     * Handle failed renewal - NO GRACE PERIOD
     */
    protected function handleFailedRenewal(BaseSubscription $subscription, string $reason): void
    {
        $subscription->update([
            'status' => SubscriptionStatus::EXPIRED,
            'payment_status' => SubscriptionPaymentStatus::FAILED,
            'auto_renew' => false, // Disable auto-renew after failure
            'cancellation_reason' => 'فشل الدفع التلقائي: ' . $reason,
        ]);

        // Send failure notification
        $this->sendPaymentFailedNotification($subscription, $reason);

        Log::warning("Subscription {$subscription->id} renewal failed - subscription stopped", [
            'reason' => $reason,
        ]);
    }

    // ========================================
    // RENEWAL REMINDERS
    // ========================================

    /**
     * Send renewal reminders for upcoming renewals
     *
     * Sends reminders at 7 days and 3 days before renewal
     */
    public function sendRenewalReminders(): array
    {
        $results = [
            'sent' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        // Get subscriptions needing 7-day reminder
        $sevenDayReminders = $this->getSubscriptionsNeedingReminder(7);
        foreach ($sevenDayReminders as $subscription) {
            try {
                $this->sendRenewalReminderNotification($subscription, 7);
                $subscription->update(['renewal_reminder_sent_at' => now()]);
                $results['sent']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'days' => 7,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Get subscriptions needing 3-day reminder
        $threeDayReminders = $this->getSubscriptionsNeedingReminder(3);
        foreach ($threeDayReminders as $subscription) {
            try {
                $this->sendRenewalReminderNotification($subscription, 3);
                $results['sent']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'days' => 3,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info("Renewal reminders sent", $results);

        return $results;
    }

    /**
     * Get subscriptions needing reminder at N days before renewal
     */
    protected function getSubscriptionsNeedingReminder(int $daysBeforeRenewal): Collection
    {
        $targetDate = now()->addDays($daysBeforeRenewal)->toDateString();

        $subscriptions = collect();

        // Quran subscriptions
        $subscriptions = $subscriptions->merge(
            QuranSubscription::where('auto_renew', true)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->whereDate('next_billing_date', $targetDate)
                ->when($daysBeforeRenewal === 7, function ($query) {
                    $query->whereNull('renewal_reminder_sent_at');
                })
                ->get()
        );

        // Academic subscriptions
        $subscriptions = $subscriptions->merge(
            AcademicSubscription::where('auto_renew', true)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->whereDate('next_billing_date', $targetDate)
                ->when($daysBeforeRenewal === 7, function ($query) {
                    $query->whereNull('renewal_reminder_sent_at');
                })
                ->get()
        );

        return $subscriptions;
    }

    // ========================================
    // SUBSCRIPTION QUERIES
    // ========================================

    /**
     * Get all subscriptions due for renewal
     */
    public function getDueForRenewal(): Collection
    {
        $subscriptions = collect();

        // Quran subscriptions due for renewal
        $subscriptions = $subscriptions->merge(
            QuranSubscription::where('auto_renew', true)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->where('next_billing_date', '<=', now()->addDays(1))
                ->get()
        );

        // Academic subscriptions due for renewal
        $subscriptions = $subscriptions->merge(
            AcademicSubscription::where('auto_renew', true)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->where('next_billing_date', '<=', now()->addDays(1))
                ->get()
        );

        return $subscriptions;
    }

    /**
     * Get subscriptions that failed renewal
     */
    public function getFailedRenewals(int $academyId, int $days = 30): Collection
    {
        $since = now()->subDays($days);

        $subscriptions = collect();

        $subscriptions = $subscriptions->merge(
            QuranSubscription::where('academy_id', $academyId)
                ->where('status', SubscriptionStatus::EXPIRED)
                ->where('payment_status', SubscriptionPaymentStatus::FAILED)
                ->where('updated_at', '>=', $since)
                ->get()
        );

        $subscriptions = $subscriptions->merge(
            AcademicSubscription::where('academy_id', $academyId)
                ->where('status', SubscriptionStatus::EXPIRED)
                ->where('payment_status', SubscriptionPaymentStatus::FAILED)
                ->where('updated_at', '>=', $since)
                ->get()
        );

        return $subscriptions->sortByDesc('updated_at')->values();
    }

    // ========================================
    // NOTIFICATIONS
    // ========================================

    /**
     * Send renewal reminder notification
     */
    protected function sendRenewalReminderNotification(BaseSubscription $subscription, int $daysUntilRenewal): void
    {
        $student = $subscription->student;
        if (!$student) {
            return;
        }

        try {
            // Use trait method if available
            if (method_exists($subscription, 'sendRenewalReminder')) {
                $subscription->sendRenewalReminder($daysUntilRenewal);
                return;
            }

            // Send via NotificationService
            $this->notificationService->sendSubscriptionExpiringNotification($student, [
                'subscription_id' => $subscription->id,
                'subscription_type' => class_basename($subscription),
                'name' => $subscription->subscription_code ?? class_basename($subscription),
                'expiry_date' => $subscription->next_billing_date?->format('Y-m-d') ?? '',
                'days_remaining' => $daysUntilRenewal,
                'renewal_amount' => $subscription->calculateRenewalPrice(),
                'currency' => 'SAR',
                'url' => '/subscriptions',
            ]);

            Log::info("Renewal reminder sent", [
                'subscription_id' => $subscription->id,
                'student_id' => $student->id,
                'days_until_renewal' => $daysUntilRenewal,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to send renewal reminder", [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send renewal success notification
     */
    protected function sendRenewalSuccessNotification(BaseSubscription $subscription, float $amount): void
    {
        $student = $subscription->student;
        if (!$student) {
            return;
        }

        try {
            // Send via NotificationService
            $this->notificationService->sendSubscriptionRenewedNotification($student, [
                'subscription_id' => $subscription->id,
                'subscription_type' => class_basename($subscription),
                'name' => $subscription->subscription_code ?? class_basename($subscription),
                'amount' => $amount,
                'currency' => 'SAR',
                'next_billing_date' => $subscription->next_billing_date?->format('Y-m-d') ?? '',
                'url' => '/subscriptions',
            ]);

            Log::info("Renewal success notification sent", [
                'subscription_id' => $subscription->id,
                'student_id' => $student->id,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to send renewal success notification", [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send payment failed notification
     */
    protected function sendPaymentFailedNotification(BaseSubscription $subscription, string $reason): void
    {
        $student = $subscription->student;
        if (!$student) {
            return;
        }

        try {
            // Send via NotificationService
            $this->notificationService->sendPaymentFailedNotification($student, [
                'subscription_id' => $subscription->id,
                'subscription_type' => class_basename($subscription),
                'subscription_name' => $subscription->subscription_code ?? class_basename($subscription),
                'amount' => $subscription->final_price ?? 0,
                'currency' => 'SAR',
                'reason' => $reason,
                'url' => '/subscriptions',
            ]);

            Log::info("Payment failed notification sent", [
                'subscription_id' => $subscription->id,
                'student_id' => $student->id,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to send payment failed notification", [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ========================================
    // MANUAL RENEWAL
    // ========================================

    /**
     * Manually renew a subscription (after manual payment)
     */
    public function manualRenewal(BaseSubscription $subscription, float $amount): BaseSubscription
    {
        return DB::transaction(function () use ($subscription, $amount) {
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            if (!$subscription->canRenew()) {
                throw new \Exception('Subscription cannot be renewed in current state');
            }

            $this->handleSuccessfulRenewal($subscription, $amount);

            Log::info("Manual renewal processed for subscription {$subscription->id}", [
                'amount' => $amount,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Reactivate an expired subscription with new payment
     */
    public function reactivate(BaseSubscription $subscription, float $amount): BaseSubscription
    {
        return DB::transaction(function () use ($subscription, $amount) {
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            if (!$subscription->isExpired()) {
                throw new \Exception('Only expired subscriptions can be reactivated');
            }

            $subscription->update([
                'status' => SubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'starts_at' => now(),
                'ends_at' => $subscription->billing_cycle->calculateEndDate(now()),
                'next_billing_date' => $subscription->billing_cycle->calculateEndDate(now()),
                'last_payment_date' => now(),
                'final_price' => $amount,
                'auto_renew' => true, // Re-enable auto-renew
                'cancellation_reason' => null,
            ]);

            // Extend sessions if applicable
            if (method_exists($subscription, 'extendSessionsOnRenewal')) {
                $subscription->extendSessionsOnRenewal();
            }

            Log::info("Subscription {$subscription->id} reactivated", [
                'amount' => $amount,
            ]);

            return $subscription->fresh();
        });
    }

    // ========================================
    // REPORTING
    // ========================================

    /**
     * Get renewal statistics for reporting
     */
    public function getRenewalStatistics(int $academyId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $stats = [
            'period_days' => $days,
            'total_renewals' => 0,
            'successful_renewals' => 0,
            'failed_renewals' => 0,
            'total_revenue' => 0,
            'upcoming_renewals' => 0,
            'by_type' => [],
        ];

        foreach (['quran' => QuranSubscription::class, 'academic' => AcademicSubscription::class] as $type => $modelClass) {
            $successful = $modelClass::where('academy_id', $academyId)
                ->where('last_payment_date', '>=', $since)
                ->where('payment_status', SubscriptionPaymentStatus::PAID)
                ->count();

            $failed = $modelClass::where('academy_id', $academyId)
                ->where('updated_at', '>=', $since)
                ->where('status', SubscriptionStatus::EXPIRED)
                ->where('payment_status', SubscriptionPaymentStatus::FAILED)
                ->count();

            $revenue = $modelClass::where('academy_id', $academyId)
                ->where('last_payment_date', '>=', $since)
                ->where('payment_status', SubscriptionPaymentStatus::PAID)
                ->sum('final_price') ?? 0;

            $upcoming = $modelClass::where('academy_id', $academyId)
                ->where('auto_renew', true)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->whereBetween('next_billing_date', [now(), now()->addDays(7)])
                ->count();

            $stats['by_type'][$type] = [
                'successful' => $successful,
                'failed' => $failed,
                'revenue' => $revenue,
                'upcoming' => $upcoming,
            ];

            $stats['successful_renewals'] += $successful;
            $stats['failed_renewals'] += $failed;
            $stats['total_revenue'] += $revenue;
            $stats['upcoming_renewals'] += $upcoming;
        }

        $stats['total_renewals'] = $stats['successful_renewals'] + $stats['failed_renewals'];

        return $stats;
    }
}
