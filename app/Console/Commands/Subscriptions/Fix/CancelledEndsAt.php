<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Backfills `ends_at = cancelled_at` on cancelled subscriptions where
 * `ends_at` still points to the original 30-day projection (i.e.
 * `ends_at > cancelled_at`).
 *
 * Surfaced when supervisors saw "ends in 7 days" badges on subs the
 * student had abandoned weeks ago. Pre-fix, the cancel paths
 * (`cancelAsDuplicateOrExpired`, `cancelDueToPaymentFailure`) never
 * touched `ends_at`, so the UI countdown kept ticking against the
 * projection.
 *
 * The model-level patch shipped alongside this command fixes forward
 * for any new cancel. This command cleans the historical population.
 *
 * Dry-run by default. --apply triggers writes. BackfillLog per row.
 */
class CancelledEndsAt extends Command
{
    protected $signature = 'subscriptions:fix-cancelled-ends-at
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--limit= : Cap the number of rows processed}';

    protected $description = 'Set ends_at = cancelled_at on cancelled subs with a future-dated ends_at projection.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $tables = ['quran_subscriptions', 'academic_subscriptions', 'course_subscriptions'];

        $totals = [];
        $grand = 0;
        foreach ($tables as $table) {
            $totals[$table] = DB::table($table)
                ->where('status', 'cancelled')
                ->whereNotNull('cancelled_at')
                ->whereNotNull('ends_at')
                ->whereColumn('ends_at', '>', 'cancelled_at')
                ->whereNull('deleted_at')
                ->count();
            $grand += $totals[$table];
        }

        $this->info(sprintf('Candidates: %d row(s) (%s)', $grand, json_encode($totals)));
        if ($grand === 0) {
            return self::SUCCESS;
        }

        $cap = $limit !== null ? min($limit, $grand) : $grand;
        $bar = $this->output->createProgressBar($cap);
        $bar->start();

        $touched = 0;
        $errors = 0;

        foreach ($tables as $table) {
            $rows = DB::table($table)
                ->where('status', 'cancelled')
                ->whereNotNull('cancelled_at')
                ->whereNotNull('ends_at')
                ->whereColumn('ends_at', '>', 'cancelled_at')
                ->whereNull('deleted_at')
                ->select('id', 'cancelled_at', 'ends_at')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                if ($limit !== null && $touched >= $limit) {
                    break 2;
                }

                try {
                    if ($apply) {
                        DB::transaction(function () use ($table, $row) {
                            BackfillLog::create([
                                'bug_id' => 'cleanup-cancelled-ends-at',
                                'table_name' => $table,
                                'row_id' => $row->id,
                                'column_name' => 'ends_at',
                                'original_value' => (string) $row->ends_at,
                                'new_value' => (string) $row->cancelled_at,
                                'backfill_command' => 'subscriptions:fix-cancelled-ends-at',
                                'ran_at' => Carbon::now(),
                            ]);

                            DB::table($table)
                                ->where('id', $row->id)
                                ->update([
                                    'ends_at' => $row->cancelled_at,
                                    'updated_at' => Carbon::now(),
                                ]);
                        });
                    }
                    $touched++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->warn(sprintf("\n%s #%d: %s", $table, $row->id, $e->getMessage()));
                }

                $bar->advance();
            }
        }

        $bar->finish();
        $this->line('');

        $this->info(sprintf(
            '%s %d row(s) processed; %d error(s).',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $touched,
            $errors,
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes. BackfillLog rows allow per-row rollback.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
