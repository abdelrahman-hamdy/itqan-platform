<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Console\Commands\Subscriptions\Audit\ClassifyResidueDrift;
use App\Models\BackfillLog;
use App\Models\QuranSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Reads the CSV emitted by `subscriptions:classify-residue-drift` (A.2) and
 * flips `subscription_counted=false` on every AUTO_FLIP_OFF verdict.
 *
 * AUTO_FLIP_OFF means the row has subscription_counted=1 but NO matching
 * MeetingAttendance, NO teacher_earning, and NO active consumption — i.e.
 * there is no evidence the session ever actually happened. The counted
 * flag is a leftover from an earlier pre-v2 path that wrote the flag
 * before status was reconciled (e.g. a scheduled session that got
 * cancelled but never had the flag cleared).
 *
 * Cycle / subscription counters are NOT touched: no consumption row was
 * ever written for these sessions, so the cycle aggregate already does
 * not reflect them. Flipping the flag just removes them from future
 * drift audits.
 *
 * BackfillLog per row records the original value (always "1") so a
 * paired rollback can restore.
 *
 * Phase B.4 of the final cleanup plan.
 */
class FlipPhantomCounted extends Command
{
    protected $signature = 'subscriptions:fix-flip-phantom-counted
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--csv=residue-classification-2026-05-17.csv : Filename under storage/app/audit/ with the classifier output}';

    protected $description = 'Flip subscription_counted=false on AUTO_FLIP_OFF verdicts from the classifier CSV (B.4 of final cleanup plan).';

    private const BUG_ID = 'phantom-flip-off-2026-05-17';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $csv = (string) $this->option('csv');

        $path = 'audit/'.$csv;
        if (! Storage::disk('local')->exists($path)) {
            $this->error(sprintf('Classifier CSV not found at storage/app/%s', $path));

            return self::FAILURE;
        }

        $rows = $this->loadCsv($path);
        $phantom = array_values(array_filter(
            $rows,
            fn (array $r) => $r['verdict'] === ClassifyResidueDrift::VERDICT_AUTO_FLIP_OFF,
        ));

        $this->info(sprintf(
            '%s: %d AUTO_FLIP_OFF rows out of %d total verdicts',
            $apply ? 'APPLYING' : 'DRY-RUN',
            count($phantom),
            count($rows),
        ));

        if (! $apply) {
            $this->newLine();
            $this->line('Sample sessions to flip (first 20):');
            foreach (array_slice($phantom, 0, 20) as $r) {
                $this->line(sprintf(
                    '  session #%d student #%d scheduled_at=%s',
                    $r['session_id'],
                    $r['student_id'],
                    $r['scheduled_at'] ?? '?',
                ));
            }
            $this->newLine();
            $this->comment('Re-run with --apply to perform the writes.');

            return self::SUCCESS;
        }

        $flipped = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($phantom as $r) {
            try {
                $session = QuranSession::query()
                    ->withoutGlobalScopes()
                    ->find((int) $r['session_id']);

                if ($session === null) {
                    $errors++;
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
                        'backfill_command' => 'subscriptions:fix-flip-phantom-counted',
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
                $this->warn(sprintf('session #%d ERROR: %s', $r['session_id'], $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'APPLIED: %d sessions flipped, %d already false, %d errors',
            $flipped,
            $skipped,
            $errors,
        ));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<array{verdict:string,session_id:int,subscription_id:?int,cycle_id:?int,counted:int,student_id:int,scheduled_at:?string,evidence:list<string>}>
     */
    private function loadCsv(string $path): array
    {
        $contents = (string) Storage::disk('local')->get($path);
        $lines = preg_split('/\r?\n/', trim($contents));
        if ($lines === false || count($lines) < 2) {
            return [];
        }
        array_shift($lines);

        $rows = [];
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $cols = $this->splitCsvLine($line);
            if (count($cols) < 8) {
                continue;
            }

            $rows[] = [
                'verdict' => $cols[0],
                'session_id' => (int) $cols[1],
                'subscription_id' => $cols[2] === '' ? null : (int) $cols[2],
                'cycle_id' => $cols[3] === '' ? null : (int) $cols[3],
                'counted' => (int) $cols[4],
                'student_id' => (int) $cols[5],
                'scheduled_at' => $cols[6] === '' ? null : $cols[6],
                'evidence' => $cols[7] === '' ? [] : explode(';', $cols[7]),
            ];
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function splitCsvLine(string $line): array
    {
        $cols = [];
        $current = '';
        $inQuotes = false;
        $length = strlen($line);

        for ($i = 0; $i < $length; $i++) {
            $ch = $line[$i];
            if ($ch === '"') {
                if ($inQuotes && $i + 1 < $length && $line[$i + 1] === '"') {
                    $current .= '"';
                    $i++;
                } else {
                    $inQuotes = ! $inQuotes;
                }
            } elseif ($ch === ',' && ! $inQuotes) {
                $cols[] = $current;
                $current = '';
            } else {
                $current .= $ch;
            }
        }
        $cols[] = $current;

        return $cols;
    }
}
