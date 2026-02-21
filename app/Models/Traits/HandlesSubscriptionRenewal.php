<?php

namespace App\Models\Traits;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * HandlesSubscriptionRenewal Trait
 *
 * Provides auto-renewal functionality for all subscription types.
 * Used by QuranSubscription, AcademicSubscription, and CourseSubscription.
 *
 * DESIGN PATTERN:
 * - Template Method Pattern: Defines algorithm structure, child classes implement specifics
 * - Transaction Safety: Uses DB transactions with row locking to prevent race conditions
 * - Retry with Grace: Up to 3 renewal attempts before cancellation if subscription hasn't expired
 *
 * RENEWAL FAILURE HANDLING:
 * - Tracks consecutive failures via metadata JSON column (renewal_failed_count)
 * - If ends_at is still in the future and fewer than 3 failures: log failure, keep subscription active
 * - After 3 consecutive failures OR if subscription has already expired: cancel immediately
 * - Successful renewal resets the failure counter
 *
 * RESPONSIBILITIES:
 * - Attempting automatic renewal with payment processing
 * - Handling payment failures with graduated retry logic
 * - Sending renewal success/failure notifications
 * - Calculating next billing dates
 *
 * CHILD CLASS REQUIREMENTS:
 * - Must extend BaseSubscription
 * - Must implement calculateRenewalPrice() method
 * - Must have billing_cycle, auto_renew, next_billing_date fields
 *
 * TRANSACTION SAFETY:
 * 1. Starts DB transaction
 * 2. Locks subscription row with lockForUpdate()
 * 3. Checks renewal eligibility
 * 4. Processes payment
 * 5. Updates subscription or handles failure
 * 6. Commits or rolls back on error
 *
 * USAGE:
 * ```php
 * class QuranSubscription extends BaseSubscription
 * {
 *     use HandlesSubscriptionRenewal;
 *
 *     public function calculateRenewalPrice(): float {
 *         return $this->getPriceForBillingCycle();
 *     }
 * }
 * ```
 *
 * @see QuranSubscription Example implementation
 * @see AcademicSubscription Example implementation
 */
trait HandlesSubscriptionRenewal
{
    /**
     * Attempt automatic renewal with payment processing
     *
     * This is the main renewal method that:
     * 1. Validates renewal eligibility
     * 2. Processes payment through PaymentService
     * 3. On success: extends subscription and resets failure counter
     * 4. On failure: tracks attempt count, cancels after 3 consecutive failures
     *
     * @return bool True if renewal succeeded, false otherwise
     */
    public function attemptAutoRenewal(): bool
    {
        // Check basic eligibility
        if (! $this->canAttemptRenewal()) {
            Log::info("Subscription {$this->id} not eligible for auto-renewal", [
                'auto_renew' => $this->auto_renew,
                'status' => $this->status?->value,
                'billing_cycle' => $this->billing_cycle?->value,
            ]);

            return false;
        }

        return DB::transaction(function () {
            // Lock subscription row to prevent concurrent renewal attempts
            $subscription = static::lockForUpdate()->find($this->id);

            if (! $subscription) {
                throw new Exception("Subscription {$this->id} not found");
            }

            // Double-check eligibility after lock (another process might have renewed)
            if (! $subscription->canAttemptRenewal()) {
                return false;
            }

            try {
                // Calculate renewal price from child class
                $renewalPrice = $subscription->calculateRenewalPrice();

                Log::info("Attempting auto-renewal for subscription {$subscription->id}", [
                    'subscription_code' => $subscription->subscription_code,
                    'renewal_price' => $renewalPrice,
                    'billing_cycle' => $subscription->billing_cycle?->value,
                ]);

                // Process payment via PaymentService
                $paymentService = app(PaymentService::class);
                $paymentResult = $paymentService->processSubscriptionRenewal(
                    $subscription,
                    $renewalPrice
                );

                if ($paymentResult['success'] ?? false) {
                    // Success: Extend subscription
                    $subscription->processSuccessfulRenewal($renewalPrice);

                    Log::info("Auto-renewal successful for subscription {$subscription->id}");

                    return true;
                } else {
                    // Failure: Track attempt and cancel after MAX_RENEWAL_ATTEMPTS
                    $subscription->processRenewalFailure($paymentResult['error'] ?? __('payments.renewal.payment_failed'));

                    Log::warning("Auto-renewal failed for subscription {$subscription->id}", [
                        'error' => $paymentResult['error'] ?? 'Unknown error',
                    ]);

                    return false;
                }
            } catch (Exception $e) {
                // Exception during payment: Track as failed attempt
                $subscription->processRenewalFailure($e->getMessage());

                Log::error("Exception during auto-renewal for subscription {$subscription->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return false;
            }
        });
    }

    /**
     * Check if subscription is eligible for auto-renewal attempt
     */
    public function canAttemptRenewal(): bool
    {
        // Must have auto-renew enabled
        if (! $this->auto_renew) {
            return false;
        }

        // Must be active status
        if ($this->status !== SessionSubscriptionStatus::ACTIVE) {
            return false;
        }

        // Billing cycle must support auto-renewal
        if (! $this->billing_cycle?->supportsAutoRenewal()) {
            return false;
        }

        return true;
    }

    /**
     * Process a successful renewal payment
     *
     * Updates subscription with new dates, resets renewal reminder,
     * and clears any renewal failure tracking from metadata.
     */
    protected function processSuccessfulRenewal(float $amount): void
    {
        $newBillingDate = $this->calculateNextBillingDate();

        // Ensure billing_cycle exists before calculating end date
        if (! $this->billing_cycle) {
            throw new RuntimeException('Cannot process renewal: billing cycle is not set for subscription #'.$this->id);
        }

        // Use ends_at directly — it always represents the paid-for period end (never modified by grace extensions)
        $metadata = $this->metadata ?? [];
        $baseDate = $this->ends_at ?? now();

        $newEndDate = $this->billing_cycle->calculateEndDate($baseDate);

        // Reset renewal failure tracking and clear grace period data on successful renewal
        unset(
            $metadata['renewal_failed_count'],
            $metadata['last_renewal_failure_at'],
            $metadata['last_renewal_failure_reason'],
            $metadata['grace_period_ends_at'],
            $metadata['grace_period_expires_at'],  // Legacy key cleanup
            $metadata['grace_period_started_at'],
            $metadata['grace_notification_last_sent_at'],
            $metadata['original_ends_at']
        );

        $this->update([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'last_payment_date' => now(),
            'next_billing_date' => $newBillingDate,
            'ends_at' => $newEndDate,
            'renewal_reminder_sent_at' => null, // Reset for next cycle
            'metadata' => $metadata ?: null,
        ]);

        // Extend sessions if applicable (for session-based subscriptions)
        $this->extendSessionsOnRenewal();

        // Send success notification
        $this->sendRenewalSuccessNotification($amount);

        // Refresh instance
        $this->refresh();
    }

    /**
     * Maximum number of consecutive renewal failures before cancellation
     */
    protected const MAX_RENEWAL_ATTEMPTS = 3;

    /**
     * Process a renewal failure with graduated retry logic
     *
     * Instead of immediately cancelling on the first failure, this method:
     * 1. Tracks consecutive failures in the metadata JSON column
     * 2. If the subscription hasn't expired (ends_at is in the future) AND
     *    fewer than MAX_RENEWAL_ATTEMPTS failures have occurred: keeps the
     *    subscription active and logs the failure for retry
     * 3. On the Nth consecutive failure (or if already expired): cancels the subscription
     *
     * This prevents a single network glitch from permanently cancelling a subscription.
     */
    protected function processRenewalFailure(string $reason): void
    {
        $metadata = $this->metadata ?? [];
        $failedCount = ($metadata['renewal_failed_count'] ?? 0) + 1;
        $metadata['renewal_failed_count'] = $failedCount;
        $metadata['last_renewal_failure_at'] = now()->toIso8601String();
        $metadata['last_renewal_failure_reason'] = $reason;

        $subscriptionStillValid = $this->ends_at && $this->ends_at->isFuture();
        $withinRetryLimit = $failedCount < self::MAX_RENEWAL_ATTEMPTS;

        if ($subscriptionStillValid && $withinRetryLimit) {
            // Grace period: keep subscription active, just record the failure
            $this->update([
                'payment_status' => SubscriptionPaymentStatus::FAILED,
                'metadata' => $metadata,
            ]);

            Log::warning("Renewal attempt {$failedCount}/".self::MAX_RENEWAL_ATTEMPTS." failed for subscription {$this->id} - will retry", [
                'reason' => $reason,
                'ends_at' => $this->ends_at->toDateString(),
                'failed_count' => $failedCount,
            ]);

            // Send failure notification so the student is aware
            $this->sendPaymentFailedNotification($reason);

            // Refresh instance
            $this->refresh();

            return;
        }

        // Enter grace period instead of immediate cancellation
        $gracePeriodDays = config('payments.renewal.grace_period_days', 3);
        $metadata['grace_period_ends_at'] = now()->addDays($gracePeriodDays)->toIso8601String();
        $metadata['grace_period_started_at'] = now()->toIso8601String();

        $this->update([
            'status' => SessionSubscriptionStatus::ACTIVE, // Keep active during grace period
            'payment_status' => SubscriptionPaymentStatus::FAILED,
            'metadata' => $metadata,
        ]);

        Log::warning("Subscription {$this->id} entered grace period after {$failedCount} failed renewal attempts", [
            'reason' => $reason,
            'grace_period_days' => $gracePeriodDays,
            'grace_period_ends_at' => now()->addDays($gracePeriodDays)->toDateString(),
        ]);

        // Send failure notification
        $this->sendPaymentFailedNotification($reason);

        // Refresh instance
        $this->refresh();
    }

    /**
     * Manually renew subscription with new payment
     *
     * Used when student manually renews after expiration
     */
    public function manualRenewal(float $amount, ?string $newBillingCycle = null): bool
    {
        if (! $this->canRenew()) {
            throw new Exception('Cannot renew subscription in current state');
        }

        return DB::transaction(function () use ($amount, $newBillingCycle) {
            $subscription = static::lockForUpdate()->find($this->id);

            // Update billing cycle if provided
            if ($newBillingCycle) {
                $subscription->billing_cycle = $newBillingCycle;
            }

            $subscription->processSuccessfulRenewal($amount);

            return true;
        });
    }

    /**
     * Get display name for the subscription (for notifications)
     */
    protected function getSubscriptionDisplayName(): string
    {
        // Try to use snapshot data first
        if (! empty($this->package_name_ar)) {
            return $this->package_name_ar;
        }

        // Fallback to package relationship
        if ($this->package && method_exists($this->package, 'name')) {
            return $this->package->name ?? 'اشتراك';
        }

        // Default based on type
        return match ($this->getSubscriptionType()) {
            'quran' => 'اشتراك القرآن',
            'academic' => 'اشتراك أكاديمي',
            'course' => 'اشتراك الدورة',
            default => 'اشتراك',
        };
    }

    /**
     * Get URL for the subscription (for notifications)
     */
    protected function getSubscriptionUrl(): string
    {
        $type = $this->getSubscriptionType();

        return match ($type) {
            'quran' => route('student.subscriptions.quran.show', ['subscription' => $this->id]),
            'academic' => route('student.subscriptions.academic.show', ['subscription' => $this->id]),
            'course' => route('student.subscriptions.course.show', ['subscription' => $this->id]),
            default => route('student.profile'),
        };
    }

    /**
     * Extend sessions on renewal (for session-based subscriptions)
     *
     * Default implementation does nothing.
     * Session-based subscriptions (Quran, Academic) should override this.
     */
    protected function extendSessionsOnRenewal(): void
    {
        // Default: no-op
        // Override in child classes like QuranSubscription, AcademicSubscription
    }

    /**
     * Send renewal success notification to student
     */
    protected function sendRenewalSuccessNotification(float $amount): void
    {
        // Get student
        $student = $this->student;
        if (! $student) {
            return;
        }

        try {
            $notificationService = app(NotificationService::class);

            $subscriptionData = [
                'subscription_id' => $this->id,
                'name' => $this->getSubscriptionDisplayName(),
                'amount' => $amount,
                'currency' => $this->currency ?? config('currencies.default', 'SAR'),
                'next_billing_date' => $this->next_billing_date?->format('Y-m-d'),
                'subscription_type' => $this->getSubscriptionType(),
                'url' => $this->getSubscriptionUrl(),
            ];

            $notificationService->sendSubscriptionRenewedNotification($student, $subscriptionData);

            Log::info("Renewal success notification sent to student {$student->id}", [
                'subscription_id' => $this->id,
                'amount' => $amount,
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to send renewal success notification', [
                'subscription_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send payment failed notification to student
     */
    protected function sendPaymentFailedNotification(string $reason): void
    {
        // Get student
        $student = $this->student;
        if (! $student) {
            return;
        }

        try {
            $notificationService = app(NotificationService::class);

            $paymentData = [
                'subscription_id' => $this->id,
                'subscription_name' => $this->getSubscriptionDisplayName(),
                'subscription_type' => $this->getSubscriptionType(),
                'amount' => $this->calculateRenewalPrice(),
                'currency' => $this->currency ?? config('currencies.default', 'SAR'),
                'reason' => $reason,
                'url' => $this->getSubscriptionUrl(),
            ];

            $notificationService->sendPaymentFailedNotification($student, $paymentData);

            Log::info("Payment failed notification sent to student {$student->id}", [
                'subscription_id' => $this->id,
                'reason' => $reason,
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to send payment failed notification', [
                'subscription_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send renewal reminder notification
     *
     * Called by scheduled job before renewal date
     */
    public function sendRenewalReminder(int $daysUntilRenewal): void
    {
        $student = $this->student;
        if (! $student) {
            return;
        }

        try {
            $notificationService = app(NotificationService::class);

            $subscriptionData = [
                'subscription_id' => $this->id,
                'name' => $this->getSubscriptionDisplayName(),
                'subscription_type' => $this->getSubscriptionType(),
                'expiry_date' => $this->ends_at?->format('Y-m-d'),
                'days_remaining' => $daysUntilRenewal,
                'renewal_amount' => $this->calculateRenewalPrice(),
                'currency' => $this->currency ?? config('currencies.default', 'SAR'),
                'url' => $this->getSubscriptionUrl(),
            ];

            $notificationService->sendSubscriptionExpiringNotification($student, $subscriptionData);

            Log::info("Renewal reminder sent to student {$student->id}", [
                'subscription_id' => $this->id,
                'days_until_renewal' => $daysUntilRenewal,
                'renewal_amount' => $this->calculateRenewalPrice(),
            ]);

            // Mark reminder as sent
            $this->update(['renewal_reminder_sent_at' => now()]);
        } catch (Exception $e) {
            Log::warning('Failed to send renewal reminder', [
                'subscription_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
