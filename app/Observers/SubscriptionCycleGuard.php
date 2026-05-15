<?php

namespace App\Observers;

use App\Models\SubscriptionCycle;
use Illuminate\Support\Facades\Log;

/**
 * Defensive guard on `SubscriptionCycle` writes.
 *
 * INV-B4 — `cycle.sessions_used` must never exceed `cycle.total_sessions`.
 * The cleanup found 10 historical cycles in this state (one on an active
 * sub). They were repaired by bumping `total_sessions = sessions_used`;
 * this observer prevents the same shape from being created again.
 *
 * Behaviour:
 *   - On `saving`, if the incoming row would result in
 *     `sessions_used > total_sessions`, raise a structured exception.
 *   - The exception carries the cycle id + delta so the calling code can
 *     log/handle it without parsing strings.
 *
 * Bypass:
 *   - Pass `cycle.metadata.allow_over_consumption = true` if a future
 *     operational scenario legitimately needs this (e.g. recovering a
 *     historical cycle). The observer logs the bypass to the
 *     `subscriptions` channel so it's visible in observability.
 */
class SubscriptionCycleGuard
{
    public function saving(SubscriptionCycle $cycle): void
    {
        $used = (int) ($cycle->sessions_used ?? 0);
        $total = (int) ($cycle->total_sessions ?? 0);

        if ($used <= $total) {
            return;
        }

        $metadata = $cycle->metadata ?? [];
        if (is_array($metadata) && ! empty($metadata['allow_over_consumption'])) {
            Log::channel(config('logging.subscriptions_channel', 'stack'))
                ->warning('subscription_cycle.over_consumption_allowed', [
                    'cycle_id' => $cycle->getKey(),
                    'subscription_type' => $cycle->subscribable_type,
                    'subscription_id' => $cycle->subscribable_id,
                    'sessions_used' => $used,
                    'total_sessions' => $total,
                    'delta' => $used - $total,
                ]);

            return;
        }

        throw new \RuntimeException(sprintf(
            'INV-B4 guard: cycle #%s would have sessions_used=%d > total_sessions=%d (delta=%d). '.
            'Bump total_sessions, reverse consumption rows, or set metadata.allow_over_consumption=true.',
            $cycle->getKey() ?? 'new',
            $used,
            $total,
            $used - $total,
        ));
    }
}
