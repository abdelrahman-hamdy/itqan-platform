<?php

namespace App\Console\Commands\Backfill;

use App\Models\BackfillLog;
use App\Models\TeacherEarning;
use Illuminate\Support\Facades\DB;

/**
 * Targeted Bug #5 backfill for the documented duplicate earnings tuples.
 *
 * Scope: ONLY the documented case from docs/subscription-bugs-found.md —
 * teacher محمد البيه (user 357, profile 79), sessions 3001 + 3002, each
 * holding two alive earning rows (one FQCN-form, one alias-form), net
 * overpay 200 EGP.
 *
 * The system-wide 37-tuple sweep is handled by
 * `2026_05_11_120000_normalize_teacher_earnings_session_type` (which also
 * installs the unique index + write-side triggers that prevent recurrence).
 *
 * Audit shape mirrors the migration: soft-delete the FQCN duplicate and
 * append a `dedup_history` entry onto the surviving alias row's
 * `calculation_metadata`. No reversal row is inserted (the composite unique
 * index in the migration would refuse the insert anyway, and a paired
 * negative reversal would over-correct the teacher's monthly total —
 * soft-deleting the FQCN row alone restores it to the correct figure).
 *
 *   php artisan earnings:fix-bug5-known-tuples --dry-run
 *   php artisan earnings:fix-bug5-known-tuples --apply
 *   php artisan earnings:fix-bug5-known-tuples --rollback
 */
class EarningsFixBug5KnownTuplesCommand extends BaseBackfillCommand
{
    protected $signature = 'earnings:fix-bug5-known-tuples
                            {--dry-run : Print what would change without mutating}
                            {--apply : Soft-delete the FQCN duplicate and append a dedup_history audit entry to the alias survivor}
                            {--rollback : Restore FQCN rows soft-deleted earlier (dedup_history audit stays as a trail of the attempt)}';

    protected $description = 'Bug #5: dedup the documented FQCN+alias earnings tuples for sessions 3001/3002 (profile 79)';

    protected const BUG_ID = 'bug_5';

    protected const COMMAND_NAME = 'earnings:fix-bug5-known-tuples';

    /** [session_id, teacher_profile_id] tuples in scope (documented). */
    private const TARGET_TUPLES = [
        [3001, 79],
        [3002, 79],
    ];

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        $dryRun = (bool) $this->option('dry-run') || ! $this->option('apply');

        if ($dryRun) {
            $this->warn('Dry-run mode (default). Pass --apply to mutate.');
        }

        $touched = 0;
        $skipped = 0;

        foreach (self::TARGET_TUPLES as [$sessionId, $profileId]) {
            $rows = TeacherEarning::withTrashed()
                ->where('session_id', $sessionId)
                ->where('teacher_id', $profileId)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get();

            if ($rows->count() < 2) {
                $this->line(sprintf(
                    '  session=%d profile=%d: %d alive row(s) — already cleaned, skipping',
                    $sessionId,
                    $profileId,
                    $rows->count(),
                ));
                $skipped++;

                continue;
            }

            // Keep the alias-form row (the one matching what the codebase
            // writes today via $session->getMorphClass()). Soft-delete any
            // FQCN-form siblings.
            $aliasRows = $rows->filter(fn (TeacherEarning $r) => ! str_contains((string) $r->session_type, '\\'));
            $fqcnRows = $rows->filter(fn (TeacherEarning $r) => str_contains((string) $r->session_type, '\\'));

            if ($aliasRows->isEmpty() || $fqcnRows->isEmpty()) {
                $this->line(sprintf(
                    '  session=%d profile=%d: unexpected shape (alias=%d, fqcn=%d) — skipping',
                    $sessionId,
                    $profileId,
                    $aliasRows->count(),
                    $fqcnRows->count(),
                ));
                $skipped++;

                continue;
            }

            $keep = $aliasRows->first();
            $this->info(sprintf(
                '  session=%d profile=%d: keep id=%d (%s, %s EGP), reverse %d FQCN row(s)',
                $sessionId,
                $profileId,
                $keep->id,
                $keep->session_type,
                $keep->amount,
                $fqcnRows->count(),
            ));

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($fqcnRows, $sessionId, $profileId, $keep) {
                $now = now();
                foreach ($fqcnRows as $fqcnRow) {
                    $this->logChange($fqcnRow, 'deleted_at', null, 'soft_delete');
                    $fqcnRow->delete();

                    // Append a dedup_history entry to the surviving alias row.
                    // No reversal insert — the soft-delete alone returns the
                    // teacher's monthly total to the correct figure, and a
                    // negative-amount sibling would over-correct.
                    $existing = $keep->fresh();
                    $history = $existing->calculation_metadata['dedup_history'] ?? [];
                    $history[] = [
                        'bug_id' => self::BUG_ID,
                        'reversed_row_id' => $fqcnRow->id,
                        'reversed_amount' => (float) $fqcnRow->amount,
                        'command' => self::COMMAND_NAME,
                        'reversed_at' => $now->toIso8601String(),
                        'tuple' => [
                            'session_id' => $sessionId,
                            'profile_id' => $profileId,
                        ],
                    ];

                    $metadata = $existing->calculation_metadata ?? [];
                    $metadata['dedup_history'] = $history;
                    $existing->update(['calculation_metadata' => $metadata]);
                }
            });

            $touched++;
        }

        $this->newLine();
        $this->info(sprintf('Done. Tuples processed: %d. Skipped: %d.', $touched, $skipped));

        return self::SUCCESS;
    }

    /**
     * Undo a prior --apply run: restore each soft-deleted FQCN row.
     *
     * The `dedup_history` audit entry on the surviving alias row is
     * intentionally left in place — it's an append-only trail of what
     * happened. Restoring the FQCN row will collide with the composite
     * unique index installed by the 2026-05-11 migration if that migration
     * has already run; rollback in that case is only meaningful when the
     * migration is also rolled back.
     */
    private function rollback(): int
    {
        $rows = BackfillLog::query()
            ->where('bug_id', self::BUG_ID)
            ->where('backfill_command', self::COMMAND_NAME)
            ->whereNull('reversed_at')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No prior --apply run logged. Nothing to roll back.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $log) {
                if ($log->column_name === 'deleted_at' && $log->new_value === 'soft_delete') {
                    TeacherEarning::withTrashed()->where('id', $log->row_id)->restore();
                }

                $log->update(['reversed_at' => now()]);
            }
        });

        $this->info(sprintf('Rolled back %d backfill row(s).', $rows->count()));

        return self::SUCCESS;
    }
}
