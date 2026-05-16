<?php

namespace App\Console\Commands;

use App\Constants\PauseReason;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\SubscriptionAuditLog;
use App\Notifications\SubscriptionExpiredNotification;
use App\Support\Subscriptions\SubscriptionSnapshot;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ExpireActiveSubscriptions Command
 *
 * New lifecycle: transitions ACTIVE subscriptions to PAUSED (not EXPIRED) when
 * their ends_at date has passed and no queued cycle exists to advance into.
 *
 * Respects per-cycle and legacy metadata grace periods — subscriptions with an
 * active grace period are skipped. If the grace period has also lapsed without
 * payment, the subscription is paused.
 *
 * The separate `subscriptions:advance-cycles` command handles the happy path
 * where a queued cycle exists and should be promoted when the current ends.
 */
class ExpireActiveSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire-active
                            {--dry-run : Preview changes without making them}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Pause active subscriptions whose paid window has ended and no grace/queued cycle remains';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

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
                ->chunkById(100, function ($subscriptions) use ($dryRun, $now, &$stats, $type) {
                    foreach ($subscriptions as $subscription) {
                        // 1. Skip if still in an active grace period (cycle or legacy metadata)
                        if ($this->isInGracePeriod($subscription, $now)) {
                            $stats['skipped_grace']++;

                            continue;
                        }

                        // 2. Skip if a queued next cycle exists — AdvanceSubscriptionCycles will handle it
                        if ($subscription->queuedCycle !== null) {
                            $stats['skipped_queued']++;

                            continue;
                        }

                        if ($dryRun) {
                            $this->line("  [DRY RUN] Would pause {$type} subscription #{$subscription->id} (ends_at: {$subscription->ends_at})");
                            $stats[$type]++;

                            continue;
                        }

                        try {
                            // Capture pre-pause snapshot for the audit log so the
                            // before/after diff is recorded. The actual pause runs
                            // inside the transaction below; audit write happens
                            // after the commit (non-fatal — see record()).
                            $before = SubscriptionSnapshot::capture($subscription);
                            $startedAt = microtime(true);

                            DB::transaction(function () use ($subscription) {
                                // Stamp `pause_reason = END_OF_PERIOD` so admins (and the
                                // Filament Resume action's `->visible()` predicate) can tell
                                // this from a manual mid-period pause. Manual pauses use
                                // Resume to recover lost time; auto-paused subscriptions
                                // need Extend or Renew instead.
                                $subscription->update([
                                    'status' => SessionSubscriptionStatus::PAUSED,
                                    'paused_at' => now(),
                                    'pause_reason' => PauseReason::END_OF_PERIOD,
                                ]);

                                $subscription->syncLinkedEducationUnitActiveFlag(false);
                                $this->suspendFutureSessions($subscription);

                                if ($subscription->student) {
                                    $subscription->student->notify(new SubscriptionExpiredNotification($subscription));
                                }
                            });

                            $stats[$type]++;

                            // Audit row so the pause is traceable in
                            // subscription_audit_log alongside manual pauses
                            // (which go through SubscriptionLifecycle::pause).
                            // Without this, all 13+ daily auto-pauses produced
                            // zero audit entries — the gap caught in the
                            // 2026-05-16 review (sub 706 et al.).
                            try {
                                $subscription->refresh();
                                $after = SubscriptionSnapshot::capture($subscription);
                                SubscriptionAuditLog::record([
                                    'subscription' => $subscription,
                                    'cycle_id' => $subscription->current_cycle_id,
                                    'action' => 'auto_pause_end_of_period',
                                    'source' => 'cron',
                                    'actor_user_id' => null,
                                    'before_state' => $before,
                                    'after_state' => $after,
                                    'view_state_before' => null,
                                    'view_state_after' => null,
                                    'invariant_violations' => [],
                                    'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                                ]);
                            } catch (\Throwable $auditError) {
                                Log::warning('Failed to record auto-pause audit log row', [
                                    'subscription_id' => $subscription->id,
                                    'error' => $auditError->getMessage(),
                                ]);
                            }

                            Log::info('Subscription auto-paused at end-of-cycle', [
                                'subscription_id' => $subscription->id,
                                'type' => $type,
                                'ends_at' => $subscription->ends_at->toDateTimeString(),
                            ]);
                        } catch (\Exception $e) {
                            $stats['errors']++;
                            Log::error('Failed to pause subscription', [
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
            $this->info('No subscriptions to pause.');

            return Command::SUCCESS;
        }

        $this->table(
            ['Type', 'Paused'],
            [
                ['Quran', $stats['quran']],
                ['Academic', $stats['academic']],
                ['Total', $total],
            ]
        );

        if ($stats['skipped_grace'] > 0) {
            $this->line("  Skipped (grace period): {$stats['skipped_grace']}");
        }
        if ($stats['skipped_queued'] > 0) {
            $this->line("  Skipped (queued cycle exists): {$stats['skipped_queued']}");
        }

        if ($stats['errors'] > 0) {
            $this->warn("  Errors: {$stats['errors']}");
        }

        if ($dryRun) {
            $this->warn('Dry run mode — no changes made.');
        } else {
            $this->info("Paused {$total} subscription(s).");
        }

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Suspend future scheduled/unscheduled sessions (recoverable on reactivation).
     *
     * Subscription isolation: sessions belong to THIS subscription. Do NOT
     * auto-rebind to a different active subscription (e.g. another active sub
     * for the same student/teacher) — that would violate the rule that each
     * subscription is a self-contained unit. Restoration happens symmetrically
     * via SubscriptionMaintenanceService::extend() / BaseSubscription::resume()
     * / SubscriptionRenewalService — within the same subscription only.
     */
    private function suspendFutureSessions($subscription): void
    {
        if (! method_exists($subscription, 'sessions')) {
            return;
        }

        $subscription->sessions()
            ->whereIn('status', [
                SessionStatus::SCHEDULED->value,
                SessionStatus::UNSCHEDULED->value,
                SessionStatus::READY->value,
            ])
            ->where(function ($q) {
                $q->where('scheduled_at', '>', now())
                    ->orWhereNull('scheduled_at');
            })
            ->update(['status' => SessionStatus::SUSPENDED->value]);
    }

    /**
     * Grace-period check: honors the cycle-level value first, then the legacy
     * metadata keys. Keeps behavior consistent with BaseSubscription::isInGracePeriod().
     */
    private function isInGracePeriod($subscription, Carbon $now): bool
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

        return Carbon::parse($metadata[$key])->isAfter($now);
    }
}
