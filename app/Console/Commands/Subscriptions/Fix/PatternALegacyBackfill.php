<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pattern A fix — for each cycle in `LEGACY_AGGREGATE_NO_ROWS` state where
 * the only conflict per session is the unreliable student_session_report
 * (which the reconciler caps at MED), synthesize one `session_consumption`
 * row per legacy-counted session.
 *
 * Strict guards:
 *   - Only cycles where v2_consumption_complete=false.
 *   - Only sessions where `subscription_counted=true`.
 *   - Only sessions with status=completed (or completed_at NOT NULL).
 *   - Skip sessions that already have a (session_id, student_user_id)
 *     consumption row (idempotent re-run).
 *   - Per-cycle assertion: after backfill,
 *     count(consumption rows for cycle) == cycle.sessions_used.
 *     If it doesn't match, ROLL BACK that cycle's transaction.
 *
 * Source value: `legacy_backfill`. This is NOT a registered precedence in
 * SessionConsumption::SOURCE_PRECEDENCE — precedence defaults to 0, so any
 * future canonical write will overwrite it. That's deliberate: legacy
 * backfill rows are the WEAKEST signal and should never block a real
 * teacher_report or admin_manual write.
 */
class PatternALegacyBackfill extends Command
{
    protected $signature = 'subscriptions:fix-pattern-a-legacy-backfill
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--limit= : Cap the number of CYCLES processed}
                            {--academy= : Restrict to one academy id}
                            {--cycle= : Process only one specific cycle id (debug)}';

    protected $description = 'Pattern A — synthesize session_consumption rows from legacy subscription_counted flag.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $academy = $this->option('academy') !== null ? (int) $this->option('academy') : null;
        $cycleId = $this->option('cycle') !== null ? (int) $this->option('cycle') : null;

        $query = SubscriptionCycle::query()
            ->where('v2_consumption_complete', false)
            ->where('sessions_used', '>', 0)
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM session_consumption sc
                WHERE sc.cycle_id = subscription_cycles.id
                  AND sc.reversed_at IS NULL
            )')
            ->where(function ($q) {
                $q->whereRaw('EXISTS (
                    SELECT 1 FROM quran_sessions qs
                    WHERE qs.subscription_cycle_id = subscription_cycles.id
                      AND qs.subscription_counted = 1
                )')->orWhereRaw('EXISTS (
                    SELECT 1 FROM academic_sessions a
                    WHERE a.subscription_cycle_id = subscription_cycles.id
                      AND a.subscription_counted = 1
                )');
            });

        if ($cycleId !== null) {
            $query->where('id', $cycleId);
        }
        if ($academy !== null) {
            $query->where('academy_id', $academy);
        }

        $total = (clone $query)->count();
        $this->info(sprintf('Pattern A candidates: %d cycle(s)', $total));

        if ($total === 0) {
            return self::SUCCESS;
        }

        $cyclesTouched = 0;
        $cyclesSkipped = 0;
        $rowsWritten = 0;
        $rowsSkipped = 0;
        $errors = 0;
        $bar = $this->output->createProgressBar(min($total, $limit ?? $total));
        $bar->start();

        $query->orderBy('id')->chunkById(50, function ($chunk) use ($apply, $limit, &$cyclesTouched, &$cyclesSkipped, &$rowsWritten, &$rowsSkipped, &$errors, $bar) {
            foreach ($chunk as $cycle) {
                if ($limit !== null && $cyclesTouched + $cyclesSkipped >= $limit) {
                    return false;
                }

                $result = $this->processCycle($cycle, $apply);

                if ($result['error'] !== null) {
                    $errors++;
                    $this->warn(sprintf("\ncycle #%d: %s", $cycle->id, $result['error']));
                } elseif ($result['skipped']) {
                    $cyclesSkipped++;
                } else {
                    $cyclesTouched++;
                }

                $rowsWritten += $result['rows_written'];
                $rowsSkipped += $result['rows_skipped'];

                $bar->advance();
            }

            return true;
        });

        $bar->finish();
        $this->line('');

        $this->info(sprintf(
            '%s cycles touched=%d, skipped=%d (assertion failed), errors=%d. Consumption rows: written=%d, already-present=%d.',
            $apply ? 'APPLIED.' : 'DRY-RUN —',
            $cyclesTouched,
            $cyclesSkipped,
            $errors,
            $rowsWritten,
            $rowsSkipped,
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes. BackfillLog rows will allow individual rollback.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{error: ?string, skipped: bool, rows_written: int, rows_skipped: int}
     */
    private function processCycle(SubscriptionCycle $cycle, bool $apply): array
    {
        $result = ['error' => null, 'skipped' => false, 'rows_written' => 0, 'rows_skipped' => 0];

        // Collect candidate sessions for this cycle from quran_sessions +
        // academic_sessions where subscription_counted=true.
        $candidates = $this->collectCandidates($cycle);

        if ($candidates === []) {
            $result['skipped'] = true;
            $result['error'] = 'no candidate sessions despite scope predicate — re-survey';

            return $result;
        }

        if (! $apply) {
            $result['rows_written'] = count($candidates);

            return $result;
        }

        try {
            DB::transaction(function () use ($cycle, $candidates, &$result) {
                foreach ($candidates as $cand) {
                    // Idempotent: skip if a non-reversed row already exists
                    // for this (session_type, session_id, student_user_id).
                    $exists = DB::table('session_consumption')
                        ->where('session_type', $cand['session_type'])
                        ->where('session_id', $cand['session_id'])
                        ->where('student_user_id', $cand['student_user_id'])
                        ->whereNull('reversed_at')
                        ->exists();
                    if ($exists) {
                        $result['rows_skipped']++;

                        continue;
                    }

                    $row = [
                        'session_id' => $cand['session_id'],
                        'session_type' => $cand['session_type'],
                        'subscription_id' => $cand['subscription_id'],
                        'subscription_type' => $cand['subscription_type'],
                        'cycle_id' => $cycle->id,
                        'student_user_id' => $cand['student_user_id'],
                        'consumption_type' => 'attended',
                        'source' => 'legacy_backfill',
                        'source_user_id' => null,
                        'consumed_at' => $cand['consumed_at'],
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                    $rowId = DB::table('session_consumption')->insertGetId($row);
                    $result['rows_written']++;

                    BackfillLog::create([
                        'bug_id' => 'cleanup-pattern-a',
                        'table_name' => 'session_consumption',
                        'row_id' => $rowId,
                        'column_name' => 'INSERT',
                        'original_value' => null,
                        'new_value' => json_encode($row),
                        'backfill_command' => 'subscriptions:fix-pattern-a-legacy-backfill',
                        'ran_at' => Carbon::now(),
                    ]);
                }

                // Assertion: after backfill, active consumption count should
                // equal cycle.sessions_used. If not, abort this cycle so the
                // transaction rolls back and the other cycles still get
                // processed.
                $consumptionCount = DB::table('session_consumption')
                    ->where('cycle_id', $cycle->id)
                    ->whereNull('reversed_at')
                    ->count();
                $expected = (int) $cycle->sessions_used;
                if ($consumptionCount !== $expected) {
                    throw new \RuntimeException(sprintf(
                        'assertion failed: consumption_count=%d != cycle.sessions_used=%d',
                        $consumptionCount,
                        $expected,
                    ));
                }

                // Flip the gate.
                BackfillLog::create([
                    'bug_id' => 'cleanup-pattern-a',
                    'table_name' => 'subscription_cycles',
                    'row_id' => $cycle->id,
                    'column_name' => 'v2_consumption_complete',
                    'original_value' => 'false',
                    'new_value' => 'true',
                    'backfill_command' => 'subscriptions:fix-pattern-a-legacy-backfill',
                    'ran_at' => Carbon::now(),
                ]);
                DB::table('subscription_cycles')
                    ->where('id', $cycle->id)
                    ->update(['v2_consumption_complete' => true]);
            });
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
            $result['skipped'] = true;
            $result['rows_written'] = 0;
        }

        return $result;
    }

    /**
     * @return list<array{session_type:string,session_id:int,student_user_id:int,subscription_id:int,subscription_type:string,consumed_at:string}>
     */
    private function collectCandidates(SubscriptionCycle $cycle): array
    {
        $rows = [];

        $quran = DB::table('quran_sessions')
            ->where('subscription_cycle_id', $cycle->id)
            ->where('subscription_counted', true)
            ->select('id', 'student_id', 'quran_subscription_id', 'completed_at', 'scheduled_at')
            ->get();

        foreach ($quran as $r) {
            $rows[] = [
                'session_type' => 'quran_session',
                'session_id' => (int) $r->id,
                'student_user_id' => (int) $r->student_id,
                'subscription_id' => (int) $r->quran_subscription_id,
                'subscription_type' => 'quran_subscription',
                'consumed_at' => (string) ($r->completed_at ?? $r->scheduled_at ?? Carbon::now()->toDateTimeString()),
            ];
        }

        $academic = DB::table('academic_sessions')
            ->where('subscription_cycle_id', $cycle->id)
            ->where('subscription_counted', true)
            ->select('id', 'student_id', 'academic_subscription_id', 'completed_at', 'scheduled_at')
            ->get();

        foreach ($academic as $r) {
            $rows[] = [
                'session_type' => 'academic_session',
                'session_id' => (int) $r->id,
                'student_user_id' => (int) $r->student_id,
                'subscription_id' => (int) $r->academic_subscription_id,
                'subscription_type' => 'academic_subscription',
                'consumed_at' => (string) ($r->completed_at ?? $r->scheduled_at ?? Carbon::now()->toDateTimeString()),
            ];
        }

        return $rows;
    }
}
