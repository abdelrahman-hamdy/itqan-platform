<?php

namespace App\Services\Subscription\Concerns;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionType;
use App\Models\BaseSubscription;
use App\Models\Payment;
use App\Models\SubscriptionAuditLog;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionLifecycle;
use App\Services\Subscription\SubscriptionPayment;
use App\Services\Subscription\SubscriptionPresentation;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tier 2 dual-execution helper — wires every payment webhook handler
 * through the v2 SubscriptionLifecycle / SubscriptionPayment services
 * IN ADDITION to the legacy activateFromPayment path.
 *
 * The legacy path stays the canonical writer until
 * `subscriptions.v2_payment_canonical` is flipped on (Tier 2 exit). v2 is
 * shadow-only: it runs, writes a SubscriptionAuditLog row of action
 * `payment.v2_shadow` with view-state-before/after + a divergence summary,
 * and crashes silently into the same audit row if anything throws. **v2
 * exceptions are NEVER re-raised** — payments must NEVER break.
 *
 * Gating:
 *   - `subscriptions.v2_payment_dual`      → shadow execution on
 *   - `subscriptions.v2_payment_canonical` → reserved for Tier 2 cutover
 *     (legacy will then run as the shadow). Today: false → no-op.
 *
 * The trait does NOT touch idempotency guards (PaymentWebhookEvent),
 * lockForUpdate on Payment, currency conversion, post-payment notifications,
 * or invoice generation — those live in the controllers and stay exactly
 * as-is.
 */
trait DualExecutesPayment
{
    /**
     * Run the legacy activation callable, then (optionally) the v2 callable
     * as a shadow. Returns the legacy result so callers can inspect it as
     * they always did.
     *
     * @template TLegacy
     *
     * @param  callable(): TLegacy  $legacy  the existing `$payable->activateFromPayment($payment)` invocation
     * @param  callable(): mixed  $v2  v2 invocation — see ::v2Callable() for the canonical shape
     * @return TLegacy
     */
    protected function runWithDualExecution(Payment $payment, callable $legacy, callable $v2): mixed
    {
        $legacyResult = $legacy();

        if (! config('subscriptions.v2_payment_dual')) {
            return $legacyResult;
        }

        $sub = $this->resolvePayableSubscription($payment);

        $viewStateBefore = $sub instanceof BaseSubscription
            ? $this->viewStateValue($sub->fresh() ?? $sub)
            : null;

        $started = microtime(true);
        $v2Exception = null;
        $v2Result = null;

        try {
            $v2Result = $v2();
        } catch (Throwable $e) {
            $v2Exception = $e;
            Log::warning('subscription.v2_shadow_threw', [
                'channel' => 'subscriptions',
                'payment_id' => $payment->getKey(),
                'payment_method' => $payment->payment_method ?? null,
                'subscription_morph' => $sub?->getMorphClass(),
                'subscription_id' => $sub?->getKey(),
                'exception' => $e->getMessage(),
                'class' => $e::class,
            ]);
        }

        $viewStateAfter = $sub instanceof BaseSubscription
            ? $this->viewStateValue($sub->fresh() ?? $sub)
            : null;

        $latencyMs = (int) round((microtime(true) - $started) * 1000);

        $this->writeShadowAudit(
            $payment,
            $sub,
            $viewStateBefore,
            $viewStateAfter,
            $v2Exception,
            $v2Result,
            $latencyMs,
        );

        return $legacyResult;
    }

    /**
     * Build the v2 callable for a given payment. Routes based on the
     * subscription's current state:
     *  - PENDING / never activated  → SubscriptionLifecycle::activate
     *  - everything else            → SubscriptionPayment::markCyclePaid
     *    on the cycle the payment points at.
     *
     * Callers can drop in their own v2 callable if they have richer routing
     * (e.g. supervisor cash). When the payment has no resolvable
     * subscription, returns a no-op closure so the trait still records a
     * "no-sub" audit row.
     */
    protected function defaultV2Callable(Payment $payment): callable
    {
        return function () use ($payment) {
            $sub = $this->resolvePayableSubscription($payment);
            if (! $sub instanceof BaseSubscription) {
                return ['skipped' => 'no_subscription_payable'];
            }

            // Webhook actor is the customer (the subscription's student) —
            // there is no human in the loop. The audit log will surface
            // source='webhook' which is the discriminator readers rely on.
            $actor = $sub->student;

            // First-activation = the sub has never had a successful payment.
            // The two stored signals that confirm this are status=PENDING
            // (lifecycle never activated) OR last_payment_date IS NULL with
            // payment_status still PENDING (cycle never settled). PHP's
            // operator precedence makes parens mandatory here — `A || B && C`
            // would group as `A || (B && C)` and let any PENDING-status sub
            // with a non-null last_payment_date slip past the activate branch.
            $isFirstActivation = $sub->status === SessionSubscriptionStatus::PENDING
                || ($sub->payment_status === SubscriptionPaymentStatus::PENDING
                    && $sub->last_payment_date === null);

            if ($isFirstActivation) {
                app(SubscriptionLifecycle::class)->activate(
                    $sub,
                    $payment,
                    $actor,
                    'webhook',
                );

                return ['routed' => 'activate'];
            }

            $cycle = $payment->cycle ?? $sub->currentCycle;
            if (! $cycle instanceof SubscriptionCycle) {
                $cycle = $sub->ensureCurrentCycle();
            }

            app(SubscriptionPayment::class)->markCyclePaid(
                $sub,
                $cycle,
                $payment,
                $actor,
                'webhook',
            );

            return ['routed' => 'mark_cycle_paid', 'cycle_id' => $cycle->getKey()];
        };
    }

    /**
     * Resolve the (Quran/Academic/Course) subscription a payment is targeting.
     * Webhook controllers may pass a Payment whose payable is a sub or whose
     * legacy `subscription_id` is set; we tolerate both.
     */
    protected function resolvePayableSubscription(Payment $payment): ?BaseSubscription
    {
        $payable = $payment->payable ?? null;
        if ($payable instanceof BaseSubscription) {
            return $payable;
        }

        if ($payment->subscription_id === null) {
            return null;
        }

        foreach (SubscriptionType::cases() as $type) {
            $modelClass = $type->modelClass();
            $found = $modelClass::find($payment->subscription_id);
            if ($found instanceof BaseSubscription) {
                return $found;
            }
        }

        return null;
    }

    private function viewStateValue(BaseSubscription $sub): ?string
    {
        try {
            return app(SubscriptionPresentation::class)->viewStateFor($sub)->value;
        } catch (Throwable) {
            return null;
        }
    }

    private function writeShadowAudit(
        Payment $payment,
        ?BaseSubscription $sub,
        ?string $viewStateBefore,
        ?string $viewStateAfter,
        ?Throwable $v2Exception,
        mixed $v2Result,
        int $latencyMs,
    ): void {
        $payload = [
            'payment_id' => $payment->getKey(),
            'payment_method' => $payment->payment_method ?? null,
            'amount' => $payment->amount ?? null,
            'currency' => $payment->currency ?? null,
            'v2_result' => is_array($v2Result) ? $v2Result : (is_scalar($v2Result) ? $v2Result : null),
            'v2_exception' => $v2Exception ? [
                'class' => $v2Exception::class,
                'message' => $v2Exception->getMessage(),
            ] : null,
        ];

        try {
            SubscriptionAuditLog::create([
                'subscription_id' => $sub?->getKey(),
                'subscription_type' => $sub?->getMorphClass(),
                'cycle_id' => $sub?->current_cycle_id,
                'action' => 'payment.v2_shadow',
                'source' => 'webhook',
                'actor_user_id' => null,
                'before_state' => ['view_state' => $viewStateBefore],
                'after_state' => ['view_state' => $viewStateAfter, 'payload' => $payload],
                'view_state_before' => $viewStateBefore,
                'view_state_after' => $viewStateAfter,
                'invariant_violations' => null,
                'has_violations' => $v2Exception !== null,
                'latency_ms' => $latencyMs,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Audit-log write failure must NEVER break legacy. Log + swallow.
            Log::error('subscription.v2_shadow_audit_failed', [
                'channel' => 'subscriptions',
                'payment_id' => $payment->getKey(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
