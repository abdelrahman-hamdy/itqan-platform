<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\SessionStatus;
use App\Models\BackfillLog;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Services\Subscription\SubscriptionReconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Restores quran sessions that were wrongly cancelled by
 * `subscriptions:fix-stuck-scheduled-sessions` on 2026-05-16. The morph-name
 * gate in that command compared `meeting_attendances.session_type` against
 * the session's morph alias (`quran_session`), but MA actually stores
 * `individual` / `group` / `trial`. As a result, 9 sessions that DID have
 * real attendance rows were treated as eligible-to-cancel.
 *
 * For each affected row:
 *   - status: cancelled → completed
 *   - subscription_counted: → true
 *   - clears cancelled_at / cancellation_reason / cancellation_type / cancelled_by
 *   - ended_at: scheduled_at + duration (capped at now())
 *   - creates one session_consumption row (source=auto_attendance) when missing
 *   - reconciles the parent subscription
 *
 * Identification predicate (cheap, no joins needed at apply-time):
 *   - status = 'cancelled'
 *   - backfill_log has a row with bug_id='stuck-scheduled-2026-05-16'
 *     for this session, where column_name='status', new_value='cancelled'
 *   - meeting_attendances row exists with session_type in (individual, group, trial)
 *
 * BackfillLog row per write under bug_id `stuck-scheduled-restore-2026-05-17`.
 * Dry-run by default.
 */
class RestoreMisCancelledStuckSessions extends Command
{
    protected $signature = 'subscriptions:fix-restore-mis-cancelled-stuck-sessions
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Restore quran sessions wrongly cancelled by the 2026-05-16 stuck-scheduled fix (morph-name gate bug).';

    private const BUG_ID = 'stuck-scheduled-restore-2026-05-17';

    private const SOURCE_BUG_ID = 'stuck-scheduled-2026-05-16';

    public function handle(SubscriptionReconciler $reconciler): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply ? 'APPLYING' : 'DRY-RUN');
        $this->newLine();

        $ids = $this->candidateIds();
        $this->info(sprintf('Candidates: %d sessions', count($ids)));

        if (empty($ids)) {
            $this->comment('Nothing to restore.');

            return self::SUCCESS;
        }

        $restored = 0;
        $consumptionRows = 0;
        $errors = 0;
        $skippedAlreadyOk = 0;
        $reconciledSubs = [];

        foreach ($ids as $id) {
            try {
                $session = QuranSession::query()->withoutGlobalScopes()->find((int) $id);
                if (! $session) {
                    continue;
                }

                $currentStatus = $session->status instanceof \BackedEnum ? $session->status->value : (string) $session->status;
                if ($currentStatus !== SessionStatus::CANCELLED->value) {
                    $skippedAlreadyOk++;
                    $this->line(sprintf('  session #%d already %s — skipping', $session->id, $currentStatus));

                    continue;
                }

                if (! $apply) {
                    $this->line(sprintf(
                        '  + session #%d would restore (sub #%s, cycle #%s, scheduled %s)',
                        $session->id,
                        $session->quran_subscription_id ?? '-',
                        $session->subscription_cycle_id ?? '-',
                        $session->scheduled_at?->toDateTimeString() ?? '-',
                    ));

                    continue;
                }

                DB::transaction(function () use ($session, &$restored, &$consumptionRows) {
                    $endedAt = $this->resolveEndedAt($session);
                    $originalCancellation = [
                        'status' => $session->status instanceof \BackedEnum ? $session->status->value : (string) $session->status,
                        'cancelled_at' => optional($session->cancelled_at)->toDateTimeString(),
                        'cancellation_reason' => $session->cancellation_reason,
                        'cancellation_type' => $session->cancellation_type,
                        'cancelled_by' => $session->cancelled_by,
                        'subscription_counted' => (bool) ($session->subscription_counted ?? false),
                        'ended_at' => optional($session->ended_at)->toDateTimeString(),
                    ];

                    QuranSession::query()
                        ->withoutGlobalScopes()
                        ->whereKey($session->id)
                        ->update([
                            'status' => SessionStatus::COMPLETED->value,
                            'cancelled_at' => null,
                            'cancellation_reason' => null,
                            'cancellation_type' => null,
                            'cancelled_by' => null,
                            'subscription_counted' => true,
                            'ended_at' => $endedAt,
                        ]);

                    BackfillLog::create([
                        'bug_id' => self::BUG_ID,
                        'table_name' => 'quran_sessions',
                        'row_id' => $session->id,
                        'column_name' => 'restore_from_cancelled',
                        'original_value' => json_encode($originalCancellation, JSON_UNESCAPED_UNICODE),
                        'new_value' => json_encode([
                            'status' => SessionStatus::COMPLETED->value,
                            'subscription_counted' => true,
                            'ended_at' => $endedAt->toDateTimeString(),
                        ], JSON_UNESCAPED_UNICODE),
                        'backfill_command' => 'subscriptions:fix-restore-mis-cancelled-stuck-sessions',
                        'ran_at' => now(),
                    ]);

                    $hasConsumption = SessionConsumption::query()
                        ->where('session_id', $session->id)
                        ->where('session_type', 'quran_session')
                        ->whereNull('reversed_at')
                        ->exists();

                    if (! $hasConsumption && $session->quran_subscription_id) {
                        $consumption = SessionConsumption::create([
                            'session_id' => $session->id,
                            'session_type' => 'quran_session',
                            'subscription_id' => $session->quran_subscription_id,
                            'subscription_type' => (new QuranSubscription)->getMorphClass(),
                            'cycle_id' => $session->subscription_cycle_id,
                            'student_user_id' => $session->student_id,
                            'consumption_type' => 'attended',
                            'source' => SessionConsumption::SOURCE_AUTO_ATTENDANCE,
                            'consumed_at' => $endedAt,
                        ]);

                        BackfillLog::create([
                            'bug_id' => self::BUG_ID,
                            'table_name' => 'session_consumption',
                            'row_id' => $consumption->id,
                            'column_name' => 'INSERT',
                            'original_value' => null,
                            'new_value' => json_encode([
                                'session_id' => $session->id,
                                'cycle_id' => $session->subscription_cycle_id,
                                'subscription_id' => $session->quran_subscription_id,
                            ]),
                            'backfill_command' => 'subscriptions:fix-restore-mis-cancelled-stuck-sessions',
                            'ran_at' => now(),
                        ]);

                        $consumptionRows++;
                    }

                    $restored++;
                });

                if ($session->quran_subscription_id) {
                    $sub = QuranSubscription::withoutGlobalScopes()
                        ->with('currentCycle')
                        ->find((int) $session->quran_subscription_id);
                    if ($sub) {
                        $reconciler->sync($sub);
                        $reconciledSubs[(int) $sub->id] = true;
                    }
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf('session #%d ERROR: %s', $id, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s: restored=%d consumption_rows_created=%d reconciled_subs=%d skipped_already_ok=%d errors=%d',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $restored,
            $consumptionRows,
            count($reconciledSubs),
            $skippedAlreadyOk,
            $errors,
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function candidateIds(): array
    {
        $rows = DB::select('
            SELECT DISTINCT qs.id AS session_id
            FROM quran_sessions qs
            INNER JOIN backfill_log bf
                ON bf.row_id = qs.id
                AND bf.table_name = "quran_sessions"
                AND bf.bug_id = ?
            WHERE qs.status = ?
              AND EXISTS (
                  SELECT 1 FROM meeting_attendances ma
                  WHERE ma.session_id = qs.id
                    AND ma.session_type IN ("individual", "group", "trial")
              )
            ORDER BY qs.id
        ', [self::SOURCE_BUG_ID, SessionStatus::CANCELLED->value]);

        return array_map(fn ($r) => (int) $r->session_id, $rows);
    }

    private function resolveEndedAt(QuranSession $session): \Carbon\Carbon
    {
        $start = $session->scheduled_at?->copy() ?? now()->subHour();
        $duration = (int) ($session->session_duration_minutes ?? 30);
        $end = $start->copy()->addMinutes($duration);
        if ($end->gt(now())) {
            $end = now();
        }

        return $end;
    }
}
