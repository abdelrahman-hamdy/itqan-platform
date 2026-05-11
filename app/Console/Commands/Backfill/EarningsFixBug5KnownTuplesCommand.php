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
 * The system-wide 37-tuple sweep is intentionally OUT OF SCOPE here — see
 * the follow-up note under Bug #5 in the bug-doc.
 *
 *   php artisan earnings:fix-bug5-known-tuples --dry-run
 *   php artisan earnings:fix-bug5-known-tuples --apply
 *   php artisan earnings:fix-bug5-known-tuples --rollback
 */
class EarningsFixBug5KnownTuplesCommand extends BaseBackfillCommand
{
    protected $signature = 'earnings:fix-bug5-known-tuples
                            {--dry-run : Print what would change without mutating}
                            {--apply : Apply the dedup + post the negative-amount reversal}
                            {--rollback : Restore FQCN rows and soft-delete the reversal rows logged earlier}';

    protected $description = 'Bug #5: dedup the documented FQCN+alias earnings tuples for sessions 3001/3002 (profile 79)';

    protected const BUG_ID = 'bug_5';

    protected const COMMAND_NAME = 'earnings:fix-bug5-known-tuples';

    private const REVERSAL_METHOD = 'backfill_dedup_reversal';

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
                foreach ($fqcnRows as $fqcnRow) {
                    $this->logChange($fqcnRow, 'deleted_at', null, 'soft_delete');

                    $fqcnRow->delete();

                    $reversal = TeacherEarning::create([
                        'academy_id' => $fqcnRow->academy_id,
                        'teacher_type' => $fqcnRow->teacher_type,
                        'teacher_id' => $fqcnRow->teacher_id,
                        'session_type' => $keep->session_type,
                        'session_id' => $fqcnRow->session_id,
                        'amount' => -1 * (float) $fqcnRow->amount,
                        'calculation_method' => self::REVERSAL_METHOD,
                        'rate_snapshot' => null,
                        'calculation_metadata' => [
                            'bug_id' => self::BUG_ID,
                            'original_row_id' => $fqcnRow->id,
                            'reason' => 'fqcn_alias_duplicate',
                            'tuple' => [
                                'session_id' => $sessionId,
                                'profile_id' => $profileId,
                            ],
                        ],
                        'earning_month' => $fqcnRow->earning_month,
                        'session_completed_at' => $fqcnRow->session_completed_at,
                        'calculated_at' => now(),
                        'is_finalized' => true,
                        'is_disputed' => false,
                    ]);

                    $this->logChange($reversal, 'amount', null, $reversal->amount);
                }
            });

            $touched++;
        }

        $this->newLine();
        $this->info(sprintf('Done. Tuples processed: %d. Skipped: %d.', $touched, $skipped));

        return self::SUCCESS;
    }

    /**
     * Undo a prior --apply run: restore each soft-deleted FQCN row and
     * soft-delete its paired negative-amount reversal row.
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

                if ($log->column_name === 'amount') {
                    TeacherEarning::where('id', $log->row_id)->delete();
                }

                $log->update(['reversed_at' => now()]);
            }
        });

        $this->info(sprintf('Rolled back %d backfill row(s).', $rows->count()));

        return self::SUCCESS;
    }
}
