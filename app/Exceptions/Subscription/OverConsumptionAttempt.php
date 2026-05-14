<?php

namespace App\Exceptions\Subscription;

use App\Exceptions\SubscriptionException;
use App\Models\BaseSubscription;
use App\Models\SubscriptionCycle;
use Throwable;

/**
 * Thrown by SubscriptionConsumption::record when accepting a new (or
 * promoted) consumption row would push the target cycle's
 * `sessions_remaining` below zero — i.e. it would violate INV-B4.
 *
 * The writer raises this BEFORE persisting the row, so the surrounding
 * DB::transaction rolls back and the subscription stays in its prior
 * valid state. Surface area:
 *   - Admin/manual override flows: the action UI should catch this and
 *     surface "Cycle quota exhausted — increase total_sessions or pick a
 *     different cycle" rather than silently dropping the write.
 *   - Auto-attendance: the job should swallow + audit-log (the over-count
 *     is a data anomaly, not a user error).
 */
class OverConsumptionAttempt extends SubscriptionException
{
    public readonly string $subscriptionMorphClass;

    public readonly int $subscriptionId;

    public readonly int $cycleId;

    public readonly int $cycleTotalSessions;

    public readonly int $cycleSessionsUsed;

    public function __construct(
        BaseSubscription $subscription,
        SubscriptionCycle $cycle,
        ?Throwable $previous = null,
    ) {
        $this->subscriptionMorphClass = $subscription->getMorphClass();
        $this->subscriptionId = (int) $subscription->id;
        $this->cycleId = (int) $cycle->id;
        $this->cycleTotalSessions = (int) $cycle->total_sessions;
        $this->cycleSessionsUsed = (int) $cycle->sessions_used;

        parent::__construct(
            sprintf(
                'Over-consumption attempt on cycle %d (subscription %s#%d): %d/%d already used, cannot accept another consumption.',
                $this->cycleId,
                $this->subscriptionMorphClass,
                $this->subscriptionId,
                $this->cycleSessionsUsed,
                $this->cycleTotalSessions,
            ),
            'OVER_CONSUMPTION_ATTEMPT',
            [
                'subscription_morph_class' => $this->subscriptionMorphClass,
                'subscription_id' => $this->subscriptionId,
                'cycle_id' => $this->cycleId,
                'cycle_total_sessions' => $this->cycleTotalSessions,
                'cycle_sessions_used' => $this->cycleSessionsUsed,
            ],
            0,
            $previous,
        );
    }
}
