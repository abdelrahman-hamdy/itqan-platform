<?php

namespace App\Http\Traits\Api;

use App\Models\SubscriptionCycle;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared cycle-formatting helpers for API controllers that return
 * subscription payloads (Student, Parent).
 *
 * Provides `formatCycle()` for a single cycle row and `formatCycleFields()`
 * for the standard set of fields to inject into a subscription response.
 */
trait FormatsSubscriptionCycles
{
    /**
     * Format a SubscriptionCycle for API output.
     */
    protected function formatCycle($cycle): ?array
    {
        if ($cycle instanceof BelongsTo) {
            $cycle = $cycle->getResults();
        }

        if (! $cycle instanceof SubscriptionCycle) {
            return null;
        }

        return [
            'id' => $cycle->id,
            'cycle_number' => $cycle->cycle_number,
            'state' => $cycle->cycle_state,
            'billing_cycle' => $cycle->billing_cycle,
            'starts_at' => $cycle->starts_at?->toDateString(),
            'ends_at' => $cycle->ends_at?->toDateString(),
            'total_sessions' => (int) $cycle->total_sessions,
            'sessions_used' => (int) $cycle->sessions_used,
            'sessions_remaining' => max(0, (int) $cycle->total_sessions - (int) $cycle->sessions_used),
            'total_price' => (float) $cycle->total_price,
            'final_price' => (float) $cycle->final_price,
            'currency' => $cycle->currency,
            'payment_status' => $cycle->payment_status,
            'grace_period_ends_at' => $cycle->grace_period_ends_at?->toDateString(),
            'is_in_grace_period' => $cycle->isInGracePeriod(),
        ];
    }

    /**
     * Standard cycle-related fields to inject into a subscription response.
     *
     * Prefers the eager-loaded `cycles` relation when available to avoid N+1
     * queries in list endpoints. Falls back to a targeted query if the relation
     * wasn't pre-loaded by the controller.
     */
    protected function formatCycleFields($subscription): array
    {
        $cycles = [];
        if (method_exists($subscription, 'cycles')) {
            $rawCycles = $subscription->relationLoaded('cycles')
                ? $subscription->cycles->sortByDesc('cycle_number')->values()
                : $subscription->cycles()->orderBy('cycle_number', 'desc')->get();

            $cycles = $rawCycles
                ->map(fn ($cycle) => $this->formatCycle($cycle))
                ->filter()
                ->values()
                ->all();
        }

        return [
            'is_schedulable' => method_exists($subscription, 'isSchedulable')
                ? $subscription->isSchedulable()
                : false,
            'current_cycle' => $this->formatCycle(
                method_exists($subscription, 'currentCycle') ? $subscription->currentCycle : null
            ),
            'cycle_count' => (int) ($subscription->cycle_count ?? 1),
            'cycles' => $cycles,
        ];
    }
}
