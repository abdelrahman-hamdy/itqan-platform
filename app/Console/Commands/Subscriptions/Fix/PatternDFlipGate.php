<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pattern D fix — flip v2_consumption_complete=true on cycles that are
 * already IN_SYNC (sessions_used == active session_consumption count).
 *
 * Current prod shape: every cycle has 0 active consumption rows, so
 * IN_SYNC means sessions_used == 0. These are cycles with no sessions
 * yet — flipping the gate is the only mutation needed.
 *
 * Strictly cycle-scoped; no consumption rows are created or modified.
 *
 * Examples:
 *   php artisan subscriptions:fix-pattern-d-flip-gate          # dry-run
 *   php artisan subscriptions:fix-pattern-d-flip-gate --apply  # write
 *   php artisan subscriptions:fix-pattern-d-flip-gate --limit=10 --apply
 */
class PatternDFlipGate extends Command
{
    protected $signature = 'subscriptions:fix-pattern-d-flip-gate
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--limit= : Cap the number of cycles processed}
                            {--academy= : Restrict to one academy id}';

    protected $description = 'Pattern D — flip v2_consumption_complete=true on cycles with sessions_used==consumption_count.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $academy = $this->option('academy') !== null ? (int) $this->option('academy') : null;

        $query = SubscriptionCycle::query()
            ->where('v2_consumption_complete', false);

        if ($academy !== null) {
            $query->where('academy_id', $academy);
        }

        // IN_SYNC predicate: cycle.sessions_used == active consumption count.
        // We compute "active consumption count" inline with a correlated
        // subquery and compare. SQL keeps the set small without loading
        // every cycle into PHP.
        $query->whereRaw('subscription_cycles.sessions_used = (
            SELECT COUNT(*) FROM session_consumption sc
            WHERE sc.cycle_id = subscription_cycles.id
              AND sc.reversed_at IS NULL
        )');

        $total = (clone $query)->count();
        $this->info(sprintf('Pattern D candidates: %d cycle(s)', $total));

        if ($total === 0) {
            return self::SUCCESS;
        }

        $touched = 0;
        $errors = 0;
        $bar = $this->output->createProgressBar(min($total, $limit ?? $total));
        $bar->start();

        $query->orderBy('id')->chunkById(200, function ($chunk) use ($apply, $limit, &$touched, &$errors, $bar) {
            foreach ($chunk as $cycle) {
                if ($limit !== null && $touched >= $limit) {
                    return false;
                }

                try {
                    if ($apply) {
                        DB::transaction(function () use ($cycle) {
                            BackfillLog::create([
                                'bug_id' => 'cleanup-pattern-d',
                                'table_name' => 'subscription_cycles',
                                'row_id' => $cycle->id,
                                'column_name' => 'v2_consumption_complete',
                                'original_value' => 'false',
                                'new_value' => 'true',
                                'backfill_command' => 'subscriptions:fix-pattern-d-flip-gate',
                                'ran_at' => Carbon::now(),
                            ]);

                            DB::table('subscription_cycles')
                                ->where('id', $cycle->id)
                                ->update(['v2_consumption_complete' => true]);
                        });
                    }
                    $touched++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->warn(sprintf("\ncycle #%d: %s", $cycle->id, $e->getMessage()));
                }

                $bar->advance();
            }

            return true;
        });

        $bar->finish();
        $this->line('');

        $this->info(sprintf(
            '%s %d cycle(s) flipped; %d error(s).',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $touched,
            $errors,
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes. BackfillLog rows will allow individual rollback.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
