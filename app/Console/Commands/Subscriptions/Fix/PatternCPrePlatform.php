<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pattern C fix — cycles whose `sessions_used > 0` but where no evidence
 * for the consumption exists (no `subscription_counted=true` session, no
 * `session_consumption` row, no `meeting_attendance` row). These are
 * cycles that carried over pre-platform usage from the manual spreadsheet
 * import.
 *
 * Decision: preserve the stored aggregate, do NOT synthesize consumption
 * rows (no evidence). Flip the v2_consumption_complete gate and record
 * the preservation in cycle.metadata so future audits don't re-flag.
 *
 * Strictly cycle-scoped. No session_consumption writes.
 */
class PatternCPrePlatform extends Command
{
    protected $signature = 'subscriptions:fix-pattern-c-pre-platform
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--limit= : Cap the number of cycles processed}
                            {--academy= : Restrict to one academy id}';

    protected $description = 'Pattern C — preserve sessions_used aggregate on cycles with no per-session evidence (pre-platform).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $academy = $this->option('academy') !== null ? (int) $this->option('academy') : null;

        // Pattern C predicate: sessions_used > 0 AND no consumption rows AND
        // no quran/academic session with subscription_counted=true on the
        // same cycle. v2_consumption_complete must still be false (we're
        // gating on the unprocessed set).
        $query = SubscriptionCycle::query()
            ->where('v2_consumption_complete', false)
            ->where('sessions_used', '>', 0)
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM session_consumption sc
                WHERE sc.cycle_id = subscription_cycles.id
                  AND sc.reversed_at IS NULL
            )')
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM quran_sessions qs
                WHERE qs.subscription_cycle_id = subscription_cycles.id
                  AND qs.subscription_counted = 1
            )')
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM academic_sessions a
                WHERE a.subscription_cycle_id = subscription_cycles.id
                  AND a.subscription_counted = 1
            )');

        if ($academy !== null) {
            $query->where('academy_id', $academy);
        }

        $total = (clone $query)->count();
        $this->info(sprintf('Pattern C candidates: %d cycle(s)', $total));

        if ($total === 0) {
            return self::SUCCESS;
        }

        $touched = 0;
        $errors = 0;
        $bar = $this->output->createProgressBar(min($total, $limit ?? $total));
        $bar->start();

        $query->orderBy('id')->chunkById(100, function ($chunk) use ($apply, $limit, &$touched, &$errors, $bar) {
            foreach ($chunk as $cycle) {
                if ($limit !== null && $touched >= $limit) {
                    return false;
                }

                $preservedValue = (int) $cycle->sessions_used;
                $existingMetadata = $cycle->metadata ?? [];
                $newMetadata = array_merge($existingMetadata, [
                    'pre_platform_consumption_preserved' => true,
                    'preserved_value' => $preservedValue,
                    'preserved_at' => Carbon::now()->toAtomString(),
                    'preserved_by_command' => 'subscriptions:fix-pattern-c-pre-platform',
                ]);

                try {
                    if ($apply) {
                        DB::transaction(function () use ($cycle, $newMetadata, $existingMetadata) {
                            BackfillLog::create([
                                'bug_id' => 'cleanup-pattern-c',
                                'table_name' => 'subscription_cycles',
                                'row_id' => $cycle->id,
                                'column_name' => 'metadata+v2_consumption_complete',
                                'original_value' => json_encode([
                                    'metadata' => $existingMetadata,
                                    'v2_consumption_complete' => false,
                                ]),
                                'new_value' => json_encode([
                                    'metadata' => $newMetadata,
                                    'v2_consumption_complete' => true,
                                ]),
                                'backfill_command' => 'subscriptions:fix-pattern-c-pre-platform',
                                'ran_at' => Carbon::now(),
                            ]);

                            DB::table('subscription_cycles')
                                ->where('id', $cycle->id)
                                ->update([
                                    'metadata' => json_encode($newMetadata),
                                    'v2_consumption_complete' => true,
                                ]);
                        });
                    } else {
                        $this->line(sprintf(
                            '  cycle #%d (sub=%s/%d): preserve sessions_used=%d via metadata + flip gate',
                            $cycle->id,
                            $cycle->subscribable_type,
                            $cycle->subscribable_id,
                            $preservedValue,
                        ));
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
            '%s %d cycle(s) processed; %d error(s).',
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
