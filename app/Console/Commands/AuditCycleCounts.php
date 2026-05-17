<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\BackfillLog;
use App\Models\BaseSubscription;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Audit and (optionally) repair drift between SubscriptionCycle.sessions_used
 * and the count of cycle-anchored sessions that were actually counted toward
 * the subscription.
 *
 * Default mode is dry-run (report only). Pass `--apply` to persist
 * corrections, optionally narrowed with `--subscription=<id>` so an operator
 * can review the dry-run output and fix one subscription at a time.
 *
 * The "expected" count for a cycle is:
 *   COUNT(session_consumption WHERE cycle_id = cycle.id
 *                              AND reversed_at IS NULL)
 *
 * This matches the v2 canonical writer (SubscriptionConsumption::record)
 * and the reconciler's recount path (INV-B3).
 */
class AuditCycleCounts extends Command
{
    protected $signature = 'subscriptions:audit-cycle-counts
                            {--apply : Persist corrections (default is dry-run)}
                            {--subscription= : Limit audit to a specific subscription id}
                            {--type= : Limit to quran|academic (omit for both)}';

    protected $description = 'Detect and optionally repair drift between subscription_cycles.sessions_used and the actual counted-session count';

    /**
     * BackfillLog stamp so `subscriptions:diagnose-cycle-drift` can detect
     * re-drift (a cycle previously repaired by --apply that has drifted again).
     */
    private const BACKFILL_BUG_ID = 'cycle_counter_drift_2026_05_11';

    private const BACKFILL_COMMAND_NAME = 'subscriptions:audit-cycle-counts';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $type = $this->option('type');
        $subscriptionId = $this->option('subscription') !== null
            ? (int) $this->option('subscription')
            : null;

        $this->info($apply
            ? 'APPLY MODE — drift will be corrected.'
            : 'DRY RUN — pass --apply to persist corrections.');
        $this->newLine();

        $totalDrift = 0;
        $totalRepaired = 0;
        $rows = [];

        $modelByType = [
            'quran' => [
                'class' => QuranSubscription::class,
                'session' => QuranSession::class,
                'morph' => (new QuranSubscription)->getMorphClass(),
            ],
            'academic' => [
                'class' => AcademicSubscription::class,
                'session' => AcademicSession::class,
                'morph' => (new AcademicSubscription)->getMorphClass(),
            ],
        ];

        foreach ($modelByType as $tag => $cfg) {
            if ($type !== null && $type !== $tag) {
                continue;
            }

            $query = $cfg['class']::query();
            if ($subscriptionId !== null) {
                $query->where('id', $subscriptionId);
            }

            $query->orderBy('id')->chunkById(100, function ($subscriptions) use ($apply, $cfg, $tag, &$rows, &$totalDrift, &$totalRepaired) {
                $cyclesBySub = SubscriptionCycle::query()
                    ->where('subscribable_type', $cfg['morph'])
                    ->whereIn('subscribable_id', $subscriptions->pluck('id'))
                    ->orderBy('cycle_number')
                    ->get()
                    ->groupBy(fn ($c) => (int) $c->subscribable_id);

                $expectedByCycle = $this->loadExpectedCountsForCycles(
                    $cyclesBySub->flatten(1)->pluck('id')->all(),
                );

                foreach ($subscriptions as $subscription) {
                    $cycles = $cyclesBySub->get($subscription->id, collect());

                    foreach ($cycles as $cycle) {
                        $expected = $expectedByCycle[(int) $cycle->id] ?? 0;
                        $stored = (int) $cycle->sessions_used;

                        if ($expected === $stored) {
                            continue;
                        }

                        $totalDrift++;
                        $rows[] = [
                            $tag,
                            $subscription->id,
                            $cycle->id,
                            $cycle->cycle_number,
                            $cycle->cycle_state,
                            $stored,
                            $expected,
                            $expected - $stored,
                        ];

                        if ($apply) {
                            $this->repairCycle($subscription, $cycle, $expected);
                            $totalRepaired++;
                        }
                    }
                }
            });
        }

        if (empty($rows)) {
            $this->info('No drift detected.');

            return self::SUCCESS;
        }

        $this->table(
            ['Type', 'Sub', 'Cycle ID', 'Cycle #', 'State', 'Stored', 'Expected', 'Delta'],
            $rows,
        );

        $this->newLine();
        $this->info("Drifted cycles: {$totalDrift}");
        if ($apply) {
            $this->info("Repaired: {$totalRepaired}");
        } else {
            $this->warn('Re-run with --apply to persist corrections.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, int> cycle_id => count of active SessionConsumption rows
     */
    private function loadExpectedCountsForCycles(array $cycleIds): array
    {
        if (empty($cycleIds)) {
            return [];
        }

        return SessionConsumption::query()
            ->whereIn('cycle_id', $cycleIds)
            ->whereNull('reversed_at')
            ->groupBy('cycle_id')
            ->selectRaw('cycle_id, COUNT(*) as total')
            ->pluck('total', 'cycle_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    private function repairCycle(BaseSubscription $subscription, SubscriptionCycle $cycle, int $expected): void
    {
        DB::transaction(function () use ($subscription, $cycle, $expected) {
            // Lock subscription before cycle to match useSession's order and
            // avoid deadlocks against live counting.
            /** @var BaseSubscription $lockedSub */
            $lockedSub = $subscription::lockForUpdate()->find($subscription->id);
            /** @var SubscriptionCycle $lockedCycle */
            $lockedCycle = SubscriptionCycle::lockForUpdate()->find($cycle->id);
            if (! $lockedCycle) {
                return;
            }

            $previousUsed = (int) $lockedCycle->sessions_used;
            $delta = $expected - $previousUsed;

            BackfillLog::record(
                self::BACKFILL_BUG_ID,
                self::BACKFILL_COMMAND_NAME,
                $lockedCycle,
                'sessions_used',
                $previousUsed,
                $expected,
            );

            $lockedCycle->update([
                'sessions_used' => $expected,
                'sessions_completed' => $expected,
            ]);

            // Mirror onto the subscription row only when the repaired cycle
            // is currently active — the subscription's sessions_used /
            // sessions_remaining columns are a snapshot of the active cycle.
            if ($lockedSub && (int) $lockedCycle->id === (int) $subscription->current_cycle_id) {
                $newRemaining = max(0, (int) $lockedSub->total_sessions - $expected);
                $totalForProgress = $expected + $newRemaining;
                $progress = $totalForProgress > 0
                    ? round(($expected / $totalForProgress) * 100, 2)
                    : 0;
                if ($newRemaining <= 0) {
                    $progress = 100;
                }

                $metadata = $lockedSub->metadata ?? [];
                if ($newRemaining > 0 && ! empty($metadata['sessions_exhausted'])) {
                    unset($metadata['sessions_exhausted'], $metadata['sessions_exhausted_at']);
                }
                if ($newRemaining <= 0) {
                    $metadata['sessions_exhausted'] = true;
                    $metadata['sessions_exhausted_at'] = now()->toDateTimeString();
                }

                $lockedSub->update([
                    'sessions_used' => $expected,
                    'sessions_remaining' => $newRemaining,
                    'total_sessions_completed' => $expected,
                    'progress_percentage' => $progress,
                    'metadata' => $metadata ?: null,
                ]);
            }

            Log::info('AuditCycleCounts repaired drift', [
                'subscription_id' => $subscription->id,
                'cycle_id' => $cycle->id,
                'cycle_number' => $cycle->cycle_number,
                'cycle_state' => $cycle->cycle_state,
                'previous_sessions_used' => $cycle->sessions_used,
                'new_sessions_used' => $expected,
                'delta' => $delta,
            ]);
        });
    }
}
