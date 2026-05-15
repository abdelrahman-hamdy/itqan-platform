<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\SubscriptionPaymentStatus;
use App\Models\BackfillLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Backfills `payment_status='failed'` on subscriptions whose `status='cancelled'`
 * but whose `payment_status` is still `pending`.
 *
 * Historical state created by pre-Feb-2026 cancel paths (duplicate-cleanup,
 * student-cancel-pending, migration-era duplicate sweep) that flipped the
 * sub to CANCELLED without touching `payment_status`. The forward bug-fix
 * shipped in commit 59648539 (Feb 2026), but historical rows weren't
 * touched. This command brings them in line with the canonical post-cancel
 * shape.
 *
 * The sub is already terminal — there's no state machine to walk, no audit
 * row to emit. We just update one column per row + log a BackfillLog entry
 * for individual reversibility.
 *
 * Dry-run by default. --apply triggers writes.
 */
class CancelledPaymentStatus extends Command
{
    protected $signature = 'subscriptions:fix-cancelled-payment-status
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--limit= : Cap the number of rows processed}';

    protected $description = 'Backfill payment_status=failed on cancelled subs whose payment_status was left as pending.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $tables = ['quran_subscriptions', 'academic_subscriptions', 'course_subscriptions'];

        $total = 0;
        foreach ($tables as $table) {
            $total += DB::table($table)
                ->where('status', 'cancelled')
                ->where('payment_status', 'pending')
                ->whereNull('deleted_at')
                ->count();
        }

        $this->info(sprintf('Candidates: %d row(s)', $total));
        if ($total === 0) {
            return self::SUCCESS;
        }
        if ($limit !== null && $limit < $total) {
            $total = $limit;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $touched = 0;
        $errors = 0;

        foreach ($tables as $table) {
            $ids = DB::table($table)
                ->where('status', 'cancelled')
                ->where('payment_status', 'pending')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->pluck('id');

            foreach ($ids as $id) {
                if ($limit !== null && $touched >= $limit) {
                    break 2;
                }

                try {
                    if ($apply) {
                        DB::transaction(function () use ($table, $id) {
                            BackfillLog::create([
                                'bug_id' => 'cleanup-cancelled-payment-status',
                                'table_name' => $table,
                                'row_id' => $id,
                                'column_name' => 'payment_status',
                                'original_value' => 'pending',
                                'new_value' => SubscriptionPaymentStatus::FAILED->value,
                                'backfill_command' => 'subscriptions:fix-cancelled-payment-status',
                                'ran_at' => Carbon::now(),
                            ]);

                            DB::table($table)
                                ->where('id', $id)
                                ->update([
                                    'payment_status' => SubscriptionPaymentStatus::FAILED->value,
                                    'updated_at' => Carbon::now(),
                                ]);
                        });
                    }
                    $touched++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->warn(sprintf("\n%s #%d: %s", $table, $id, $e->getMessage()));
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
