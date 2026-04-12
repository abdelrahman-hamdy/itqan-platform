<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Notifications\SubscriptionExpiredNotification;
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
                            DB::transaction(function () use ($subscription) {
                                $subscription->update([
                                    'status' => SessionSubscriptionStatus::PAUSED,
                                    'paused_at' => now(),
                                ]);

                                $this->deactivateLinkedEntities($subscription);
                                $this->suspendFutureSessions($subscription);

                                if ($subscription->student) {
                                    $subscription->student->notify(new SubscriptionExpiredNotification($subscription));
                                }
                            });

                            $stats[$type]++;

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
     * Deactivate linked circle (Quran) or lesson (Academic).
     */
    private function deactivateLinkedEntities($subscription): void
    {
        if ($subscription instanceof QuranSubscription && $subscription->education_unit_id) {
            $subscription->educationUnit?->update(['is_active' => false]);
        }

        if ($subscription instanceof AcademicSubscription) {
            $subscription->lesson?->update(['is_active' => false]);
        }
    }

    /**
     * Suspend future scheduled/unscheduled sessions (recoverable on reactivation).
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
