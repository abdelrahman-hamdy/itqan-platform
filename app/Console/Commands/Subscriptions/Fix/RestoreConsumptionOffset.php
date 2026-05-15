<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\AcademicSubscription;
use App\Models\BackfillLog;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionReconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Restores cycle.sessions_used for cycles whose offset metadata was
 * present at cleanup time but where the reconciler's old recount logic
 * subsequently overwrote sessions_used with just the active consumption
 * row count (losing the offset).
 *
 * Targets cycles where:
 *   - v2_consumption_complete = true (so the reconciler's recount path
 *     was active and could have run)
 *   - metadata.unaccounted_sessions_used is set (Pattern A shortfall),
 *     OR
 *   - metadata.pre_platform_consumption_preserved = true (Pattern C)
 *   - AND stored sessions_used != consumption_count + offset
 *     (i.e. the bug already wrote the wrong value)
 *
 * Per-cycle: compute the correct value, log to BackfillLog, write the fix.
 * After this lands AND the reconciler patch ships (commit on the same PR),
 * subsequent mutations preserve the offset correctly.
 *
 * Dry-run by default. --apply triggers writes.
 *
 * Originally surfaced by sub 556 — admin-preset 5 pre-consumed sessions
 * lost on 2026-05-15 cleanup.
 */
class RestoreConsumptionOffset extends Command
{
    protected $signature = 'subscriptions:fix-restore-consumption-offset
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--limit= : Cap the number of cycles processed}
                            {--cycle= : Process only one cycle id (debug)}';

    protected $description = 'Restore cycle.sessions_used where the buggy reconciler wiped out a documented metadata offset.';

    public function handle(SubscriptionReconciler $reconciler): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $cycleId = $this->option('cycle') !== null ? (int) $this->option('cycle') : null;

        $candidates = $this->collectCandidates($cycleId);

        $this->info(sprintf('Candidates: %d cycle(s)', count($candidates)));
        if (count($candidates) === 0) {
            return self::SUCCESS;
        }

        if ($limit !== null) {
            $candidates = array_slice($candidates, 0, $limit);
        }

        $bar = $this->output->createProgressBar(count($candidates));
        $bar->start();

        $stats = ['fixed' => 0, 'already_correct' => 0, 'errored' => 0, 'subs_reconciled' => 0];

        foreach ($candidates as $row) {
            $cycle = SubscriptionCycle::find($row->id);
            if ($cycle === null) {
                $stats['errored']++;
                $bar->advance();

                continue;
            }

            $consumptionCount = DB::table('session_consumption')
                ->where('cycle_id', $cycle->id)
                ->whereNull('reversed_at')
                ->count();

            $metadata = $cycle->metadata ?? [];
            $offset = 0;
            if (isset($metadata['unaccounted_sessions_used'])) {
                $offset += (int) $metadata['unaccounted_sessions_used'];
            }
            if (! empty($metadata['pre_platform_consumption_preserved']) && isset($metadata['preserved_value'])) {
                $offset += (int) $metadata['preserved_value'];
            }

            $expected = $consumptionCount + $offset;
            $current = (int) $cycle->sessions_used;

            if ($current === $expected) {
                $stats['already_correct']++;
                $bar->advance();

                continue;
            }

            if (! $apply) {
                $this->line(sprintf(
                    "\n  cycle #%d: stored=%d → restored=%d (consumption=%d + offset=%d)",
                    $cycle->id, $current, $expected, $consumptionCount, $offset,
                ));
                $stats['fixed']++;
                $bar->advance();

                continue;
            }

            try {
                DB::transaction(function () use ($cycle, $current, $expected) {
                    BackfillLog::create([
                        'bug_id' => 'cleanup-restore-offset',
                        'table_name' => 'subscription_cycles',
                        'row_id' => $cycle->id,
                        'column_name' => 'sessions_used',
                        'original_value' => (string) $current,
                        'new_value' => (string) $expected,
                        'backfill_command' => 'subscriptions:fix-restore-consumption-offset',
                        'ran_at' => Carbon::now(),
                    ]);
                    DB::table('subscription_cycles')
                        ->where('id', $cycle->id)
                        ->update(['sessions_used' => $expected]);
                });
                $stats['fixed']++;

                // If this is the current cycle of an active sub, re-mirror
                // the parent. The patched reconciler now respects the offset.
                $subClass = match ($cycle->subscribable_type) {
                    'quran_subscription' => QuranSubscription::class,
                    'academic_subscription' => AcademicSubscription::class,
                    default => null,
                };
                if ($subClass !== null) {
                    $sub = $subClass::find($cycle->subscribable_id);
                    if ($sub !== null) {
                        try {
                            $reconciler->syncWithoutInvariantCheck($sub);
                            $stats['subs_reconciled']++;
                        } catch (\Throwable $e) {
                            // Mirror failed; cycle is correct; skip silently.
                        }
                    }
                }
            } catch (\Throwable $e) {
                $stats['errored']++;
                $this->warn(sprintf("\ncycle #%d: %s", $cycle->id, $e->getMessage()));
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        $this->info(sprintf(
            '%s fixed=%d, already_correct=%d, subs_reconciled=%d, errored=%d',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $stats['fixed'],
            $stats['already_correct'],
            $stats['subs_reconciled'],
            $stats['errored'],
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes. BackfillLog rows allow per-cycle rollback.');
        }

        return $stats['errored'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<object>
     */
    private function collectCandidates(?int $cycleId): array
    {
        $q = SubscriptionCycle::query()
            ->where('v2_consumption_complete', true)
            ->where(function ($q) {
                $q->whereRaw("JSON_EXTRACT(metadata, '$.unaccounted_sessions_used') IS NOT NULL")
                  ->orWhereRaw("JSON_EXTRACT(metadata, '$.pre_platform_consumption_preserved') = TRUE");
            });

        if ($cycleId !== null) {
            $q->where('id', $cycleId);
        }

        return $q->select('id')->orderBy('id')->get()->all();
    }
}
