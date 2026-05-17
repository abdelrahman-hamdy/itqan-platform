<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use App\Models\BaseSubscription;
use App\Models\MeetingAttendance;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Services\Subscription\SubscriptionReconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reverse `session_consumption` rows that the legacy backfills wrongly
 * created on sessions the supervisor had matrix-excluded.
 *
 * Bug pattern (sub 1081 case, 2026-05-17):
 *   - Supervisor toggles `counts_for_subscription=false` on MA
 *   - The trait sets `session.subscription_counted=true` (stop-retries marker)
 *     but never calls useSession() — so the cycle counter is unchanged
 *   - Later, a backfill (`legacy_backfill` source) sees
 *     `subscription_counted=1 + no consumption row` and blindly creates one
 *   - The reconciler then mirrors that consumption count into
 *     `cycle.sessions_used`, over-charging the student
 *
 * Detection predicate: `session_consumption.source = 'legacy_backfill'` AND
 * the student's MA row has `counts_for_subscription = 0`.
 *
 * For each wrongly-created row:
 *   - Set reversed_at, reversed_reason, reversed_by_user_id (system)
 *   - BackfillLog the action (bug_id: matrix-excluded-rev-2026-05-17)
 * Then reconcile each affected subscription so cycle/sub counters drop.
 *
 * Dry-run by default.
 */
class ReverseMatrixExcludedBackfills extends Command
{
    protected $signature = 'subscriptions:fix-reverse-matrix-excluded-backfills
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Reverse legacy_backfill consumption rows where MA.counts_for_subscription=false (supervisor matrix-excluded).';

    private const BUG_ID = 'matrix-excluded-rev-2026-05-17';

    private const REVERSED_REASON = 'matrix_excluded_backfill';

    public function handle(SubscriptionReconciler $reconciler): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply ? 'APPLYING' : 'DRY-RUN');
        $this->newLine();

        $candidates = $this->candidates();
        $this->info(sprintf('Wrongly-created legacy_backfill rows: %d', count($candidates)));

        if (empty($candidates)) {
            $this->comment('Nothing to reverse.');

            return self::SUCCESS;
        }

        $bySub = [];
        foreach ($candidates as $r) {
            $bySub[(int) $r->subscription_type.'|'.(int) $r->subscription_id] = true;
            $bySub[$r->subscription_type.'|'.$r->subscription_id][] = $r;
        }
        // re-structure: we want a flat per-sub group
        $grouped = [];
        foreach ($candidates as $r) {
            $key = $r->subscription_type.'|'.$r->subscription_id;
            $grouped[$key][] = $r;
        }

        $this->info(sprintf('Affected subscriptions: %d', count($grouped)));
        $this->newLine();

        $reversed = 0;
        $errors = 0;
        $reconciledSubs = [];

        foreach ($grouped as $key => $rows) {
            [$subType, $subId] = explode('|', $key);
            $subId = (int) $subId;

            if (! $apply) {
                $this->line(sprintf('  would reverse %d rows on %s #%d', count($rows), $subType, $subId));

                continue;
            }

            try {
                DB::transaction(function () use ($rows, &$reversed) {
                    foreach ($rows as $r) {
                        $consumption = SessionConsumption::find($r->id);
                        if (! $consumption || $consumption->reversed_at !== null) {
                            continue;
                        }

                        $original = [
                            'reversed_at' => $consumption->reversed_at,
                            'reversed_reason' => $consumption->reversed_reason,
                            'source' => $consumption->source,
                        ];

                        $consumption->reversed_at = now();
                        $consumption->reversed_reason = self::REVERSED_REASON;
                        $consumption->reversed_by_user_id = null;
                        $consumption->save();

                        BackfillLog::create([
                            'bug_id' => self::BUG_ID,
                            'table_name' => 'session_consumption',
                            'row_id' => $consumption->id,
                            'column_name' => 'reversed_at',
                            'original_value' => json_encode($original, JSON_UNESCAPED_UNICODE),
                            'new_value' => json_encode([
                                'reversed_at' => $consumption->reversed_at->toDateTimeString(),
                                'reversed_reason' => self::REVERSED_REASON,
                            ], JSON_UNESCAPED_UNICODE),
                            'backfill_command' => 'subscriptions:fix-reverse-matrix-excluded-backfills',
                            'ran_at' => now(),
                        ]);
                        $reversed++;
                    }
                });

                $sub = $this->resolveSub($subType, $subId);
                if ($sub) {
                    try {
                        $reconciler->sync($sub);
                        $reconciledSubs[$subId] = true;
                    } catch (\Throwable $reconErr) {
                        $this->warn(sprintf('  sub #%d reversed but reconciler deferred: %s', $subId, $reconErr->getMessage()));
                    }
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf('  sub #%d ERROR: %s', $subId, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s: reversed=%d reconciled_subs=%d errors=%d',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $reversed,
            count($reconciledSubs),
            $errors,
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<\stdClass>
     */
    private function candidates(): array
    {
        return DB::select("
            SELECT sc.id, sc.session_id, sc.session_type, sc.subscription_id, sc.subscription_type, sc.cycle_id, sc.student_user_id
            FROM session_consumption sc
            INNER JOIN meeting_attendances ma
                ON ma.session_id = sc.session_id
                AND ma.session_type IN ('individual', 'group', 'trial', 'academic', 'interactive')
                AND ma.user_id = sc.student_user_id
                AND ma.user_type = 'student'
            WHERE sc.source = 'legacy_backfill'
              AND sc.reversed_at IS NULL
              AND ma.counts_for_subscription = 0
            ORDER BY sc.subscription_id, sc.id
        ");
    }

    private function resolveSub(string $morphAlias, int $id): ?BaseSubscription
    {
        return match ($morphAlias) {
            'quran_subscription' => QuranSubscription::withoutGlobalScopes()->with('currentCycle')->find($id),
            'academic_subscription' => \App\Models\AcademicSubscription::withoutGlobalScopes()->with('currentCycle')->find($id),
            default => null,
        };
    }
}
