<?php

namespace App\Services\Subscription;

use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Exceptions\SubscriptionNotFoundException;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\Payment;
use App\Models\QuranSubscription;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

                // Create a Payment record for the renewal
                $payment = $this->createRenewalPayment($lockedSubscription, $renewalPrice);

                // Process the payment through the payment service
                $paymentResult = $this->paymentService->processSubscriptionRenewal($payment);

                if ($paymentResult['success'] ?? false) {
                    $this->handleSuccessfulRenewal($lockedSubscription, $renewalPrice);

                    return true;
                } else {
                    // Mark payment as failed
                    $payment->update([
                        'status' => PaymentStatus::FAILED,
                        'failure_reason' => $paymentResult['error'] ?? 'فشل الدفع',
                    ]);

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
        if ($subscription->status !== SessionSubscriptionStatus::ACTIVE) {
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
            'status' => SessionSubscriptionStatus::ACTIVE,
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
     *
     * When payment fails, subscription is cancelled immediately
     */
    public function handleFailedRenewal(BaseSubscription $subscription, string $reason): void
    {
        $subscription->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
            'payment_status' => SubscriptionPaymentStatus::FAILED,
            'auto_renew' => false,
            'cancellation_reason' => 'فشل الدفع التلقائي: '.$reason,
            'cancelled_at' => now(),
        ]);

        $this->notificationService->sendPaymentFailedNotification($subscription, $reason);

        Log::warning("Subscription {$subscription->id} renewal failed - subscription cancelled", [
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
     * Reactivate a cancelled subscription with new payment
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

            if (! $lockedSubscription->isCancelled()) {
                throw new \Exception('Only cancelled subscriptions can be reactivated');
            }

            $lockedSubscription->update([
                'status' => SessionSubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'starts_at' => now(),
                'ends_at' => $lockedSubscription->billing_cycle->calculateEndDate(now()),
                'next_billing_date' => $lockedSubscription->billing_cycle->calculateEndDate(now()),
                'last_payment_date' => now(),
                'final_price' => $amount,
                'auto_renew' => true,
                'cancellation_reason' => null,
                'cancelled_at' => null,
            ]);

            Log::info("Subscription {$lockedSubscription->id} reactivated", [
                'amount' => $amount,
            ]);

            return $lockedSubscription->fresh();
        });
    }

    /**
     * Create a Payment record for subscription renewal.
     */
    protected function createRenewalPayment(BaseSubscription $subscription, float $amount): Payment
    {
        $subscriptionType = $subscription instanceof QuranSubscription ? 'quran' : 'academic';
        $payableType = $subscription instanceof QuranSubscription
            ? QuranSubscription::class
            : AcademicSubscription::class;

        return Payment::create([
            'academy_id' => $subscription->academy_id,
            'user_id' => $subscription->student_id,
            'subscription_id' => $subscription->id,
            'payment_code' => $this->generatePaymentCode($subscription->academy_id),
            'payment_method' => 'auto_renewal',
            'payment_gateway' => config('payments.default', 'paymob'),
            'payment_type' => $subscriptionType.'_subscription_renewal',
            'amount' => $amount,
            'net_amount' => $amount,
            'currency' => $subscription->currency ?? getCurrencyCode(null, $subscription->academy),
            'status' => PaymentStatus::PENDING,
            'notes' => 'تجديد تلقائي للاشتراك',
            'payable_type' => $payableType,
            'payable_id' => $subscription->id,
        ]);
    }

    /**
     * Generate a unique payment code.
     */
    protected function generatePaymentCode(int $academyId): string
    {
        $prefix = 'RNW';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$academyId}-{$date}-{$random}";
    }
}
