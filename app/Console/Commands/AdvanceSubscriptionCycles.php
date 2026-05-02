<?php

namespace App\Console\Commands;

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AdvanceSubscriptionCycles Command
 *
 * Promotes queued subscription cycles to active when the current cycle ends.
 *
 * For each active subscription whose current cycle's ends_at has passed AND
 * which has a queued cycle waiting:
 *
 * 1. Archive the current cycle (cycle_state = archived)
 * 2. Promote the queued cycle (cycle_state = active, current_cycle_id updated)
 * 3. Sync subscription columns to the new cycle's values
 * 4. For Academic subscriptions: create the next batch of UNSCHEDULED sessions
 *    on the existing lesson
 *
 * Scheduled hourly via routes/console.php.
 */
class AdvanceSubscriptionCycles extends Command
{
    protected $signature = 'subscriptions:advance-cycles
                            {--dry-run : Preview changes without making them}';

    protected $description = 'Promote queued subscription cycles to active when the current cycle ends';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Scanning for subscriptions with queued cycles ready to promote...');
        $this->newLine();

        $stats = [
            'quran' => 0,
            'academic' => 0,
            'errors' => 0,
        ];

        $types = [
            'quran' => QuranSubscription::class,
            'academic' => AcademicSubscription::class,
        ];

        foreach ($types as $type => $modelClass) {
            $modelClass::withoutGlobalScopes()
                ->where('status', SessionSubscriptionStatus::ACTIVE)
                ->whereNotNull('current_cycle_id')
                ->whereHas('currentCycle', function ($q) {
                    $q->whereNotNull('ends_at')->where('ends_at', '<=', now());
                })
                ->whereHas('queuedCycle')
                ->with(['currentCycle', 'queuedCycle'])
                ->chunkById(50, function ($subscriptions) use ($dryRun, &$stats, $type) {
                    foreach ($subscriptions as $subscription) {
                        if ($dryRun) {
                            $this->line("  [DRY RUN] Would advance {$type} subscription #{$subscription->id}");
                            $stats[$type]++;

                            continue;
                        }

                        try {
                            $this->promote($subscription);
                            $stats[$type]++;
                        } catch (\Exception $e) {
                            $stats['errors']++;
                            Log::error('Failed to advance subscription cycle', [
                                'subscription_id' => $subscription->id,
                                'type' => $type,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });
        }

        $total = $stats['quran'] + $stats['academic'];

        if ($total === 0) {
            $this->info('No cycles to advance.');

            return Command::SUCCESS;
        }

        $this->table(
            ['Type', 'Advanced'],
            [
                ['Quran', $stats['quran']],
                ['Academic', $stats['academic']],
                ['Total', $total],
            ]
        );

        if ($stats['errors'] > 0) {
            $this->warn("  Errors: {$stats['errors']}");
        }

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function promote($subscription): void
    {
        DB::transaction(function () use ($subscription) {
            $currentCycle = $subscription->currentCycle;
            $queuedCycle = $subscription->queuedCycle;

            if (! $currentCycle || ! $queuedCycle) {
                return;
            }

            // Archive the current cycle
            $currentCycle->update([
                'cycle_state' => SubscriptionCycle::STATE_ARCHIVED,
                'archived_at' => now(),
            ]);

            // Promote the queued cycle
            $queuedCycle->update([
                'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            ]);

            // Sync subscription columns to the new cycle, clearing any
            // transient per-cycle flags that would otherwise leak from the
            // old cycle into the new one (reminder timestamps, pause state).
            $subscription->update([
                'current_cycle_id' => $queuedCycle->id,
                'starts_at' => $queuedCycle->starts_at,
                'ends_at' => $queuedCycle->ends_at,
                'next_billing_date' => $queuedCycle->ends_at,
                'billing_cycle' => BillingCycle::from($queuedCycle->billing_cycle),
                'total_sessions' => $queuedCycle->total_sessions,
                'sessions_remaining' => $queuedCycle->total_sessions,
                'sessions_used' => 0,
                'total_sessions_scheduled' => 0,
                'total_sessions_completed' => 0,
                'total_sessions_missed' => 0,
                'progress_percentage' => 0,
                'status' => SessionSubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'cycle_count' => $queuedCycle->cycle_number,
                'renewal_reminder_sent_at' => null,
                'paused_at' => null,
                'pause_reason' => null,
            ]);

            // Academic: create the next batch of UNSCHEDULED sessions
            if ($subscription instanceof AcademicSubscription
                && method_exists($subscription, 'createLessonAndSessionsForCycle')) {
                $subscription->createLessonAndSessionsForCycle($queuedCycle);
            }

            Log::info('Subscription cycle advanced', [
                'subscription_id' => $subscription->id,
                'from_cycle_id' => $currentCycle->id,
                'to_cycle_id' => $queuedCycle->id,
                'cycle_number' => $queuedCycle->cycle_number,
            ]);
        });
    }
}
