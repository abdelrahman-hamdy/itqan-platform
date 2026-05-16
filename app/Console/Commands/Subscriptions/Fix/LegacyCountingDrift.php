<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionReconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill the residual drift between the legacy `subscription_counted` flag
 * and the v2 `session_consumption` rows. Unblocks the Phase 4 schema-drop.
 *
 * Three actions:
 *   1. SAFE backfill — for cycles where adding the drift consumption rows
 *      keeps cycle.sessions_used <= total_sessions. Creates one consumption
 *      row per drift session (source=legacy_backfill). Reconciler is NOT
 *      auto-invoked — runs at the end per affected subscription.
 *   2. OVERFLOW report — for cycles where adding the rows would exceed
 *      total_sessions. These need admin judgment (was preset wrong? was
 *      a session over-counted by legacy path? did admin extend package?).
 *   3. PENDING-V2 flip — flips v2_consumption_complete=true on the 3
 *      brand-new active+paid cycles that pattern-a hasn't covered yet
 *      (0 consumption rows + 0 sessions used = trivially v2-clean).
 *
 * Out of scope (separate concerns, not touched here):
 *   - meeting_attendances drift (morph-type mismatch in audit query —
 *     attendance uses 'individual'/'group', consumption uses 'quran_session').
 *   - Drift sessions with NO `quran_subscription_id` (orphaned, can't
 *     derive a cycle to attribute to).
 *   - teacher_earnings (never touched — earnings key off session.status,
 *     not consumption rows).
 *
 * BackfillLog row per write. Dry-run by default.
 */
class LegacyCountingDrift extends Command
{
    protected $signature = 'subscriptions:fix-legacy-counting-drift
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Backfill session_consumption rows for the residual legacy-flag drift; unblocks Phase 4 schema-drop.';

    private const BUG_ID = 'legacy-counting-drift-2026-05-17';

    public function handle(SubscriptionReconciler $reconciler): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'APPLYING' : 'DRY-RUN');
        $this->newLine();

        $driftRows = $this->fetchDriftSessions();
        $orphans = collect($driftRows)->filter(fn ($r) => $r->subscription_id === null || $r->cycle_id === null);
        $attributable = collect($driftRows)->filter(fn ($r) => $r->subscription_id !== null && $r->cycle_id !== null);

        $byCycle = $attributable->groupBy('cycle_id');

        $safeRows = [];
        $overflowSummary = [];

        foreach ($byCycle as $cycleId => $rows) {
            $cycle = SubscriptionCycle::find((int) $cycleId);
            if (! $cycle) {
                continue;
            }
            $newUsed = (int) $cycle->sessions_used + $rows->count();
            if ($newUsed <= (int) $cycle->total_sessions) {
                foreach ($rows as $r) {
                    $safeRows[] = $r;
                }
            } else {
                $overflowSummary[] = [
                    'cycle_id' => (int) $cycleId,
                    'sub_id' => (int) $rows->first()->subscription_id,
                    'state' => $cycle->cycle_state,
                    'used_before' => (int) $cycle->sessions_used,
                    'drift' => $rows->count(),
                    'used_after' => $newUsed,
                    'total' => (int) $cycle->total_sessions,
                ];
            }
        }

        $safeBySub = collect($safeRows)->groupBy('subscription_id');

        $pendingV2 = SubscriptionCycle::query()
            ->where(function ($q) {
                $q->where('v2_consumption_complete', false)->orWhereNull('v2_consumption_complete');
            })
            ->get(['id', 'cycle_state', 'payment_status', 'sessions_used', 'total_sessions']);

        // === Report ===
        $this->info(sprintf(
            'DRIFT SESSIONS: %d total, %d attributable, %d orphans (no sub/cycle — skipped)',
            count($driftRows),
            $attributable->count(),
            $orphans->count(),
        ));
        $this->info(sprintf(
            'SAFE BACKFILL: %d sessions across %d cycles, %d subscriptions',
            count($safeRows),
            $safeBySub->keys()->count() > 0 ? $safeBySub->map(fn ($g) => $g->pluck('cycle_id')->unique())->flatten()->unique()->count() : 0,
            $safeBySub->keys()->count(),
        ));
        $this->info(sprintf('OVERFLOW (admin review): %d cycles', count($overflowSummary)));
        $this->info(sprintf('PENDING-V2 FLIP: %d cycles', $pendingV2->count()));
        $this->newLine();

        if (! empty($overflowSummary)) {
            $this->warn('Overflow cycles (NOT backfilled — admin must decide preset/over-count/package-extension):');
            $this->table(
                ['cycle_id', 'sub_id', 'state', 'used_before', 'drift', 'used_after', 'total'],
                $overflowSummary,
            );
        }

        if ($pendingV2->count() > 0) {
            $this->line('Pending v2 cycles to flip:');
            foreach ($pendingV2 as $c) {
                $this->line(sprintf('  cycle #%d state=%s pay=%s used=%d/%d', $c->id, $c->cycle_state, $c->payment_status, $c->sessions_used, $c->total_sessions));
            }
            $this->newLine();
        }

        $this->info('Per-affected-subscription summary (top 10 by drift count):');
        $perSub = $safeBySub->map(fn ($g, $sid) => [
            'sub_id' => (int) $sid,
            'drift_count' => $g->count(),
            'cycles' => $g->pluck('cycle_id')->unique()->implode(','),
        ])->sortByDesc('drift_count')->take(10)->values();
        $this->table(['sub_id', 'drift_count', 'cycles'], $perSub->toArray());

        if (! $apply) {
            $this->newLine();
            $this->comment('DRY-RUN complete. No writes. Re-run with --apply.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Applying...');
        $createdRows = 0;
        $errors = 0;
        $reconciledSubs = [];

        foreach ($safeBySub as $subId => $rows) {
            try {
                DB::transaction(function () use ($subId, $rows, &$createdRows) {
                    foreach ($rows as $r) {
                        $consumedAt = $r->subscription_counted_at ?: $r->scheduled_at ?: now();

                        $consumption = SessionConsumption::create([
                            'session_id' => $r->session_id,
                            'session_type' => 'quran_session',
                            'subscription_id' => $r->subscription_id,
                            'subscription_type' => 'quran_subscription',
                            'cycle_id' => $r->cycle_id,
                            'student_user_id' => $r->student_id,
                            'consumption_type' => 'attended',
                            'source' => 'legacy_backfill',
                            'consumed_at' => $consumedAt,
                        ]);

                        BackfillLog::create([
                            'bug_id' => self::BUG_ID,
                            'table_name' => 'session_consumption',
                            'row_id' => $consumption->id,
                            'column_name' => 'INSERT',
                            'original_value' => null,
                            'new_value' => json_encode([
                                'session_id' => $r->session_id,
                                'cycle_id' => $r->cycle_id,
                                'subscription_id' => $r->subscription_id,
                            ]),
                            'backfill_command' => 'subscriptions:fix-legacy-counting-drift',
                            'ran_at' => now(),
                        ]);

                        $createdRows++;
                    }
                });

                $sub = QuranSubscription::withoutGlobalScopes()->with('currentCycle')->find((int) $subId);
                if ($sub) {
                    $reconciler->sync($sub);
                    $reconciledSubs[] = (int) $subId;
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf('sub #%d ERROR: %s', $subId, $e->getMessage()));
            }
        }

        foreach ($pendingV2 as $cycle) {
            try {
                BackfillLog::create([
                    'bug_id' => self::BUG_ID,
                    'table_name' => 'subscription_cycles',
                    'row_id' => $cycle->id,
                    'column_name' => 'v2_consumption_complete',
                    'original_value' => (string) ($cycle->v2_consumption_complete ?? 'NULL'),
                    'new_value' => '1',
                    'backfill_command' => 'subscriptions:fix-legacy-counting-drift',
                    'ran_at' => now(),
                ]);
                $cycle->v2_consumption_complete = true;
                $cycle->save();
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf('cycle #%d v2 flip ERROR: %s', $cycle->id, $e->getMessage()));
            }
        }

        $this->info(sprintf(
            'APPLIED: %d consumption rows created, %d subs reconciled, %d v2 flags flipped, %d errors',
            $createdRows,
            count($reconciledSubs),
            $pendingV2->count(),
            $errors,
        ));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Fetch all quran sessions with legacy_counted=true and no matching
     * active consumption row.
     *
     * @return list<\stdClass>
     */
    private function fetchDriftSessions(): array
    {
        return DB::select("
            SELECT qs.id AS session_id,
                   qs.quran_subscription_id AS subscription_id,
                   qs.subscription_cycle_id AS cycle_id,
                   qs.student_id AS student_id,
                   qs.subscription_counted_at,
                   qs.scheduled_at
            FROM quran_sessions qs
            WHERE qs.subscription_counted = 1
              AND qs.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM session_consumption sc
                  WHERE sc.session_id = qs.id
                    AND sc.session_type = 'quran_session'
                    AND sc.reversed_at IS NULL
              )
        ");
    }
}
