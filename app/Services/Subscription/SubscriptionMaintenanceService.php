<?php

namespace App\Services\Subscription;

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Carbon\Carbon;
use Exception;
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
        return DB::transaction(function () use ($subscription, $enabled) {
            // Lock the row to prevent concurrent renewal jobs from racing with this toggle
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            if ($enabled && ! $subscription->billing_cycle->supportsAutoRenewal()) {
                throw new Exception('This billing cycle does not support auto-renewal');
            }

            $subscription->update(['auto_renew' => $enabled]);

            Log::info('Subscription auto-renewal toggled', [
                'id' => $subscription->id,
                'auto_renew' => $enabled,
            ]);

            return $subscription->fresh();
        });
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

    // ========================================================================
    // GRACE PERIOD (EXTEND / CANCEL EXTENSION)
    // ========================================================================

    /**
     * Extend a subscription by granting a grace period on its current cycle.
     *
     * Single source of truth for both Filament admin and supervisor frontend
     * extend actions. Writes grace_period_ends_at to the current cycle when
     * one exists, mirrors it into subscription.metadata for backward compat.
     *
     * Works on ACTIVE / PAUSED / EXPIRED / PENDING. If the subscription is
     * PAUSED or EXPIRED, transitions to ACTIVE for the grace duration.
     * Does NOT touch ends_at or payment_status — the paid-for window stays honest.
     *
     * @param  array{extended_by?: int|null, extended_by_name?: string|null}  $actor
     * @return array{subscription: BaseSubscription, grace_period_ends_at: Carbon}
     */
    public function extend(BaseSubscription $subscription, int $graceDays, array $actor = []): array
    {
        if ($graceDays < 1) {
            throw new Exception('Grace days must be at least 1');
        }

        return DB::transaction(function () use ($subscription, $graceDays, $actor) {
            /** @var BaseSubscription $subscription */
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            $metadata = $subscription->metadata ?? [];

            // Stack on existing grace period, otherwise start from ends_at (or now)
            $baseDate = isset($metadata['grace_period_ends_at'])
                ? Carbon::parse($metadata['grace_period_ends_at'])
                : ($subscription->ends_at ?? now());

            $gracePeriodEndsAt = $baseDate->copy()->addDays($graceDays);
            $metadata['grace_period_ends_at'] = $gracePeriodEndsAt->toDateTimeString();

            // Audit log entry — capped to the most recent
            // `config('subscriptions.grace.extensions_log_cap')` entries so the
            // metadata JSON doesn't grow unbounded over many extensions.
            $extensions = $metadata['extensions'] ?? [];
            $extensions[] = [
                'type' => 'grace_period',
                'grace_days' => $graceDays,
                'extended_by' => $actor['extended_by'] ?? auth()->id(),
                'extended_by_name' => $actor['extended_by_name'] ?? (auth()->user()->name ?? 'system'),
                'ends_at_at_time' => ($subscription->ends_at ?? now())->toDateTimeString(),
                'grace_period_ends_at' => $gracePeriodEndsAt->toDateTimeString(),
                'extended_at' => now()->toDateTimeString(),
            ];
            $cap = (int) config('subscriptions.grace.extensions_log_cap', 50);
            $metadata['extensions'] = $cap > 0 ? array_slice($extensions, -$cap) : $extensions;

            $updateData = ['metadata' => $metadata];

            // If PAUSED or EXPIRED, re-activate for the grace duration so the
            // student can keep scheduling. Does NOT touch ends_at.
            $justReactivated = in_array($subscription->status, [
                SessionSubscriptionStatus::PAUSED,
                SessionSubscriptionStatus::EXPIRED,
            ], true);

            if ($justReactivated) {
                $updateData['status'] = SessionSubscriptionStatus::ACTIVE;
            }

            $subscription->update($updateData);

            // Mirror the grace onto the current cycle row if one exists.
            // lockForUpdate prevents a concurrent extension from reading stale data.
            if ($subscription->current_cycle_id) {
                SubscriptionCycle::where('id', $subscription->current_cycle_id)
                    ->lockForUpdate()
                    ->update(['grace_period_ends_at' => $gracePeriodEndsAt]);
            }

            // Only on the actual PAUSED/EXPIRED → ACTIVE transition: bring back
            // suspended sessions and the linked entity's is_active flag.
            // Already-ACTIVE extensions (grace stack) skip this to avoid empty UPDATEs.
            if ($justReactivated) {
                $subscription->restoreSuspendedSessions();
                $subscription->syncLinkedEducationUnitActiveFlag(true);
            }

            Log::info('Subscription extended (grace period)', [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
                'grace_days' => $graceDays,
                'grace_period_ends_at' => $gracePeriodEndsAt->toDateTimeString(),
            ]);

            return [
                'subscription' => $subscription->fresh(),
                'grace_period_ends_at' => $gracePeriodEndsAt,
            ];
        });
    }

    /**
     * Cancel the active grace period on a subscription.
     *
     * Removes grace_period_ends_at from metadata and current cycle, logs a
     * cancellation entry in extensions history. If the subscription period
     * has already ended when cancellation happens, pauses the subscription
     * (new lifecycle: pause, not expire).
     *
     * @param  array{cancelled_by?: int|null, cancelled_by_name?: string|null}  $actor
     */
    public function cancelExtension(BaseSubscription $subscription, array $actor = []): BaseSubscription
    {
        return DB::transaction(function () use ($subscription, $actor) {
            /** @var BaseSubscription $subscription */
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            $metadata = $subscription->metadata ?? [];

            if (! isset($metadata['grace_period_ends_at'])
                && ! ($subscription->current_cycle_id
                    && $subscription->currentCycle?->grace_period_ends_at !== null)) {
                throw new Exception('No active grace period to cancel');
            }

            unset($metadata['grace_period_ends_at'], $metadata['grace_period_expires_at']);

            $metadata['extensions'] = $metadata['extensions'] ?? [];
            $metadata['extensions'][] = [
                'type' => 'grace_period_cancelled',
                'cancelled_by' => $actor['cancelled_by'] ?? auth()->id(),
                'cancelled_by_name' => $actor['cancelled_by_name'] ?? (auth()->user()->name ?? 'system'),
                'cancelled_at' => now()->toDateTimeString(),
            ];

            $updateData = ['metadata' => $metadata ?: null];

            // If the subscription's paid period has already ended, pause it
            // (previously this set EXPIRED; the new lifecycle prefers PAUSED).
            if ($subscription->ends_at && $subscription->ends_at->isPast()) {
                $updateData['status'] = SessionSubscriptionStatus::PAUSED;
            }

            $subscription->update($updateData);

            // Clear grace_period_ends_at on the current cycle row
            if ($subscription->current_cycle_id) {
                SubscriptionCycle::where('id', $subscription->current_cycle_id)
                    ->lockForUpdate()
                    ->update(['grace_period_ends_at' => null]);
            }

            Log::info('Subscription grace period cancelled', [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Pause a subscription (admin/supervisor action).
     *
     * Visible only when subscription is ACTIVE and not in grace period.
     * Sets status = PAUSED, records paused_at.
     */
    public function pause(BaseSubscription $subscription): BaseSubscription
    {
        return DB::transaction(function () use ($subscription) {
            /** @var BaseSubscription $subscription */
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            if ($subscription->status !== SessionSubscriptionStatus::ACTIVE) {
                throw new Exception('Only active subscriptions can be paused');
            }

            $subscription->update([
                'status' => SessionSubscriptionStatus::PAUSED,
                'paused_at' => now(),
            ]);

            Log::info('Subscription paused', [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Resume a paused subscription.
     *
     * Calls the model's resume() so the "extend ends_at by paused duration"
     * logic stays in one place.
     */
    public function resume(BaseSubscription $subscription): BaseSubscription
    {
        return DB::transaction(function () use ($subscription) {
            /** @var BaseSubscription $subscription */
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            if ($subscription->status !== SessionSubscriptionStatus::PAUSED) {
                throw new Exception('Only paused subscriptions can be resumed');
            }

            $subscription->resume();

            Log::info('Subscription resumed', [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
            ]);

            return $subscription->fresh();
        });
    }
}
