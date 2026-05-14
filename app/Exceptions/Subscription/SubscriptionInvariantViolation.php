<?php

namespace App\Exceptions\Subscription;

use App\Exceptions\SubscriptionException;
use App\Models\BaseSubscription;
use Throwable;

/**
 * Thrown by SubscriptionReconciler::sync when the post-mirror invariant
 * check returns a non-empty violations array.
 *
 * Per INV-C2 the reconciler MUST be the last call inside every locked
 * mutator's transaction. Raising this exception causes Laravel to roll
 * back the surrounding DB::transaction and the lock is released — the
 * write never lands, the subscription stays in its prior (valid) state.
 *
 * The `violations` payload is the array returned by
 * SubscriptionInvariantChecker::check($sub). Each entry should be a
 * structured violation (e.g. [code, message, fields]) — Phase A.4 owns
 * its exact shape.
 */
class SubscriptionInvariantViolation extends SubscriptionException
{
    public readonly string $subscriptionMorphClass;

    public readonly int $subscriptionId;

    /**
     * @param  array<int, array<string, mixed>>  $violations
     */
    public function __construct(
        BaseSubscription $subscription,
        public readonly array $violations,
        ?Throwable $previous = null,
    ) {
        $this->subscriptionMorphClass = $subscription->getMorphClass();
        $this->subscriptionId = (int) $subscription->id;

        $count = count($violations);
        $firstCode = $violations[0]['code'] ?? ($violations[0]['name'] ?? 'unknown');

        parent::__construct(
            message: sprintf(
                'Subscription %s#%d failed invariant check (%d violation%s; first: %s).',
                $this->subscriptionMorphClass,
                $this->subscriptionId,
                $count,
                $count === 1 ? '' : 's',
                $firstCode,
            ),
            errorCode: 'INVARIANT_VIOLATION',
            context: [
                'subscription_morph_class' => $this->subscriptionMorphClass,
                'subscription_id' => $this->subscriptionId,
                'violations' => $violations,
            ],
            previous: $previous,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function violations(): array
    {
        return $this->violations;
    }
}
