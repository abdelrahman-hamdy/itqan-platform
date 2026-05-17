<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Console\Commands\Subscriptions\Audit\ClassifyResidueDrift;
use App\Exceptions\Subscription\OverConsumptionAttempt;
use App\Models\BackfillLog;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Services\Subscription\SubscriptionReconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Reads the CSV emitted by `subscriptions:classify-residue-drift` (A.2) and
 * applies the AUTO_BACKFILL verdicts as session_consumption rows.
 *
 * For each AUTO_BACKFILL row, we INSERT a SessionConsumption with
 * source=legacy_backfill, consumption_type derived from the MA status, and
 * cycle_id from the session row. Each insert is wrapped in BackfillLog
 * with bug_id=`auto-backfill-2026-05-17`. After the per-sub batch lands we
 * call SubscriptionReconciler::sync() once to re-derive the cycle/sub
 * counters from the new active-consumption set (INV-B3).
 *
 * Sessions whose insert would push the cycle over total_sessions raise
 * OverConsumptionAttempt (INV-B4 enforced in SubscriptionConsumption);
 * we catch it, attribute the failure to the cycle, and append the session
 * to a "would-overflow" bucket that is reported at end-of-run for operator
 * review (resolve via `subscriptions:fix-final-counted-flip`).
 *
 * Dry-run by default; pass --apply to write.
 *
 * Phase B.3 of the final cleanup plan.
 */
class AutoBackfillClassifiedDrift extends Command
{
    protected $signature = 'subscriptions:fix-auto-backfill-classified-drift
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--csv=residue-classification-2026-05-17.csv : Filename under storage/app/audit/ with the classifier output}';

    protected $description = 'Read the residue-classification CSV and create session_consumption rows for AUTO_BACKFILL verdicts (B.3 of final cleanup plan).';

    private const BUG_ID = 'auto-backfill-2026-05-17';

    public function handle(SubscriptionReconciler $reconciler): int
    {
        $apply = (bool) $this->option('apply');
        $csv = (string) $this->option('csv');

        $rows = $this->loadCsv('audit/'.$csv);
        if ($rows === null) {
            $this->error(sprintf('Classifier CSV not found at storage/app/audit/%s', $csv));

            return self::FAILURE;
        }

        $autoBackfill = array_values(array_filter(
            $rows,
            fn (array $r) => $r['verdict'] === ClassifyResidueDrift::VERDICT_AUTO_BACKFILL,
        ));

        $this->info(sprintf(
            '%s: %d AUTO_BACKFILL rows out of %d total verdicts',
            $apply ? 'APPLYING' : 'DRY-RUN',
            count($autoBackfill),
            count($rows),
        ));

        if (! $apply) {
            $this->newLine();
            $this->line('Per-cycle preview (first 20):');
            $byCycle = collect($autoBackfill)->groupBy('cycle_id')->take(20);
            foreach ($byCycle as $cycleId => $set) {
                $this->line(sprintf('  cycle #%s → %d rows', $cycleId, $set->count()));
            }
            $this->newLine();
            $this->comment('Re-run with --apply to perform the writes.');

            return self::SUCCESS;
        }

        $created = 0;
        $overflow = [];
        $errors = 0;
        $reconciledSubs = [];
        $bySub = collect($autoBackfill)->groupBy('subscription_id');

        foreach ($bySub as $subId => $set) {
            try {
                $sub = QuranSubscription::query()
                    ->withoutGlobalScopes()
                    ->with('currentCycle')
                    ->find((int) $subId);

                if ($sub === null) {
                    $errors++;
                    $this->warn(sprintf('sub #%d not found, skipping %d rows', $subId, $set->count()));
                    continue;
                }

                foreach ($set as $r) {
                    try {
                        $session = QuranSession::query()
                            ->withoutGlobalScopes()
                            ->find((int) $r['session_id']);

                        if ($session === null) {
                            $errors++;
                            continue;
                        }

                        $consumptionType = $this->consumptionTypeFor($r['evidence']);
                        $consumedAt = $session->ended_at ?: $session->scheduled_at ?: now();

                        DB::transaction(function () use ($r, $session, $consumptionType, $consumedAt) {
                            $row = SessionConsumption::create([
                                'session_id' => $session->id,
                                'session_type' => 'quran_session',
                                'subscription_id' => (int) $r['subscription_id'],
                                'subscription_type' => (new QuranSubscription)->getMorphClass(),
                                'cycle_id' => (int) $r['cycle_id'],
                                'student_user_id' => (int) $r['student_id'],
                                'consumption_type' => $consumptionType,
                                'source' => SessionConsumption::SOURCE_LEGACY_BACKFILL,
                                'consumed_at' => $consumedAt,
                            ]);

                            BackfillLog::create([
                                'bug_id' => self::BUG_ID,
                                'table_name' => 'session_consumption',
                                'row_id' => $row->id,
                                'column_name' => 'INSERT',
                                'original_value' => null,
                                'new_value' => json_encode([
                                    'session_id' => $session->id,
                                    'cycle_id' => (int) $r['cycle_id'],
                                    'subscription_id' => (int) $r['subscription_id'],
                                    'consumption_type' => $consumptionType,
                                ]),
                                'backfill_command' => 'subscriptions:fix-auto-backfill-classified-drift',
                                'ran_at' => now(),
                            ]);
                        });

                        $created++;
                    } catch (OverConsumptionAttempt $e) {
                        $overflow[] = [
                            'session_id' => (int) $r['session_id'],
                            'sub_id' => (int) $r['subscription_id'],
                            'cycle_id' => (int) $r['cycle_id'],
                        ];
                    }
                }

                $reconciler->sync($sub->fresh(['currentCycle']));
                $reconciledSubs[] = (int) $subId;
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf('sub #%d ERROR: %s', $subId, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'APPLIED: %d consumption rows created, %d subs reconciled, %d would-overflow, %d errors',
            $created,
            count($reconciledSubs),
            count($overflow),
            $errors,
        ));

        if (! empty($overflow)) {
            $this->newLine();
            $this->warn(sprintf(
                'Would-overflow bucket (%d rows) — sessions that would push their cycle past total_sessions. Resolve via `subscriptions:fix-final-counted-flip --apply`:',
                count($overflow),
            ));
            $byCycle = collect($overflow)->groupBy('cycle_id');
            foreach ($byCycle as $cycleId => $set) {
                $this->line(sprintf('  cycle #%s → %d sessions', $cycleId, $set->count()));
            }
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Parse the CSV into an array of associative rows. Mirror of
     * ClassifyResidueDrift::renderCsv() column order.
     *
     * @return list<array{verdict:string,session_id:int,subscription_id:?int,cycle_id:?int,counted:int,student_id:int,scheduled_at:?string,evidence:list<string>}>|null
     */
    private function loadCsv(string $path): ?array
    {
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('local')->get($path);
        $lines = preg_split('/\r?\n/', trim((string) $contents));
        if ($lines === false || count($lines) < 2) {
            return [];
        }

        array_shift($lines); // header

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
     * Parse one CSV line accounting for a quoted evidence column that may
     * contain commas. Mirror of the simple writer in
     * ClassifyResidueDrift::renderCsv().
     *
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

    /**
     * Map MeetingAttendance.attendance_status from the evidence array to a
     * consumption_type. Default to TYPE_ATTENDED when the evidence does
     * not pin a status (no-show=paid policy still consumes the slot).
     *
     * @param  list<string>  $evidence
     */
    private function consumptionTypeFor(array $evidence): string
    {
        foreach ($evidence as $tag) {
            if (str_starts_with($tag, 'ma.status=')) {
                $status = substr($tag, strlen('ma.status='));
                return match ($status) {
                    'late' => SessionConsumption::TYPE_LATE,
                    'left' => SessionConsumption::TYPE_LEFT,
                    'absent' => SessionConsumption::TYPE_ABSENT_COUNTED,
                    default => SessionConsumption::TYPE_ATTENDED,
                };
            }
        }

        return SessionConsumption::TYPE_ATTENDED;
    }
}
