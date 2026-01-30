<?php

namespace App\Models\Traits;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * HandlesSubscriptionRenewal Trait
 *
 * Provides auto-renewal functionality for all subscription types.
 * Used by QuranSubscription, AcademicSubscription, and CourseSubscription.
 *
 * DESIGN PATTERN:
 * - Template Method Pattern: Defines algorithm structure, child classes implement specifics
 * - Transaction Safety: Uses DB transactions with row locking to prevent race conditions
 * - No Grace Period: Payment failure immediately stops subscription per requirement
 *
 * RESPONSIBILITIES:
 * - Attempting automatic renewal with payment processing
 * - Handling payment failures (immediate subscription stop)
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
     * 3. On success: extends subscription
     * 4. On failure: immediately stops subscription (no grace period)
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
                throw new \Exception("Subscription {$this->id} not found");
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
                    // Failure: Immediately stop subscription (NO GRACE PERIOD)
                    $subscription->processRenewalFailure($paymentResult['error'] ?? 'فشل الدفع');

                    Log::warning("Auto-renewal failed for subscription {$subscription->id}", [
                        'error' => $paymentResult['error'] ?? 'Unknown error',
                    ]);

                    return false;
                }
            } catch (\Exception $e) {
                // Exception during payment: Stop subscription immediately
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
     * Updates subscription with new dates and resets renewal reminder
     */
    protected function processSuccessfulRenewal(float $amount): void
    {
        $newBillingDate = $this->calculateNextBillingDate();

        // Ensure billing_cycle exists before calculating end date
        if (! $this->billing_cycle) {
            throw new \RuntimeException('Cannot process renewal: billing cycle is not set for subscription #'.$this->id);
        }

        $newEndDate = $this->billing_cycle->calculateEndDate($this->ends_at ?? now());

        $this->update([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'last_payment_date' => now(),
            'next_billing_date' => $newBillingDate,
            'ends_at' => $newEndDate,
            'renewal_reminder_sent_at' => null, // Reset for next cycle
        ]);

        // Extend sessions if applicable (for session-based subscriptions)
        $this->extendSessionsOnRenewal();

        // Send success notification
        $this->sendRenewalSuccessNotification($amount);

        // Refresh instance
        $this->refresh();
    }

    /**
     * Process a renewal failure - IMMEDIATELY STOP SUBSCRIPTION
     *
     * Per requirement: NO grace period. Subscription stops immediately on payment failure.
     */
    protected function processRenewalFailure(string $reason): void
    {
        $this->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
            'payment_status' => SubscriptionPaymentStatus::FAILED,
            'auto_renew' => false, // Disable auto-renew after failure
            'cancellation_reason' => 'فشل الدفع التلقائي: '.$reason,
            'cancelled_at' => now(),
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
            throw new \Exception('Cannot renew subscription in current state');
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
            $notificationService = app(\App\Services\NotificationService::class);

            $subscriptionData = [
                'subscription_id' => $this->id,
                'name' => $this->getSubscriptionDisplayName(),
                'amount' => $amount,
                'currency' => $this->currency ?? 'SAR',
                'next_billing_date' => $this->next_billing_date?->format('Y-m-d'),
                'subscription_type' => $this->getSubscriptionType(),
                'url' => $this->getSubscriptionUrl(),
            ];

            $notificationService->sendSubscriptionRenewedNotification($student, $subscriptionData);

            Log::info("Renewal success notification sent to student {$student->id}", [
                'subscription_id' => $this->id,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
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
            $notificationService = app(\App\Services\NotificationService::class);

            $paymentData = [
                'subscription_id' => $this->id,
                'subscription_name' => $this->getSubscriptionDisplayName(),
                'subscription_type' => $this->getSubscriptionType(),
                'amount' => $this->calculateRenewalPrice(),
                'currency' => $this->currency ?? 'SAR',
                'reason' => $reason,
                'url' => $this->getSubscriptionUrl(),
            ];

            $notificationService->sendPaymentFailedNotification($student, $paymentData);

            Log::info("Payment failed notification sent to student {$student->id}", [
                'subscription_id' => $this->id,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
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
            $notificationService = app(\App\Services\NotificationService::class);

            $subscriptionData = [
                'subscription_id' => $this->id,
                'name' => $this->getSubscriptionDisplayName(),
                'subscription_type' => $this->getSubscriptionType(),
                'expiry_date' => $this->ends_at?->format('Y-m-d'),
                'days_remaining' => $daysUntilRenewal,
                'renewal_amount' => $this->calculateRenewalPrice(),
                'currency' => $this->currency ?? 'SAR',
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
        } catch (\Exception $e) {
            Log::warning('Failed to send renewal reminder', [
                'subscription_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
