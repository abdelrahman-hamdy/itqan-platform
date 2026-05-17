<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phase 4 Phase-0 throwaway — flip every remaining cycle still at
 * `v2_consumption_complete = false` so the audit history is consistent
 * before the column is dropped in Phase 4 / PR 3.
 *
 * As of 2026-05-17 there are 10 such cycles on prod. They are the
 * leftover "no consumption ever produced" rows that the Phase-3 patterns
 * never touched because the gate predicate excluded them.
 *
 * Operator workflow:
 *   1. Deploy PR 1 (API + dead-branch cleanup).
 *   2. Run `php artisan subscriptions:fix-backfill-v2-complete-flag --apply`
 *      on prod (BackfillLog audit per cycle).
 *   3. Verify
 *      `SubscriptionCycle::where('v2_consumption_complete', false)
 *        ->whereNull('deleted_at')->count()` → 0.
 *   4. Deploy PR 2 — this command and the entire Pattern A/C/D family
 *      get deleted as part of that PR.
 */
class BackfillV2CompleteFlag extends Command
{
    protected $signature = 'subscriptions:fix-backfill-v2-complete-flag
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--limit= : Cap the number of cycles processed}';

    protected $description = 'Phase-0 throwaway — flip v2_consumption_complete=true on the residual cycles before the column drop.';

    private const BUG_ID = 'v2-complete-final-2026-05-17';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $query = SubscriptionCycle::query()
            ->withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('v2_consumption_complete', false);

        $total = (clone $query)->count();
        $this->info(sprintf('Residual v2_consumption_complete=false cycles: %d', $total));

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
                                'bug_id' => self::BUG_ID,
                                'table_name' => 'subscription_cycles',
                                'row_id' => $cycle->id,
                                'column_name' => 'v2_consumption_complete',
                                'original_value' => '0',
                                'new_value' => '1',
                                'backfill_command' => 'subscriptions:fix-backfill-v2-complete-flag',
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
            $this->comment('Re-run with --apply to perform the writes. BackfillLog rows allow per-cycle rollback.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
