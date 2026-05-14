<?php

namespace App\Console\Commands\Subscriptions;

use App\Console\Commands\Backfill\BaseBackfillCommand;
use App\Models\AcademicSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Phase A.2 — one-time bootstrap of `session_consumption` rows from the
 * legacy idempotency flags (R2 migration).
 *
 * Reads every row marked as already counted via the old dual-write paths:
 *   - `MeetingAttendance` rows with `subscription_counted_at IS NOT NULL`
 *     (group sessions tracked per-student here).
 *   - `QuranSession` / `AcademicSession` rows with `subscription_counted = true`
 *     (individual/legacy sessions tracked per-session here).
 *
 * Writes the equivalent `session_consumption` row at `source = auto_attendance`
 * (the lowest precedence — bootstrap is purely "this was counted by the old
 * pipeline"; admin/teacher overrides will promote later via the
 * SubscriptionConsumption writer's P5 cascade).
 *
 * Idempotent: the unique index `(session_id, session_type, subscription_id,
 * subscription_type)` is checked before each insert; collisions are skipped
 * with a `skipped_existing` log line. Re-running the command after a partial
 * run is safe.
 *
 * NOT wrapped in SubscriptionLock — this is a pre-production bootstrap that
 * runs once against a quiesced dataset. Online migration with concurrent
 * traffic is out of scope (it would require per-sub locking + a separate
 * reconcile call per row).
 *
 * Usage:
 *   php artisan subscriptions:bootstrap-consumption-rows --dry-run
 *   php artisan subscriptions:bootstrap-consumption-rows
 *   php artisan subscriptions:bootstrap-consumption-rows --academy=42
 */
class BootstrapConsumptionRowsCommand extends BaseBackfillCommand
{
    protected $signature = 'subscriptions:bootstrap-consumption-rows
                            {--dry-run : Print what would be inserted without writing}
                            {--academy= : Restrict to a single academy id}';

    protected $description = 'Bootstrap session_consumption rows from legacy subscription_counted / subscription_counted_at flags (Phase A.2 — INV-B1 migration).';

    protected const BUG_ID = 'SUBV2-BOOTSTRAP';

    protected const COMMAND_NAME = 'subscriptions:bootstrap-consumption-rows';

    /**
     * Page size when streaming the source tables. Bootstrap may scan
     * hundreds of thousands of rows in prod; keep memory bounded.
     */
    private const CHUNK_SIZE = 500;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $academyId = $this->option('academy') !== null ? (int) $this->option('academy') : null;

        if ($dryRun) {
            $this->warn(__('subscriptions.bootstrap_consumption.dry_run_notice'));
        } else {
            $this->info(__('subscriptions.bootstrap_consumption.apply_notice'));
        }

        $stats = [
            'attendance_inserted' => 0,
            'attendance_skipped_existing' => 0,
            'attendance_skipped_unresolved' => 0,
            'session_inserted' => 0,
            'session_skipped_existing' => 0,
            'session_skipped_unresolved' => 0,
        ];

        // ----------------------------------------------------------------
        // 1) Group/per-student counted attendance rows.
        // ----------------------------------------------------------------
        $this->backfillFromMeetingAttendances($academyId, $dryRun, $stats);

        // ----------------------------------------------------------------
        // 2) Individual/legacy session-level subscription_counted flag.
        //    Only Quran + Academic — InteractiveCourseSession does NOT use
        //    the trait per the cycle-anchor migration doc.
        // ----------------------------------------------------------------
        $this->backfillFromSessions(QuranSession::class, $academyId, $dryRun, $stats);
        $this->backfillFromSessions(AcademicSession::class, $academyId, $dryRun, $stats);

        $this->newLine();
        $this->table(
            [__('subscriptions.bootstrap_consumption.metric_header'), __('subscriptions.bootstrap_consumption.count_header')],
            collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all(),
        );

        return self::SUCCESS;
    }

    /**
     * Walk `meeting_attendances` for rows with `subscription_counted_at`
     * stamped. Each row maps to one consumption row keyed on the
     * (session_id, session_type, subscription_id, subscription_type) tuple
     * we can resolve off the underlying session.
     */
    private function backfillFromMeetingAttendances(
        ?int $academyId,
        bool $dryRun,
        array &$stats,
    ): void {
        $query = MeetingAttendance::query()
            ->whereNotNull('subscription_counted_at')
            ->where('user_type', MeetingAttendance::STUDENT_USER_TYPE)
            ->orderBy('id');

        if ($academyId !== null) {
            // No academy_id on meeting_attendances — filter via the session.
            $query->where(function ($q) use ($academyId) {
                $q->whereExists(function ($sub) use ($academyId) {
                    $sub->select(DB::raw(1))
                        ->from('quran_sessions')
                        ->whereColumn('quran_sessions.id', 'meeting_attendances.session_id')
                        ->where('meeting_attendances.session_type', 'quran')
                        ->where('quran_sessions.academy_id', $academyId);
                })->orWhereExists(function ($sub) use ($academyId) {
                    $sub->select(DB::raw(1))
                        ->from('academic_sessions')
                        ->whereColumn('academic_sessions.id', 'meeting_attendances.session_id')
                        ->where('meeting_attendances.session_type', 'academic')
                        ->where('academic_sessions.academy_id', $academyId);
                });
            });
        }

        $query->chunkById(self::CHUNK_SIZE, function ($attendances) use ($dryRun, &$stats) {
            foreach ($attendances as $attendance) {
                $this->processAttendanceRow($attendance, $dryRun, $stats);
            }
        });
    }

    private function processAttendanceRow(
        MeetingAttendance $attendance,
        bool $dryRun,
        array &$stats,
    ): void {
        try {
            $session = $this->loadSessionForAttendance($attendance);

            if ($session === null) {
                $stats['attendance_skipped_unresolved']++;

                return;
            }

            $subscription = $this->resolveSubscription($session, $attendance->user_id);

            if ($subscription === null) {
                $stats['attendance_skipped_unresolved']++;

                return;
            }

            $cycleId = $attendance->subscription_cycle_id
                ?? $session->subscription_cycle_id
                ?? $subscription->current_cycle_id;

            if ($cycleId === null) {
                $stats['attendance_skipped_unresolved']++;

                return;
            }

            $exists = SessionConsumption::query()
                ->where('session_id', $session->getKey())
                ->where('session_type', $session->getMorphClass())
                ->where('subscription_id', $subscription->getKey())
                ->where('subscription_type', $subscription->getMorphClass())
                ->exists();

            if ($exists) {
                $stats['attendance_skipped_existing']++;

                return;
            }

            $consumptionType = \App\Support\Subscriptions\AttendanceConsumptionMapper::consumptionTypeFor(
                $attendance->attendance_status,
                (bool) ($attendance->counts_for_subscription ?? true),
            );

            if ($consumptionType === null) {
                $stats['attendance_skipped_unresolved']++;

                return;
            }

            if ($dryRun) {
                $stats['attendance_inserted']++;

                return;
            }

            DB::transaction(function () use ($attendance, $session, $subscription, $cycleId, $consumptionType) {
                SessionConsumption::create([
                    'session_id' => $session->getKey(),
                    'session_type' => $session->getMorphClass(),
                    'subscription_id' => $subscription->getKey(),
                    'subscription_type' => $subscription->getMorphClass(),
                    'cycle_id' => $cycleId,
                    'student_user_id' => $attendance->user_id,
                    'consumption_type' => $consumptionType,
                    'source' => SessionConsumption::SOURCE_AUTO_ATTENDANCE,
                    'source_user_id' => null,
                    'consumed_at' => $attendance->subscription_counted_at,
                ]);
            });

            $stats['attendance_inserted']++;
        } catch (Throwable $e) {
            $this->warn(sprintf(
                'attendance#%d: %s',
                $attendance->id,
                $e->getMessage(),
            ));
            $stats['attendance_skipped_unresolved']++;
        }
    }

    /**
     * Walk session rows with `subscription_counted = true`. Each row maps
     * to one consumption charged against the session's own subscription.
     *
     * @param  class-string<Model>  $sessionModel
     */
    private function backfillFromSessions(
        string $sessionModel,
        ?int $academyId,
        bool $dryRun,
        array &$stats,
    ): void {
        $query = $sessionModel::query()
            ->where('subscription_counted', true)
            ->orderBy('id');

        if ($academyId !== null) {
            $query->where('academy_id', $academyId);
        }

        $query->chunkById(self::CHUNK_SIZE, function ($sessions) use ($dryRun, &$stats) {
            foreach ($sessions as $session) {
                $this->processSessionRow($session, $dryRun, $stats);
            }
        });
    }

    private function processSessionRow(
        Model $session,
        bool $dryRun,
        array &$stats,
    ): void {
        try {
            $studentUserId = $this->extractStudentUserId($session);
            $subscription = $this->resolveSubscription($session, $studentUserId);

            if ($subscription === null || $studentUserId === null) {
                $stats['session_skipped_unresolved']++;

                return;
            }

            $cycleId = $session->subscription_cycle_id ?? $subscription->current_cycle_id;
            if ($cycleId === null) {
                $stats['session_skipped_unresolved']++;

                return;
            }

            $exists = SessionConsumption::query()
                ->where('session_id', $session->getKey())
                ->where('session_type', $session->getMorphClass())
                ->where('subscription_id', $subscription->getKey())
                ->where('subscription_type', $subscription->getMorphClass())
                ->exists();

            if ($exists) {
                $stats['session_skipped_existing']++;

                return;
            }

            // No attendance row → assume the legacy counted flag was set
            // because the session was completed normally. Map to ATTENDED.
            $consumptionType = SessionConsumption::TYPE_ATTENDED;

            if ($dryRun) {
                $stats['session_inserted']++;

                return;
            }

            DB::transaction(function () use ($session, $subscription, $cycleId, $studentUserId, $consumptionType) {
                SessionConsumption::create([
                    'session_id' => $session->getKey(),
                    'session_type' => $session->getMorphClass(),
                    'subscription_id' => $subscription->getKey(),
                    'subscription_type' => $subscription->getMorphClass(),
                    'cycle_id' => $cycleId,
                    'student_user_id' => $studentUserId,
                    'consumption_type' => $consumptionType,
                    'source' => SessionConsumption::SOURCE_AUTO_ATTENDANCE,
                    'source_user_id' => null,
                    'consumed_at' => $session->ended_at ?? $session->scheduled_at ?? now(),
                ]);
            });

            $stats['session_inserted']++;
        } catch (Throwable $e) {
            $this->warn(sprintf(
                'session %s#%d: %s',
                $session->getMorphClass(),
                $session->getKey(),
                $e->getMessage(),
            ));
            $stats['session_skipped_unresolved']++;
        }
    }

    /**
     * Load the underlying session record for a MeetingAttendance using the
     * `session_type` discriminator stored on the attendance row.
     */
    private function loadSessionForAttendance(MeetingAttendance $attendance): ?Model
    {
        return match ($attendance->session_type) {
            'academic' => AcademicSession::query()->find($attendance->session_id),
            'interactive' => null, // InteractiveCourseSession is enrollment-based; skip
            default => QuranSession::query()->find($attendance->session_id),
        };
    }

    /**
     * Resolve the consuming subscription for a given (session, studentUser).
     * Reuses the same logic the legacy CountsTowardsSubscription trait used:
     * honor the session's own subscription FK first, then fall back to
     * enrollment / individual circle lookup. We don't reimplement all the
     * legacy fallbacks here — group sessions that lack an FK on the row
     * but whose attendance carries a subscription_cycle_id still resolve
     * via the cycle, which is enough for bootstrap fidelity.
     */
    private function resolveSubscription(Model $session, ?int $studentUserId): ?Model
    {
        // QuranSession + AcademicSession both expose getSubscriptionForCounting().
        if (method_exists($session, 'getSubscriptionForCounting')) {
            $sub = $session->getSubscriptionForCounting();
            if ($sub !== null) {
                return $sub;
            }
        }

        // Group-session attendance row: anchor via the attendance's
        // subscription_cycle_id if we have one.
        if ($studentUserId !== null && property_exists($session, 'subscription_cycle_id')) {
            $cycle = $session->subscription_cycle_id
                ? SubscriptionCycle::query()->find($session->subscription_cycle_id)
                : null;

            if ($cycle !== null) {
                $sub = $cycle->subscribable;
                if ($sub !== null) {
                    return $sub;
                }
            }
        }

        return null;
    }

    /**
     * Pull a student user id off a session for the per-session backfill
     * path. QuranSession exposes `student_id`; AcademicSession links via
     * subscription's `student_id`.
     */
    private function extractStudentUserId(Model $session): ?int
    {
        if (! empty($session->student_id)) {
            return (int) $session->student_id;
        }

        if (method_exists($session, 'getSubscriptionForCounting')) {
            $sub = $session->getSubscriptionForCounting();
            if ($sub !== null && ! empty($sub->student_id)) {
                return (int) $sub->student_id;
            }
        }

        return null;
    }
}
