<?php

namespace App\Services\Subscription;

use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Exceptions\SubscriptionNotFoundException;
use App\Models\BaseSubscription;
use App\Models\QuranSubscription;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RenewalProcessor
 *
 * Handles the core subscription renewal processing logic.
 *
 * RESPONSIBILITIES:
 * - Processing automatic renewal for individual subscriptions
 * - Managing renewal transactions
 * - Handling successful and failed renewal outcomes
 * - Verifying renewal eligibility
 *
 * DESIGN DECISIONS:
 * - NO grace period: Payment failure immediately stops subscription
 * - Transaction safety: Uses database locks to prevent race conditions
 * - Delegates to trait methods when available for consistency
 */
class RenewalProcessor
{
    public function __construct(
        private PaymentService $paymentService,
        private RenewalNotificationService $notificationService
    ) {}

    /**
     * Process renewal for a single subscription
     *
     * Uses the HandlesSubscriptionRenewal trait method if available
     */
    public function processRenewal(BaseSubscription $subscription): bool
    {
        if (! $this->canProcessRenewal($subscription)) {
            Log::info("Subscription {$subscription->id} skipped - not eligible for renewal");

            return false;
        }

        if (method_exists($subscription, 'attemptAutoRenewal')) {
            return $subscription->attemptAutoRenewal();
        }

        return $this->processRenewalManually($subscription);
    }

    /**
     * Manual renewal processing (fallback)
     *
     * @throws SubscriptionNotFoundException When the subscription cannot be found
     */
    protected function processRenewalManually(BaseSubscription $subscription): bool
    {
        return DB::transaction(function () use ($subscription) {
            $lockedSubscription = $subscription::lockForUpdate()->find($subscription->id);

            if (! $lockedSubscription) {
                throw SubscriptionNotFoundException::forId(
                    (string) $subscription->id,
                    $subscription instanceof QuranSubscription ? 'quran' : 'academic'
                );
            }

            if (! $this->canProcessRenewal($lockedSubscription)) {
                return false;
            }

            try {
                $renewalPrice = $lockedSubscription->calculateRenewalPrice();

                $paymentResult = $this->paymentService->processSubscriptionRenewal(
                    $lockedSubscription,
                    $renewalPrice
                );

                if ($paymentResult['success'] ?? false) {
                    $this->handleSuccessfulRenewal($lockedSubscription, $renewalPrice);

                    return true;
                } else {
                    $this->handleFailedRenewal($lockedSubscription, $paymentResult['error'] ?? 'فشل الدفع');

                    return false;
                }
            } catch (\Illuminate\Database\QueryException $e) {
                Log::error('Database error during renewal processing', [
                    'subscription_id' => $lockedSubscription->id,
                    'error' => $e->getMessage(),
                ]);
                $this->handleFailedRenewal($lockedSubscription, 'خطأ في قاعدة البيانات');

                return false;
            } catch (\Throwable $e) {
                Log::critical('Unexpected error during renewal processing', [
                    'subscription_id' => $lockedSubscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                report($e);
                $this->handleFailedRenewal($lockedSubscription, 'خطأ غير متوقع');

                return false;
            }
        });
    }

    /**
     * Check if subscription can be processed for renewal
     */
    public function canProcessRenewal(BaseSubscription $subscription): bool
    {
        if ($subscription->status !== SubscriptionStatus::ACTIVE) {
            return false;
        }

        if (! $subscription->auto_renew) {
            return false;
        }

        if (! $subscription->billing_cycle?->supportsAutoRenewal()) {
            return false;
        }

        if ($subscription->next_billing_date && $subscription->next_billing_date->isAfter(now()->addDays(3))) {
            return false;
        }

        return true;
    }

    /**
     * Handle successful renewal
     */
    public function handleSuccessfulRenewal(BaseSubscription $subscription, float $amount): void
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

        $this->notificationService->sendRenewalSuccessNotification($subscription, $amount);

        Log::info("Subscription {$subscription->id} renewed successfully", [
            'amount' => $amount,
            'new_billing_date' => $newBillingDate->toDateString(),
        ]);
    }

    /**
     * Handle failed renewal - NO GRACE PERIOD
     */
    public function handleFailedRenewal(BaseSubscription $subscription, string $reason): void
    {
        $subscription->update([
            'status' => SubscriptionStatus::EXPIRED,
            'payment_status' => SubscriptionPaymentStatus::FAILED,
            'auto_renew' => false,
            'cancellation_reason' => 'فشل الدفع التلقائي: '.$reason,
        ]);

        $this->notificationService->sendPaymentFailedNotification($subscription, $reason);

        Log::warning("Subscription {$subscription->id} renewal failed - subscription stopped", [
            'reason' => $reason,
        ]);
    }

    /**
     * Manually renew a subscription (after manual payment)
     *
     * @throws SubscriptionNotFoundException When the subscription cannot be found
     */
    public function manualRenewal(BaseSubscription $subscription, float $amount): BaseSubscription
    {
        return DB::transaction(function () use ($subscription, $amount) {
            $lockedSubscription = $subscription::lockForUpdate()->find($subscription->id);

            if (! $lockedSubscription) {
                throw SubscriptionNotFoundException::forId(
                    (string) $subscription->id,
                    $subscription instanceof QuranSubscription ? 'quran' : 'academic'
                );
            }

            if (! $lockedSubscription->canRenew()) {
                throw new \Exception('Subscription cannot be renewed in current state');
            }

            $this->handleSuccessfulRenewal($lockedSubscription, $amount);

            Log::info("Manual renewal processed for subscription {$lockedSubscription->id}", [
                'amount' => $amount,
            ]);

            return $lockedSubscription->fresh();
        });
    }

    /**
     * Reactivate an expired subscription with new payment
     *
     * @throws SubscriptionNotFoundException When the subscription cannot be found
     */
    public function reactivate(BaseSubscription $subscription, float $amount): BaseSubscription
    {
        return DB::transaction(function () use ($subscription, $amount) {
            $lockedSubscription = $subscription::lockForUpdate()->find($subscription->id);

            if (! $lockedSubscription) {
                throw SubscriptionNotFoundException::forId(
                    (string) $subscription->id,
                    $subscription instanceof QuranSubscription ? 'quran' : 'academic'
                );
            }

            if (! $lockedSubscription->isExpired()) {
                throw new \Exception('Only expired subscriptions can be reactivated');
            }

            $lockedSubscription->update([
                'status' => SubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'starts_at' => now(),
                'ends_at' => $lockedSubscription->billing_cycle->calculateEndDate(now()),
                'next_billing_date' => $lockedSubscription->billing_cycle->calculateEndDate(now()),
                'last_payment_date' => now(),
                'final_price' => $amount,
                'auto_renew' => true,
                'cancellation_reason' => null,
            ]);

            Log::info("Subscription {$lockedSubscription->id} reactivated", [
                'amount' => $amount,
            ]);

            return $lockedSubscription->fresh();
        });
    }
}
