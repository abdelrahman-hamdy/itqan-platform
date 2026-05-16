<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\SessionStatus;
use App\Models\BackfillLog;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\TeacherEarning;
use App\Services\Subscription\SubscriptionReconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Companion to StuckScheduledSessions. That command skipped any stuck-scheduled
 * row that already had a `teacher_earnings` ledger entry on the "earnings imply
 * the session actually ran" theory. This command processes that skipped set —
 * 19 quran rows on prod as of 2026-05-17 — by transitioning them to COMPLETED
 * with the correct accounting:
 *
 *   - status: scheduled → completed
 *   - ended_at: scheduled_at + session_duration_minutes (best-effort, never future)
 *   - subscription_counted: 1
 *   - one session_consumption row (source=auto_attendance) per attended student
 *   - SubscriptionReconciler::sync() on the parent subscription
 *
 * **Per-row eligibility:** the teacher must have an attendance row in this
 * session (any morph type) with attendance_status='attended'. Rows where the
 * teacher is recorded `absent` but earnings still exist are anomalies and get
 * SKIPPED for manual admin review — paying a teacher who didn't show up is its
 * own incident.
 *
 * Per the no-show=paid policy, student-side attendance is informational only:
 * the session counts even if the student didn't attend.
 *
 * BackfillLog row per write for rollback. Dry-run by default.
 */
class CompleteStuckWithEarnings extends Command
{
    protected $signature = 'subscriptions:fix-complete-stuck-with-earnings
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Complete stuck-scheduled quran sessions that already have teacher_earnings (companion to fix-stuck-scheduled-sessions).';

    private const BUG_ID = 'complete-stuck-earnings-26-05-17';

    public function handle(SubscriptionReconciler $reconciler): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply ? 'APPLYING' : 'DRY-RUN');
        $this->newLine();

        $scanned = 0;
        $eligible = 0;
        $completed = 0;
        $consumptionRows = 0;
        $skipped = [
            'consumption_exists' => 0,
            'no_teacher_attendance' => 0,
            'teacher_absent' => 0,
            'no_earning' => 0,
            'no_subscription' => 0,
            'no_cycle' => 0,
        ];
        $errors = 0;
        $reconciledSubs = [];

        QuranSession::query()
            ->withoutGlobalScopes()
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('scheduled_at', '<', now()->subMinutes(5))
            ->orderBy('id')
            ->chunkById(200, function ($sessions) use (
                $apply,
                $reconciler,
                &$scanned,
                &$eligible,
                &$completed,
                &$consumptionRows,
                &$skipped,
                &$errors,
                &$reconciledSubs,
            ) {
                foreach ($sessions as $s) {
                    $scanned++;
                    try {
                        $sessionType = $s->getMorphClass();

                        if (! TeacherEarning::query()
                            ->where('session_id', $s->id)
                            ->where('session_type', TeacherEarning::normalizeSessionType($sessionType))
                            ->exists()) {
                            $skipped['no_earning']++;

                            continue;
                        }

                        if (SessionConsumption::query()
                            ->where('session_id', $s->id)
                            ->where('session_type', $sessionType)
                            ->whereNull('reversed_at')
                            ->exists()) {
                            $skipped['consumption_exists']++;

                            continue;
                        }

                        $teacherAttendance = $this->teacherAttendanceFor($s);
                        if (! $teacherAttendance) {
                            $skipped['no_teacher_attendance']++;

                            continue;
                        }

                        $teacherStatus = $this->resolveAttendanceStatus($teacherAttendance);
                        if ($teacherStatus === 'absent') {
                            $this->warn(sprintf(
                                'session #%d: teacher recorded absent but earning exists — SKIPPED (admin must review)',
                                $s->id,
                            ));
                            $skipped['teacher_absent']++;

                            continue;
                        }

                        if (! $s->quran_subscription_id) {
                            $skipped['no_subscription']++;

                            continue;
                        }

                        // session_consumption.cycle_id is NOT NULL — skip rows
                        // missing a cycle anchor. The session is still legacy-
                        // counted (subscription_counted=true) but won't get a
                        // canonical consumption row until the cycle is assigned.
                        if (! $s->subscription_cycle_id) {
                            $skipped['no_cycle'] = ($skipped['no_cycle'] ?? 0) + 1;

                            continue;
                        }

                        $eligible++;

                        if (! $apply) {
                            $this->line(sprintf(
                                '  + session #%d would complete (sub #%d, cycle #%s, scheduled %s)',
                                $s->id,
                                $s->quran_subscription_id,
                                $s->subscription_cycle_id ?? '-',
                                $s->scheduled_at?->toDateTimeString() ?? '-',
                            ));

                            continue;
                        }

                        DB::transaction(function () use ($s, $sessionType, &$completed, &$consumptionRows) {
                            $originalStatus = $s->status instanceof \BackedEnum ? $s->status->value : (string) $s->status;
                            $originalEndedAt = $s->ended_at;
                            $originalCounted = (bool) ($s->subscription_counted ?? false);

                            $endedAt = $this->resolveEndedAt($s);

                            QuranSession::query()
                                ->withoutGlobalScopes()
                                ->whereKey($s->id)
                                ->update([
                                    'status' => SessionStatus::COMPLETED->value,
                                    'ended_at' => $endedAt,
                                    'subscription_counted' => true,
                                ]);

                            BackfillLog::create([
                                'bug_id' => self::BUG_ID,
                                'table_name' => 'quran_sessions',
                                'row_id' => $s->id,
                                'column_name' => 'status_ended_at_counted',
                                'original_value' => json_encode([
                                    'status' => $originalStatus,
                                    'ended_at' => $originalEndedAt instanceof \Carbon\Carbon ? $originalEndedAt->toDateTimeString() : $originalEndedAt,
                                    'subscription_counted' => $originalCounted,
                                ], JSON_UNESCAPED_UNICODE),
                                'new_value' => json_encode([
                                    'status' => SessionStatus::COMPLETED->value,
                                    'ended_at' => $endedAt->toDateTimeString(),
                                    'subscription_counted' => true,
                                ], JSON_UNESCAPED_UNICODE),
                                'backfill_command' => 'subscriptions:fix-complete-stuck-with-earnings',
                                'ran_at' => now(),
                            ]);

                            $consumption = SessionConsumption::create([
                                'session_id' => $s->id,
                                'session_type' => $sessionType,
                                'subscription_id' => $s->quran_subscription_id,
                                'subscription_type' => (new QuranSubscription)->getMorphClass(),
                                'cycle_id' => $s->subscription_cycle_id,
                                'student_user_id' => $s->student_id,
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
                                    'session_id' => $s->id,
                                    'cycle_id' => $s->subscription_cycle_id,
                                    'subscription_id' => $s->quran_subscription_id,
                                ]),
                                'backfill_command' => 'subscriptions:fix-complete-stuck-with-earnings',
                                'ran_at' => now(),
                            ]);

                            $completed++;
                            $consumptionRows++;
                        });

                        $sub = QuranSubscription::withoutGlobalScopes()
                            ->with('currentCycle')
                            ->find((int) $s->quran_subscription_id);
                        if ($sub) {
                            try {
                                $reconciler->sync($sub);
                                $reconciledSubs[(int) $sub->id] = true;
                            } catch (\Throwable $reconcileError) {
                                // The consumption row IS the source of truth
                                // (INV-B2). The reconciler may refuse to mirror
                                // it into the cycle counters when doing so
                                // would violate INV-B4 (sessions_remaining < 0)
                                // — that's exactly the overflow case the
                                // /manage/overflow-cycles-review UI handles.
                                // Log and continue; the session-level state is
                                // already correct.
                                $this->warn(sprintf(
                                    'session #%d: consumption written, reconciler deferred: %s',
                                    $s->id,
                                    $reconcileError->getMessage(),
                                ));
                            }
                        }
                    } catch (\Throwable $e) {
                        $errors++;
                        $this->warn(sprintf('session #%d ERROR: %s', $s->id, $e->getMessage()));
                    }
                }
            });

        $this->newLine();
        $this->info(sprintf(
            '%s: scanned=%d eligible=%d completed=%d consumption_rows=%d reconciled_subs=%d errors=%d',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $scanned,
            $eligible,
            $completed,
            $consumptionRows,
            count($reconciledSubs),
            $errors,
        ));
        if (array_sum($skipped) > 0) {
            $this->newLine();
            $this->line('Skipped (per-class counts):');
            foreach ($skipped as $reason => $n) {
                if ($n > 0) {
                    $this->line(sprintf('  %s: %d', $reason, $n));
                }
            }
        }

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Return the teacher's MeetingAttendance row for this session (across all
     * possible morph tags + teacher user_type variants), or null.
     */
    private function teacherAttendanceFor(QuranSession $session): ?MeetingAttendance
    {
        if (! $session->quran_teacher_id) {
            return null;
        }

        return MeetingAttendance::query()
            ->where('session_id', $session->id)
            ->where('user_id', $session->quran_teacher_id)
            ->whereIn('user_type', MeetingAttendance::TEACHER_USER_TYPES)
            ->whereIn('session_type', [$session->getMorphClass(), 'individual', 'group', 'trial'])
            ->first();
    }

    private function resolveAttendanceStatus(MeetingAttendance $attendance): ?string
    {
        $status = $attendance->attendance_status;
        if ($status instanceof \BackedEnum) {
            return $status->value;
        }

        return $status === null ? null : (string) $status;
    }

    private function resolveEndedAt(QuranSession $session): \Carbon\Carbon
    {
        $start = $session->scheduled_at?->copy() ?? now()->subHour();
        $duration = (int) ($session->session_duration_minutes ?? 30);
        $end = $start->copy()->addMinutes($duration);

        // Never stamp an ended_at in the future for a historical session.
        if ($end->gt(now())) {
            $end = now();
        }

        return $end;
    }
}
