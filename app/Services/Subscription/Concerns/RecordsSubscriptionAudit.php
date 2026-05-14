<?php

namespace App\Services\Subscription\Concerns;

use App\Exceptions\Subscription\SubscriptionInvariantViolation;
use App\Models\BaseSubscription;
use App\Models\SubscriptionAuditLog;
use App\Models\User;
use App\Support\Subscriptions\SubscriptionSnapshot;
use Throwable;

/**
 * Audit-log trait reused by the four canonical subscription writer services:
 *
 *   - SubscriptionLifecycle      (create, activate, pause, resume, extend,
 *                                 cancel, renew, resubscribe, expire,
 *                                 advanceCycle)
 *   - SubscriptionConsumption    (record, reverse)
 *   - SubscriptionPayment        (markCyclePaid, markCycleFailed,
 *                                 confirmCashPayment)
 *   - SubscriptionPricing        (applyOverride, recomputeNextCyclePrice,
 *                                 priceCycle)
 *
 * Phase C ships this trait but DOES NOT wire it into any writer yet —
 * Phase A.5 (the writer-service consolidation pass) does that. The
 * intended usage pattern, copied here so A.5 doesn't have to re-derive it,
 * looks like this:
 *
 *     class SubscriptionLifecycle
 *     {
 *         use RecordsSubscriptionAudit;
 *
 *         public function renew(BaseSubscription $sub, ?User $actor = null, string $source = 'web'): SubscriptionCycle
 *         {
 *             return SubscriptionLock::for($sub, function () use ($sub, $actor, $source) {
 *                 return DB::transaction(function () use ($sub, $actor, $source) {
 *                     return $this->withAudit($sub, 'renew', $source, $actor, function () use ($sub) {
 *                         // ... mutate cycle rows
 *                         // ... SubscriptionReconciler::sync($sub)
 *                         return $newCycle;
 *                     });
 *                 });
 *             });
 *         }
 *     }
 *
 * Contract:
 *   - withAudit MUST be called INSIDE the locked + transactional block.
 *   - The before-snapshot is captured before $work() runs.
 *   - After $work() returns, the subscription is refreshed (so we see the
 *     reconciler's writes) and the after-snapshot is captured.
 *   - SubscriptionInvariantViolation thrown by the reconciler is caught,
 *     recorded into the audit row, then re-thrown so the surrounding
 *     transaction still rolls back.
 *   - SubscriptionAuditLog::record() is no-fail by design, so an audit
 *     write failure never prevents the surrounding mutation from committing.
 */
trait RecordsSubscriptionAudit
{
    /**
     * Wrap a writer's body so the audit log captures before + after
     * snapshots, latency, and any invariant violations.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $work
     * @return TReturn
     *
     * @throws SubscriptionInvariantViolation re-thrown after recording
     */
    protected function withAudit(
        BaseSubscription $sub,
        string $action,
        string $source,
        ?User $actor,
        callable $work,
    ): mixed {
        $before = SubscriptionSnapshot::capture($sub);
        $beforeViewState = $this->resolveViewState($sub);
        $startedAt = microtime(true);

        try {
            $result = $work();

            // Refresh so the snapshot reflects the reconciler's mirror writes.
            try {
                $sub->refresh();
            } catch (Throwable $refreshError) {
                // Refresh failing is non-fatal — we'd rather log a snapshot
                // that's stale than skip the audit row entirely.
            }

            $after = SubscriptionSnapshot::capture($sub);
            $afterViewState = $this->resolveViewState($sub);
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

            SubscriptionAuditLog::record([
                'subscription' => $sub,
                'cycle_id' => $after['current_cycle_id'] ?? $before['current_cycle_id'] ?? null,
                'action' => $action,
                'source' => $source,
                'actor_user_id' => $actor?->getKey(),
                'before_state' => $before,
                'after_state' => $after,
                'view_state_before' => $beforeViewState,
                'view_state_after' => $afterViewState,
                'invariant_violations' => [], // empty = ran and passed
                'latency_ms' => $latencyMs,
            ]);

            return $result;
        } catch (SubscriptionInvariantViolation $violation) {
            // Reconciler rejected the write. Record the attempt + violations
            // before letting the exception propagate (the surrounding
            // DB::transaction will roll back).
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

            SubscriptionAuditLog::record([
                'subscription' => $sub,
                'cycle_id' => $before['current_cycle_id'] ?? null,
                'action' => $action,
                'source' => $source,
                'actor_user_id' => $actor?->getKey(),
                'before_state' => $before,
                'after_state' => $before, // mutation never committed
                'view_state_before' => $beforeViewState,
                'view_state_after' => $beforeViewState,
                'invariant_violations' => $violation->violations(),
                'latency_ms' => $latencyMs,
            ]);

            throw $violation;
        }
    }

    /**
     * Look up the SubscriptionViewState case name for this subscription, if
     * the presentation service is already wired (it lands in Phase A.6).
     * Returns null gracefully while we wait.
     */
    private function resolveViewState(BaseSubscription $sub): ?string
    {
        // TODO(phase-A.6): replace with the real presentation call once
        // SubscriptionPresentation::viewStateFor() ships, e.g.
        //   return app(SubscriptionPresentation::class)->viewStateFor($sub)->value;
        $presentationClass = '\\App\\Services\\Subscription\\SubscriptionPresentation';

        if (! class_exists($presentationClass)) {
            return null;
        }

        try {
            $service = app($presentationClass);
            if (! method_exists($service, 'viewStateFor')) {
                return null;
            }

            $state = $service->viewStateFor($sub);

            if ($state instanceof \BackedEnum) {
                return (string) $state->value;
            }

            if ($state instanceof \UnitEnum) {
                return $state->name;
            }

            return is_scalar($state) ? (string) $state : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}
