<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\SessionStatus;
use App\Models\BackfillLog;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off targeted cleanup for the 7 lie-state subs surfaced by the
 * 2026-05-16 `subscriptions:audit-pending-with-scheduled` run on prod.
 *
 * Two passes:
 *   1. CANCEL — future SCHEDULED sessions anchored to the listed cycle
 *      (which is either the pending current cycle or a saturated /
 *      legacy-overflow archived cycle). The student never paid quota
 *      for these. Status flip only; no consumption row touched (none
 *      exists by precondition).
 *   2. RE-ANCHOR — sub 903 has 3 future sessions on a paid + archived
 *      cycle (522) with 6 unused sessions. Per P13, the unused quota
 *      should have rolled into the successor pending cycle (1051) at
 *      renewal; that pre-dated the deploy. Move the 3 session rows'
 *      `subscription_cycle_id` to 1051 and bump
 *      `cycle 1051.total_sessions` / `carryover_sessions` by 3.
 *
 * Both passes write per-row BackfillLog entries for rollback. Dry-run
 * by default.
 */
class UndeservedPendingSessions extends Command
{
    protected $signature = 'subscriptions:fix-undeserved-pending-sessions
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'One-off cleanup for the 7 lie-state subs from 2026-05-16: cancel future scheduled sessions anchored to unpaid/saturated cycles; re-anchor 3 paid-quota sessions on sub 903.';

    private const BUG_ID = 'undeserved-pending-sessions-2026-05-16';

    /** @var array<int, array{sub:int, cycle:int}> */
    private const CANCEL_TARGETS = [
        ['sub' => 562, 'cycle' => 652],
        ['sub' => 772, 'cycle' => 762],
        ['sub' => 417, 'cycle' => 790],
        ['sub' => 910, 'cycle' => 535],
        ['sub' => 643, 'cycle' => 970],
        ['sub' => 892, 'cycle' => 1074],
    ];

    /** @var array{sub:int, from_cycle:int, to_cycle:int} */
    private const REANCHOR_TARGET = [
        'sub' => 903,
        'from_cycle' => 522,
        'to_cycle' => 1051,
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $totalCancelled = 0;
        $totalReanchored = 0;
        $errors = 0;

        $this->info($apply ? 'APPLYING' : 'DRY-RUN');
        $this->newLine();

        foreach (self::CANCEL_TARGETS as $target) {
            try {
                $totalCancelled += $this->cancelOnCycle($target['sub'], $target['cycle'], $apply);
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf('sub #%d cycle #%d ERROR: %s', $target['sub'], $target['cycle'], $e->getMessage()));
            }
        }

        try {
            $totalReanchored = $this->reanchor(self::REANCHOR_TARGET, $apply);
        } catch (\Throwable $e) {
            $errors++;
            $this->warn(sprintf('reanchor ERROR: %s', $e->getMessage()));
        }

        $this->newLine();
        $this->info(sprintf(
            '%s: cancelled=%d reanchored=%d errors=%d',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $totalCancelled,
            $totalReanchored,
            $errors,
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function cancelOnCycle(int $subId, int $cycleId, bool $apply): int
    {
        $sub = QuranSubscription::withoutGlobalScopes()->find($subId);
        if ($sub === null) {
            $this->warn(sprintf('  sub #%d not found — skipped', $subId));

            return 0;
        }

        $sessions = $sub->sessions()
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('subscription_cycle_id', $cycleId)
            ->where('scheduled_at', '>', now())
            ->get(['id', 'scheduled_at', 'status', 'subscription_cycle_id']);

        if ($sessions->isEmpty()) {
            $this->line(sprintf('  sub #%d cycle #%d: 0 sessions match — skipped', $subId, $cycleId));

            return 0;
        }

        $this->line(sprintf('  sub #%d cycle #%d: %d future SCHEDULED sessions to CANCEL', $subId, $cycleId, $sessions->count()));
        foreach ($sessions as $s) {
            $this->line(sprintf('    session #%d  %s', $s->id, $s->scheduled_at->toDateTimeString()));
        }

        if (! $apply) {
            return $sessions->count();
        }

        $touched = 0;
        DB::transaction(function () use ($sessions, $sub, &$touched) {
            foreach ($sessions as $s) {
                BackfillLog::create([
                    'bug_id' => self::BUG_ID,
                    'table_name' => 'quran_sessions',
                    'row_id' => $s->id,
                    'column_name' => 'status',
                    'original_value' => $s->status,
                    'new_value' => SessionStatus::CANCELLED->value,
                    'backfill_command' => 'subscriptions:fix-undeserved-pending-sessions',
                    'ran_at' => now(),
                ]);

                $sub->sessions()
                    ->whereKey($s->id)
                    ->update(['status' => SessionStatus::CANCELLED->value]);
                $touched++;
            }
        });

        return $touched;
    }

    /**
     * @param  array{sub:int, from_cycle:int, to_cycle:int}  $target
     */
    private function reanchor(array $target, bool $apply): int
    {
        $sub = QuranSubscription::withoutGlobalScopes()->find($target['sub']);
        if ($sub === null) {
            $this->warn(sprintf('  reanchor sub #%d not found — skipped', $target['sub']));

            return 0;
        }

        $toCycle = SubscriptionCycle::query()->find($target['to_cycle']);
        if ($toCycle === null) {
            $this->warn(sprintf('  reanchor target cycle #%d not found — skipped', $target['to_cycle']));

            return 0;
        }

        $sessions = $sub->sessions()
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('subscription_cycle_id', $target['from_cycle'])
            ->where('scheduled_at', '>', now())
            ->get(['id', 'scheduled_at', 'subscription_cycle_id']);

        if ($sessions->isEmpty()) {
            $this->line(sprintf('  reanchor sub #%d: 0 sessions on cycle #%d — skipped', $target['sub'], $target['from_cycle']));

            return 0;
        }

        $count = $sessions->count();
        $this->newLine();
        $this->line(sprintf('  reanchor sub #%d: move %d sessions from cycle #%d → #%d AND bump cycle #%d total+=%d carryover+=%d',
            $target['sub'], $count, $target['from_cycle'], $target['to_cycle'], $target['to_cycle'], $count, $count));
        foreach ($sessions as $s) {
            $this->line(sprintf('    session #%d  %s', $s->id, $s->scheduled_at->toDateTimeString()));
        }
        $this->line(sprintf('    cycle #%d before: total_sessions=%d carryover_sessions=%d',
            $toCycle->id, $toCycle->total_sessions, $toCycle->carryover_sessions ?? 0));

        if (! $apply) {
            return $count;
        }

        DB::transaction(function () use ($sessions, $sub, $toCycle, $target, $count) {
            foreach ($sessions as $s) {
                BackfillLog::create([
                    'bug_id' => self::BUG_ID,
                    'table_name' => 'quran_sessions',
                    'row_id' => $s->id,
                    'column_name' => 'subscription_cycle_id',
                    'original_value' => (string) $target['from_cycle'],
                    'new_value' => (string) $target['to_cycle'],
                    'backfill_command' => 'subscriptions:fix-undeserved-pending-sessions',
                    'ran_at' => now(),
                ]);

                $sub->sessions()
                    ->whereKey($s->id)
                    ->update(['subscription_cycle_id' => $target['to_cycle']]);
            }

            BackfillLog::create([
                'bug_id' => self::BUG_ID,
                'table_name' => 'subscription_cycles',
                'row_id' => $toCycle->id,
                'column_name' => 'total_sessions_carryover_sessions',
                'original_value' => json_encode([
                    'total_sessions' => $toCycle->total_sessions,
                    'carryover_sessions' => $toCycle->carryover_sessions ?? 0,
                ], JSON_UNESCAPED_SLASHES),
                'new_value' => json_encode([
                    'total_sessions' => $toCycle->total_sessions + $count,
                    'carryover_sessions' => ($toCycle->carryover_sessions ?? 0) + $count,
                ], JSON_UNESCAPED_SLASHES),
                'backfill_command' => 'subscriptions:fix-undeserved-pending-sessions',
                'ran_at' => now(),
            ]);

            $toCycle->total_sessions += $count;
            $toCycle->carryover_sessions = ($toCycle->carryover_sessions ?? 0) + $count;
            $toCycle->save();
        });

        return $count;
    }
}
