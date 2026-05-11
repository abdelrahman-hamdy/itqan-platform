<?php

namespace App\Constants;

/**
 * Pause reason discriminators stamped on `subscriptions.pause_reason` to
 * indicate how a subscription entered the PAUSED state.
 *
 * The pause origin determines which admin action is appropriate to unblock
 * the subscription:
 *   - END_OF_PERIOD → Extend (grace days) or Renew (full new cycle).
 *     Resume is hidden in the UI because it would silently extend the paid
 *     window by however long elapsed between cron and admin click.
 *   - Manual pauses (admin-supplied free-form Arabic strings) → Resume,
 *     which adds the paused-duration onto ends_at to recover the lost time.
 *   - Legacy `legacy_sessions_exhausted_pause_reason` (config-driven) →
 *     `BaseSubscription::returnSession()` auto-unpauses; otherwise Renew.
 *
 * @see \App\Console\Commands\ExpireActiveSubscriptions
 * @see \App\Filament\Shared\Traits\HasSubscriptionActions
 * @see docs/subscription-behavior-spec.md §1.3
 */
final class PauseReason
{
    /**
     * The paid subscription window ended with no grace period and no queued
     * cycle. Stamped by `subscriptions:expire-active`.
     */
    public const END_OF_PERIOD = 'end_of_period';
}
