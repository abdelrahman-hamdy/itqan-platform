<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Repairs cycles where `sessions_used > total_sessions` (INV-B4 — negative
 * remaining). Bumps total_sessions = sessions_used so the cycle accounting
 * reflects what the student actually attended.
 *
 * Why "credit the student" (bump total) instead of "revoke the over-
 * consumed sessions" (cap sessions_used):
 *   - The student already attended those sessions; consumption is operational
 *     truth.
 *   - The old code path allowed over-consumption (no model-level guard).
 *     Capping would deny the student credit for work the teacher already
 *     completed; bumping accepts the historical reality.
 *   - Total_sessions is mirrored to the parent sub; the change cascades
 *     correctly via the reconciler.
 *
 * Dry-run by default. BackfillLog per row for reversal.
 */
class CycleNegativeRemaining extends Command
{
    protected $signature = 'subscriptions:fix-cycle-negative-remaining
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--limit= : Cap the number of cycles processed}';

    protected $description = 'Repair cycles where sessions_used > total_sessions by bumping total_sessions to match.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $candidates = DB::table('subscription_cycles')
            ->whereColumn('sessions_used', '>', 'total_sessions')
            ->select('id', 'subscribable_type', 'subscribable_id', 'cycle_state', 'sessions_used', 'total_sessions')
            ->orderBy('id')
            ->get();

        $this->info(sprintf('Candidates: %d cycle(s)', $candidates->count()));
        if ($candidates->isEmpty()) {
            return self::SUCCESS;
        }

        if ($limit !== null && $candidates->count() > $limit) {
            $candidates = $candidates->take($limit);
        }

        $bar = $this->output->createProgressBar($candidates->count());
        $bar->start();

        $touched = 0;
        $errors = 0;

        foreach ($candidates as $row) {
            $newTotal = (int) $row->sessions_used;
            $oldTotal = (int) $row->total_sessions;

            if (! $apply) {
                $this->line(sprintf("\n  cycle #%d (sub=%s/%d, state=%s): %d → %d total_sessions",
                    $row->id, $row->subscribable_type, $row->subscribable_id, $row->cycle_state,
                    $oldTotal, $newTotal,
                ));
                $touched++;
                $bar->advance();

                continue;
            }

            try {
                DB::transaction(function () use ($row, $oldTotal, $newTotal) {
                    BackfillLog::create([
                        'bug_id' => 'cleanup-cycle-negative-remaining',
                        'table_name' => 'subscription_cycles',
                        'row_id' => $row->id,
                        'column_name' => 'total_sessions',
                        'original_value' => (string) $oldTotal,
                        'new_value' => (string) $newTotal,
                        'backfill_command' => 'subscriptions:fix-cycle-negative-remaining',
                        'ran_at' => Carbon::now(),
                    ]);

                    DB::table('subscription_cycles')
                        ->where('id', $row->id)
                        ->update([
                            'total_sessions' => $newTotal,
                            'updated_at' => Carbon::now(),
                        ]);

                    // Mirror to parent sub if this is the active cycle.
                    if ($row->cycle_state === 'active') {
                        $parentTable = match ($row->subscribable_type) {
                            'quran_subscription' => 'quran_subscriptions',
                            'academic_subscription' => 'academic_subscriptions',
                            'course_subscription' => 'course_subscriptions',
                            default => null,
                        };
                        if ($parentTable !== null) {
                            DB::table($parentTable)
                                ->where('id', $row->subscribable_id)
                                ->update([
                                    'total_sessions' => $newTotal,
                                    'sessions_remaining' => 0,
                                    'updated_at' => Carbon::now(),
                                ]);
                        }
                    }
                });
                $touched++;
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf("\ncycle #%d: %s", $row->id, $e->getMessage()));
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        $this->info(sprintf(
            '%s %d cycle(s) processed; %d error(s).',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $touched,
            $errors,
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes. BackfillLog rows allow per-cycle rollback.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
