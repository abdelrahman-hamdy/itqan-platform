<?php

namespace App\Support\Subscriptions;

use App\Models\BaseSubscription;
use App\Models\SubscriptionCycle;

/**
 * Pure snapshot helper for the Phase C audit log.
 *
 * `SubscriptionSnapshot::capture($sub)` returns a deterministic array of
 * the fields that matter when diffing before/after state across a writer.
 * The output is what gets stored in `subscription_audit_log.before_state`
 * / `after_state` — keep it small, stable, and easy to JSON-diff.
 *
 * Anything you add here is visible in every future audit row; don't dump
 * the whole model.
 */
final class SubscriptionSnapshot
{
    /**
     * Capture a snapshot of the fields the audit log diffs.
     *
     * Reads from in-memory attributes (does not refresh from DB) so callers
     * are responsible for $sub->refresh()-ing if they want post-write
     * truth — this is intentional, the trait pattern in
     * RecordsSubscriptionAudit handles the refresh.
     *
     * @return array{
     *     id: int|null,
     *     morph_class: string,
     *     status: string|null,
     *     payment_status: string|null,
     *     sessions_used: int|null,
     *     sessions_remaining: int|null,
     *     total_sessions: int|null,
     *     starts_at: string|null,
     *     ends_at: string|null,
     *     grace_period_ends_at: string|null,
     *     current_cycle_id: int|null,
     *     current_cycle_state: string|null,
     *     current_cycle_payment_status: string|null,
     * }
     */
    public static function capture(BaseSubscription $sub): array
    {
        $cycle = $sub->relationLoaded('currentCycle')
            ? $sub->getRelation('currentCycle')
            : $sub->currentCycle;

        return [
            'id' => $sub->getKey() !== null ? (int) $sub->getKey() : null,
            'morph_class' => $sub->getMorphClass(),
            'status' => self::stringify($sub->getAttribute('status')),
            'payment_status' => self::stringify($sub->getAttribute('payment_status')),
            'sessions_used' => self::toIntOrNull($sub->getAttribute('sessions_used')),
            'sessions_remaining' => self::toIntOrNull($sub->getAttribute('sessions_remaining')),
            'total_sessions' => self::toIntOrNull($sub->getAttribute('total_sessions')),
            'starts_at' => self::toIso8601OrNull($sub->getAttribute('starts_at')),
            'ends_at' => self::toIso8601OrNull($sub->getAttribute('ends_at')),
            'grace_period_ends_at' => self::toIso8601OrNull($sub->getAttribute('grace_period_ends_at')),
            'current_cycle_id' => $cycle instanceof SubscriptionCycle ? (int) $cycle->getKey() : null,
            'current_cycle_state' => $cycle instanceof SubscriptionCycle
                ? self::stringify($cycle->getAttribute('cycle_state'))
                : null,
            'current_cycle_payment_status' => $cycle instanceof SubscriptionCycle
                ? self::stringify($cycle->getAttribute('payment_status'))
                : null,
        ];
    }

    private static function toIntOrNull(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Coerce enum / Stringable / scalar into a plain string for JSON.
     */
    private static function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_object($value)) {
            // Native PHP enum
            if ($value instanceof \BackedEnum) {
                return (string) $value->value;
            }
            if ($value instanceof \UnitEnum) {
                return $value->name;
            }
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            return null;
        }

        return (string) $value;
    }

    private static function toIso8601OrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        // Carbon casts already return DateTimeInterface; this branch covers
        // raw string dates that slipped past the cast.
        return is_string($value) ? $value : null;
    }
}
