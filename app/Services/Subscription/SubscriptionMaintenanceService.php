<?php

namespace App\Services\Subscription;

use Exception;
use App\Enums\BillingCycle;
use App\Enums\EnrollmentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles subscription lifecycle maintenance operations.
 *
 * Covers activation, cancellation, billing cycle changes,
 * auto-renewal toggling, payment failure handling, and
 * cleanup of expired pending subscriptions.
 *
 * Extracted from SubscriptionService to isolate maintenance logic.
 */
class SubscriptionMaintenanceService
{
    /**
     * Subscription type constants (mirrors SubscriptionService)
     */
    public const TYPE_QURAN = 'quran';

    public const TYPE_ACADEMIC = 'academic';

    public const TYPE_COURSE = 'course';

    /**
     * Get all expired pending subscriptions.
     *
     * @param  int|null  $hours  Hours after which pending is expired (uses config default)
     * @return Collection Collection of all expired pending subscriptions
     */
    public function getExpiredPendingSubscriptions(?int $hours = null): Collection
    {
        $hours = $hours ?? config('subscriptions.pending.expires_after_hours', 48);
        $expiredSubscriptions = collect();

        // Quran subscriptions
        $expiredSubscriptions = $expiredSubscriptions->merge(
            QuranSubscription::expiredPending($hours)->get()
        );

        // Academic subscriptions
        $expiredSubscriptions = $expiredSubscriptions->merge(
            AcademicSubscription::expiredPending($hours)->get()
        );

        // Course subscriptions
        $expiredSubscriptions = $expiredSubscriptions->merge(
            CourseSubscription::expiredPending($hours)->get()
        );

        return $expiredSubscriptions;
    }

    /**
     * Cleanup expired pending subscriptions.
     *
     * @param  int|null  $hours  Hours after which pending is expired
     * @param  bool  $dryRun  If true, only returns count without making changes
     * @return array{cancelled: int, by_type: array}
     */
    public function cleanupExpiredPending(?int $hours = null, bool $dryRun = false): array
    {
        $hours = $hours ?? config('subscriptions.pending.expires_after_hours', 48);
        $batchSize = config('subscriptions.cleanup.batch_size', 100);
        $logDeletions = config('subscriptions.cleanup.log_deletions', true);

        $result = [
            'cancelled' => 0,
            'by_type' => [
                self::TYPE_QURAN => 0,
                self::TYPE_ACADEMIC => 0,
                self::TYPE_COURSE => 0,
            ],
        ];

        $subscriptionTypes = [
            self::TYPE_QURAN => QuranSubscription::class,
            self::TYPE_ACADEMIC => AcademicSubscription::class,
            self::TYPE_COURSE => CourseSubscription::class,
        ];

        foreach ($subscriptionTypes as $type => $modelClass) {
            $query = $modelClass::expiredPending($hours);

            if ($dryRun) {
                $count = $query->count();
                $result['by_type'][$type] = $count;
                $result['cancelled'] += $count;
                continue;
            }

            // Process in batches
            $query->chunkById($batchSize, function ($subscriptions) use ($type, $logDeletions, &$result) {
                foreach ($subscriptions as $subscription) {
                    DB::transaction(function () use ($subscription, $type, $logDeletions, &$result) {
                        $subscription->cancelAsDuplicateOrExpired(
                            config('subscriptions.cancellation_reasons.expired')
                        );

                        // Cancel associated pending payments
                        $subscription->payments()
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'cancelled',
                                'cancelled_at' => now(),
                            ]);

                        $result['by_type'][$type]++;
                        $result['cancelled']++;

                        if ($logDeletions) {
                            Log::info('Expired pending subscription cancelled', [
                                'id' => $subscription->id,
                                'code' => $subscription->subscription_code,
                                'type' => $type,
                                'created_at' => $subscription->created_at->toDateTimeString(),
                            ]);
                        }
                    });
                }
            });
        }

        return $result;
    }

    /**
     * Activate a subscription after successful payment
     */
    public function activate(BaseSubscription $subscription, ?float $amountPaid = null): BaseSubscription
    {
        return DB::transaction(function () use ($subscription, $amountPaid) {
            // Lock the row to prevent race conditions
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            if (! $subscription->isPending()) {
                throw new Exception('Subscription is not in pending state');
            }

            $subscription->activate();

            if ($amountPaid !== null) {
                $subscription->update(['final_price' => $amountPaid]);
            }

            Log::info('Subscription activated', [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
                'amount' => $amountPaid,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Cancel a subscription
     */
    public function cancel(BaseSubscription $subscription, ?string $reason = null): BaseSubscription
    {
        return DB::transaction(function () use ($subscription, $reason) {
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            if (! $subscription->canCancel()) {
                throw new Exception('Subscription cannot be cancelled in current state');
            }

            $subscription->cancel($reason);

            Log::info('Subscription cancelled', [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
                'reason' => $reason,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Change subscription billing cycle
     *
     * Note: Takes effect on next renewal, not immediately
     */
    public function changeBillingCycle(BaseSubscription $subscription, BillingCycle $newCycle): BaseSubscription
    {
        return DB::transaction(function () use ($subscription, $newCycle) {
            $updateData = ['billing_cycle' => $newCycle];

            // If new cycle doesn't support auto-renewal, disable it
            if (! $newCycle->supportsAutoRenewal() && $subscription->auto_renew) {
                $updateData['auto_renew'] = false;
            }

            $subscription->update($updateData);

            Log::info('Subscription billing cycle changed', [
                'id' => $subscription->id,
                'new_cycle' => $newCycle->value,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Toggle auto-renewal
     */
    public function toggleAutoRenewal(BaseSubscription $subscription, bool $enabled): BaseSubscription
    {
        if ($enabled && ! $subscription->billing_cycle->supportsAutoRenewal()) {
            throw new Exception('This billing cycle does not support auto-renewal');
        }

        $subscription->update(['auto_renew' => $enabled]);

        Log::info('Subscription auto-renewal toggled', [
            'id' => $subscription->id,
            'auto_renew' => $enabled,
        ]);

        return $subscription->fresh();
    }

    /**
     * Handle payment failure for a subscription.
     *
     * Cancels the subscription and marks payment as failed.
     */
    public function handlePaymentFailure(BaseSubscription $subscription, ?string $reason = null): BaseSubscription
    {
        return DB::transaction(function () use ($subscription, $reason) {
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            $subscription->cancelDueToPaymentFailure();

            Log::warning('Subscription cancelled due to payment failure', [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
                'reason' => $reason,
            ]);

            return $subscription->fresh();
        });
    }
}
