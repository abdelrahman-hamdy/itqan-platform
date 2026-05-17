<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use App\Models\QuranSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Blanket sweep that flips `subscription_counted=false` on every quran_session
 * whose flag is still 1 but has NO active session_consumption row (and is not
 * soft-deleted). Closes all remaining residue cohorts in one pass:
 * MATRIX_EXCLUDED, NEEDS_REVIEW, overflow-cycle excess, and any new drift
 * that landed between classifier runs.
 *
 * Why this is safe:
 *  - The v2 path counts via session_consumption rows, NOT this flag.
 *  - The flag survives only as input to LegacyCountingDrift audit predicates;
 *    flipping it silences the audit noise without touching cycle aggregates.
 *  - Every flip writes a BackfillLog row with original_value='1', so the sweep
 *    is fully reversible per session.
 *
 * Step 3 of the 2026-05-17 final cleanup plan.
 */
class FinalCountedFlip extends Command
{
    protected $signature = 'subscriptions:fix-final-counted-flip
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--chunk=500 : Rows per batch when iterating candidates}';

    protected $description = 'Flip subscription_counted=false on every quran_session with flag=1 and no active consumption (Step 3 of final cleanup plan).';

    private const BUG_ID = 'final-counted-flip-2026-05-17';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $chunk = max(50, (int) $this->option('chunk'));

        $candidateSql = <<<'SQL'
            SELECT qs.id
            FROM quran_sessions qs
            WHERE qs.subscription_counted = 1
              AND qs.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM session_consumption sc
                  WHERE sc.session_id = qs.id
                    AND sc.session_type = 'quran_session'
                    AND sc.reversed_at IS NULL
              )
            ORDER BY qs.id
        SQL;

        $candidates = DB::select($candidateSql);
        $total = count($candidates);

        $this->info(sprintf(
            '%s: %d candidate sessions to flip subscription_counted → 0',
            $apply ? 'APPLYING' : 'DRY-RUN',
            $total,
        ));

        if ($total === 0) {
            $this->info('Nothing to do.');

            return self::SUCCESS;
        }

        if (! $apply) {
            $sample = array_slice($candidates, 0, 20);
            $this->newLine();
            $this->line('Sample session IDs (first 20):');
            foreach ($sample as $row) {
                $this->line(sprintf('  session #%d', (int) $row->id));
            }
            $this->newLine();
            $this->comment('Re-run with --apply to perform the writes.');

            return self::SUCCESS;
        }

        $flipped = 0;
        $skipped = 0;
        $errors = 0;

        foreach (array_chunk($candidates, $chunk) as $batch) {
            $ids = array_map(static fn ($row) => (int) $row->id, $batch);

            foreach ($ids as $sessionId) {
                try {
                    $session = QuranSession::query()
                        ->withoutGlobalScopes()
                        ->find($sessionId);

                    if ($session === null) {
                        $skipped++;
                        continue;
                    }

                    if (! $session->subscription_counted) {
                        $skipped++;
                        continue;
                    }

                    DB::transaction(function () use ($session) {
                        BackfillLog::create([
                            'bug_id' => self::BUG_ID,
                            'table_name' => 'quran_sessions',
                            'row_id' => $session->id,
                            'column_name' => 'subscription_counted',
                            'original_value' => '1',
                            'new_value' => '0',
                            'backfill_command' => 'subscriptions:fix-final-counted-flip',
                            'ran_at' => now(),
                        ]);

                        QuranSession::query()
                            ->withoutGlobalScopes()
                            ->whereKey($session->id)
                            ->update(['subscription_counted' => false]);
                    });

                    $flipped++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->warn(sprintf('session #%d ERROR: %s', $sessionId, $e->getMessage()));
                }
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'APPLIED: %d sessions flipped, %d already false / missing, %d errors',
            $flipped,
            $skipped,
            $errors,
        ));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
