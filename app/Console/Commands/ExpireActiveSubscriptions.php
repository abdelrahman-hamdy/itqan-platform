<?php

namespace App\Console\Commands;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Notifications\SubscriptionExpiredNotification;
use App\Services\Subscription\SubscriptionLifecycle;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ExpireActiveSubscriptions cron — flips ACTIVE subs whose `ends_at` has
 * passed into the canonical EXPIRED state via SubscriptionLifecycle::expire().
 *
 * Policy clarified 2026-05-16: end-of-period is a terminal transition, NOT
 * an automatic grace window.
 *
 * Pause is reserved for explicit teacher/admin holds. The previous
 * implementation auto-paused with `pause_reason = END_OF_PERIOD`; that
 * coupled three different concerns (renewal due, sub-still-accessible,
 * pause-as-state) and was retired together with all UI predicates that
 * branched on END_OF_PERIOD.
 *
 * Skips:
 *   - subs in an active admin-granted grace window (cycle.grace_period_ends_at > now)
 *   - subs whose queued cycle is paid (AdvanceSubscriptionCycles will
 *     promote it instead — different verb entirely)
 *
 * Failure path: per-sub transaction inside expire(). One failure logs +
 * skips that sub and increments the failure counter; the batch continues.
 */
class ExpireActiveSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire-active
                            {--dry-run : Preview changes without making them}';

    protected $description = 'Expire ACTIVE subscriptions whose paid window has ended (no queued+paid cycle, no admin grace).';

    public function handle(SubscriptionLifecycle $lifecycle): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Scanning for active subscriptions past their end date...');
        $this->newLine();

        $stats = [
            'quran' => 0,
            'academic' => 0,
            'skipped_grace' => 0,
            'skipped_queued' => 0,
            'errors' => 0,
        ];

        $now = now();

        $types = [
            'quran' => QuranSubscription::class,
            'academic' => AcademicSubscription::class,
        ];

        foreach ($types as $type => $modelClass) {
            $modelClass::withoutGlobalScopes()
                ->where('status', SessionSubscriptionStatus::ACTIVE)
                ->whereNotNull('ends_at')
                ->where('ends_at', '<', $now)
                ->with(['student', 'currentCycle', 'queuedCycle'])
                ->chunkById(100, function ($subscriptions) use ($dryRun, $lifecycle, &$stats, $type) {
                    foreach ($subscriptions as $subscription) {
                        // 1. Skip if still in an active admin-granted grace
                        //    window. Cycle.grace_period_ends_at is only set
                        //    by SubscriptionMaintenanceService::extend() now;
                        //    the cron itself never writes it.
                        if ($this->isInGracePeriod($subscription)) {
                            $stats['skipped_grace']++;

                            continue;
                        }

                        // 2. Skip if the next cycle is already paid + queued
                        //    — AdvanceSubscriptionCycles will promote it
                        //    instead of expiring the sub.
                        $queued = $subscription->queuedCycle;
                        if ($queued !== null && $queued->payment_status === 'paid') {
                            $stats['skipped_queued']++;

                            continue;
                        }

                        if ($dryRun) {
                            $this->line("  [DRY RUN] Would expire {$type} subscription #{$subscription->id} (ends_at: {$subscription->ends_at})");
                            $stats[$type]++;

                            continue;
                        }

                        try {
                            // The canonical expire path handles:
                            //  - cycle.cycle_state → ARCHIVED (+ payment FAILED on hybrid)
                            //  - sub.status → EXPIRED
                            //  - SubscriptionLock + audit-log row
                            //  - reconciler->sync (counters, invariants)
                            //  - linked education-unit is_active → false
                            //  - SubscriptionExpiredUnpaid notification (hybrid path)
                            $expired = $lifecycle->expire($subscription, source: 'cron');

                            // The clean-expire path (cycle was PAID, just
                            // ran out of dates) doesn't notify from inside
                            // expire(). Match the legacy cron's UX —
                            // student gets a "your subscription ended"
                            // ping with a renew CTA.
                            $wasHybrid = $subscription->currentCycle?->payment_status === 'pending'
                                || $subscription->currentCycle?->payment_status === 'failed';
                            if (! $wasHybrid && $subscription->student) {
                                try {
                                    $subscription->student->notify(
                                        new SubscriptionExpiredNotification($subscription)
                                    );
                                } catch (\Throwable $notifyError) {
                                    Log::warning('subscription.expire_notify_failed', [
                                        'subscription_id' => $subscription->id,
                                        'error' => $notifyError->getMessage(),
                                    ]);
                                }
                            }

                            $stats[$type]++;

                            Log::info('subscription.auto_expired_at_end_of_cycle', [
                                'subscription_id' => $subscription->id,
                                'type' => $type,
                                'ends_at' => $subscription->ends_at->toDateTimeString(),
                                'hybrid' => $wasHybrid,
                            ]);
                        } catch (\Throwable $e) {
                            $stats['errors']++;
                            Log::error('subscription.auto_expire_failed', [
                                'subscription_id' => $subscription->id,
                                'type' => $type,
                                'error' => $e->getMessage(),
                            ]);
                            \App\Services\Subscription\SubscriptionFailureCounter::recordFailure();
                        }
                    }
                });
        }

        $total = $stats['quran'] + $stats['academic'];

        if ($total === 0 && $stats['skipped_grace'] === 0 && $stats['skipped_queued'] === 0) {
            $this->info('No subscriptions to expire.');

            return Command::SUCCESS;
        }

        $this->table(
            ['Type', 'Expired'],
            [
                ['Quran', $stats['quran']],
                ['Academic', $stats['academic']],
                ['Total', $total],
            ]
        );

        if ($stats['skipped_grace'] > 0) {
            $this->line("  Skipped (admin grace extension active): {$stats['skipped_grace']}");
        }
        if ($stats['skipped_queued'] > 0) {
            $this->line("  Skipped (queued+paid cycle — advance-cycles will promote): {$stats['skipped_queued']}");
        }

        if ($stats['errors'] > 0) {
            $this->warn("  Errors: {$stats['errors']}");
        }

        if ($dryRun) {
            $this->warn('Dry run mode — no changes made.');
        } else {
            $this->info("Expired {$total} subscription(s).");
        }

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Grace-period check: respects the cycle-level grace_period_ends_at and
     * the legacy metadata keys. NOTE — the cron itself never writes these;
     * the only writer is `SubscriptionMaintenanceService::extend()`, which
     * is the admin manual extend action.
     */
    private function isInGracePeriod($subscription): bool
    {
        if (method_exists($subscription, 'isInGracePeriod')) {
            return $subscription->isInGracePeriod();
        }

        $metadata = $subscription->metadata ?? [];
        $key = isset($metadata['grace_period_ends_at']) ? 'grace_period_ends_at'
            : (isset($metadata['grace_period_expires_at']) ? 'grace_period_expires_at' : null);

        if (! $key) {
            return false;
        }

        return Carbon::parse($metadata[$key])->isAfter(now());
    }
}
