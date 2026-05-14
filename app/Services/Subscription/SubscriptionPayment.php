<?php

namespace App\Services\Subscription;

use App\Enums\SubscriptionViewState;
use App\Models\BaseSubscription;
use App\Models\Payment;
use App\Models\SubscriptionCycle;
use App\Models\User;
use App\Services\Subscription\Concerns\RecordsSubscriptionAudit;
use App\Support\Subscriptions\SubscriptionLock;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionPayment — the SOLE writer of cycle payment-status transitions
 * triggered from a payment event.
 *
 * Three entry points, all wrapped in lock → tx → reconciler → audit per
 * `docs/subscription-invariants.md §6`:
 *
 *   - {@see markCyclePaid()}        — webhook / gateway callback.
 *   - {@see markCycleFailed()}      — gateway reports failure.
 *   - {@see confirmCashPayment()}   — supervisor manual confirmation.
 *
 * `confirmCashPayment()` is special: when the subscription is in the
 * `expired` view-state, it routes through `SubscriptionLifecycle::resubscribe()`
 * instead of just marking the cycle paid. That's P4 — "supervisor cash on
 * expired sub = reactivate with today's date".
 *
 * Reconciler invocation is non-negotiable: the cycle write changes
 * `payment_status`, which the subscription row mirrors. The reconciler
 * also re-runs `SubscriptionInvariantChecker` and rolls back if any
 * invariant is violated.
 */
class SubscriptionPayment
{
    use RecordsSubscriptionAudit;

    public function __construct(
        private readonly SubscriptionReconciler $reconciler,
        private readonly Dispatcher $dispatcher,
    ) {}

    /**
     * Mark `$cycle` paid in response to a successful payment event.
     *
     * Webhook path: a queued cycle goes ACTIVE on the same beat the
     * gateway confirms the charge (because the reconciler mirrors the
     * subscription off the cycle on every sync). The current cycle path is
     * unchanged.
     */
    public function markCyclePaid(
        BaseSubscription $sub,
        SubscriptionCycle $cycle,
        Payment $payment,
        ?User $actor = null,
        string $source = 'webhook',
    ): SubscriptionCycle {
        return SubscriptionLock::for($sub, function () use ($sub, $cycle, $payment, $actor, $source) {
            return DB::transaction(function () use ($sub, $cycle, $payment, $actor, $source) {
                return $this->withAudit(
                    $sub,
                    'mark_cycle_paid',
                    $source,
                    $actor,
                    function () use ($sub, $cycle, $payment) {
                        /** @var SubscriptionCycle $locked */
                        $locked = SubscriptionCycle::query()
                            ->whereKey($cycle->getKey())
                            ->lockForUpdate()
                            ->first();

                        if ($locked === null) {
                            throw new \RuntimeException("SubscriptionCycle#{$cycle->getKey()} disappeared mid-mark-paid.");
                        }

                        // PENDING → ACTIVE on the cycle the moment its
                        // payment lands. A queued cycle that gets paid
                        // before its starts_at remains QUEUED — the
                        // advance-cron promotes it at the right time.
                        $shouldActivate = $locked->cycle_state === SubscriptionCycle::STATE_QUEUED
                            && $locked->starts_at !== null
                            && $locked->starts_at->lte(now());

                        $locked->fill([
                            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
                            'payment_id' => $payment->getKey(),
                        ]);

                        if ($shouldActivate) {
                            $locked->cycle_state = SubscriptionCycle::STATE_ACTIVE;
                        }

                        $locked->save();

                        // Stamp the parent's last_payment_date so the
                        // §1 "first-payment shape" check stops matching.
                        $sub->last_payment_date = now();
                        $sub->reconciling = true;
                        try {
                            $sub->save();
                        } finally {
                            $sub->reconciling = false;
                        }

                        $this->reconciler->sync($sub);

                        Log::info('subscription.cycle_marked_paid', [
                            'subscription_id' => $sub->getKey(),
                            'subscription_type' => $sub->getMorphClass(),
                            'cycle_id' => $locked->getKey(),
                            'payment_id' => $payment->getKey(),
                            'activated' => $shouldActivate,
                        ]);

                        return $locked->fresh();
                    },
                );
            });
        });
    }

    /**
     * Mark `$cycle` payment as FAILED. Gateway-side failure (declined card,
     * webhook reports failure, etc.). The subscription row will mirror this
     * to `payment_status = FAILED` on the next reconciler sync.
     */
    public function markCycleFailed(
        BaseSubscription $sub,
        SubscriptionCycle $cycle,
        string $reason,
        ?User $actor = null,
        string $source = 'webhook',
    ): SubscriptionCycle {
        return SubscriptionLock::for($sub, function () use ($sub, $cycle, $reason, $actor, $source) {
            return DB::transaction(function () use ($sub, $cycle, $reason, $actor, $source) {
                return $this->withAudit(
                    $sub,
                    'mark_cycle_failed',
                    $source,
                    $actor,
                    function () use ($sub, $cycle, $reason) {
                        /** @var SubscriptionCycle $locked */
                        $locked = SubscriptionCycle::query()
                            ->whereKey($cycle->getKey())
                            ->lockForUpdate()
                            ->first();

                        if ($locked === null) {
                            throw new \RuntimeException("SubscriptionCycle#{$cycle->getKey()} disappeared mid-mark-failed.");
                        }

                        $metadata = $locked->metadata ?? [];
                        $metadata['payment_failure_reason'] = $reason;
                        $metadata['payment_failed_at'] = now()->toDateTimeString();

                        $locked->fill([
                            'payment_status' => SubscriptionCycle::PAYMENT_FAILED,
                            'metadata' => $metadata,
                        ]);
                        $locked->save();

                        $this->reconciler->sync($sub);

                        Log::warning('subscription.cycle_marked_failed', [
                            'subscription_id' => $sub->getKey(),
                            'subscription_type' => $sub->getMorphClass(),
                            'cycle_id' => $locked->getKey(),
                            'reason' => $reason,
                        ]);

                        return $locked->fresh();
                    },
                );
            });
        });
    }

    /**
     * Supervisor manual cash-payment confirmation.
     *
     * Per P4 + INV-G3: when the subscription is in the `expired` view-state
     * a supervisor confirming cash MUST route through
     * `SubscriptionLifecycle::resubscribe()` — a new cycle starting today
     * with the current package, full quota, audit-logged as "reactivated
     * via late cash". Otherwise we mark the cycle paid in place.
     *
     * Returns the cycle that was settled (either the original passed in,
     * or the newly-created cycle from the resubscribe path).
     */
    public function confirmCashPayment(
        BaseSubscription $sub,
        SubscriptionCycle $cycle,
        User $supervisor,
        string $source = 'supervisor',
    ): SubscriptionCycle {
        // P4 / INV-G3 — expired sub routing. We branch BEFORE acquiring the
        // payment-flow lock because resubscribe acquires the same lock; the
        // single-acquire keeps the call cheap.
        $presentation = app(SubscriptionPresentation::class);
        if ($presentation->viewStateFor($sub) === SubscriptionViewState::EXPIRED) {
            Log::info('subscription.supervisor_cash_routed_to_resubscribe', [
                'subscription_id' => $sub->getKey(),
                'subscription_type' => $sub->getMorphClass(),
                'cycle_id' => $cycle->getKey(),
                'supervisor_id' => $supervisor->getKey(),
            ]);

            $lifecycle = app(SubscriptionLifecycle::class);
            $resubbed = $lifecycle->resubscribe(
                $sub,
                ['payment_mode' => 'paid', 'supervisor_cash' => true],
                $supervisor,
                'supervisor',
            );

            return $resubbed->fresh(['currentCycle'])->currentCycle
                ?? throw new \RuntimeException('Resubscribe completed without a current cycle.');
        }

        // Standard path: mark THIS cycle paid. We synthesise a manual cash
        // Payment row so the cycle.payment_id link is preserved (downstream
        // earnings / receipts depend on it). The actual Payment creation
        // is delegated to the existing supervisor-cash flow — we just
        // need a payment id to thread.
        //
        // TODO(A.5+supervisor-cash-payment): in Phase A.7 we'll have a
        // CashPaymentRecorder service that mints the Payment row from the
        // supervisor input. Until then callers MUST pre-create the
        // Payment and pass it in via $cycle->payment.
        $payment = $cycle->payment;
        if (! $payment instanceof Payment) {
            throw new \RuntimeException(sprintf(
                'confirmCashPayment requires cycle #%d to already have a Payment row attached (cycle.payment_id). The Phase A.5 service does not mint payments itself.',
                $cycle->getKey(),
            ));
        }

        return $this->markCyclePaid($sub, $cycle, $payment, $supervisor, $source);
    }
}
