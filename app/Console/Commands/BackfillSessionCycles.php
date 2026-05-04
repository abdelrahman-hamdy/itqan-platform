<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Backfill `subscription_cycle_id` on existing quran_sessions, academic_sessions,
 * and meeting_attendances rows. New rows created post-deploy are stamped by the
 * session observer; this command is for historical data.
 *
 * Backfill priority (highest → lowest confidence):
 *   1. MeetingAttendance.subscription_counted_at — set when useSession() ran;
 *      tells us the cycle that was active at the moment of counting.
 *   2. session.created_at falls inside a cycle's [starts_at, ends_at) window —
 *      the cycle the session was minted under, robust to later reschedules.
 *   3. session.scheduled_at containment — last-resort heuristic for legacy rows.
 *   4. NULL + flag in audit report so an operator can attribute manually.
 *
 * Idempotent: re-running only writes where the column is still NULL.
 */
class BackfillSessionCycles extends Command
{
    protected $signature = 'subscriptions:backfill-session-cycles
                            {--apply : Persist updates (default is dry-run)}
                            {--chunk=500 : Number of rows per batch}
                            {--type=all : Limit to quran|academic|attendance|all}';

    protected $description = 'Backfill subscription_cycle_id on session and attendance rows from historical cycle data';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $dryRun = ! $apply;
        $chunk = max(50, (int) $this->option('chunk'));
        $type = $this->option('type');

        $this->info($dryRun ? 'DRY RUN — no rows will be written. Pass --apply to persist.' : 'Applying backfill writes.');
        $this->newLine();

        $stats = ['quran' => 0, 'academic' => 0, 'attendance' => 0, 'unattributed' => 0];

        if ($type === 'all' || $type === 'quran') {
            $stats['quran'] = $this->backfillSessions(
                QuranSession::class,
                'quran_subscription_id',
                (new QuranSubscription)->getMorphClass(),
                $dryRun,
                $chunk,
                $stats,
            );
        }
        if ($type === 'all' || $type === 'academic') {
            $stats['academic'] = $this->backfillSessions(
                AcademicSession::class,
                'academic_subscription_id',
                (new AcademicSubscription)->getMorphClass(),
                $dryRun,
                $chunk,
                $stats,
            );
        }
        if ($type === 'all' || $type === 'attendance') {
            $stats['attendance'] = $this->backfillMeetingAttendances($dryRun, $chunk);
        }

        $this->newLine();
        $this->table(
            ['Type', 'Stamped'],
            [
                ['Quran sessions', $stats['quran']],
                ['Academic sessions', $stats['academic']],
                ['Meeting attendances', $stats['attendance']],
                ['Unattributable (left NULL)', $stats['unattributed']],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Backfill subscription_cycle_id on a session table (quran or academic).
     *
     * Per chunk: hoist all cycles + all counted_at timestamps in two grouped
     * queries, resolve cycles in PHP, then emit one UPDATE per (cycle_id → ids)
     * bucket. No per-row transaction / lock.
     */
    private function backfillSessions(
        string $sessionClass,
        string $subscriptionFk,
        string $subscribableType,
        bool $dryRun,
        int $chunk,
        array &$stats,
    ): int {
        $stamped = 0;

        $sessionClass::query()
            ->whereNull('subscription_cycle_id')
            ->whereNotNull($subscriptionFk)
            ->orderBy('id')
            ->chunkById($chunk, function ($sessions) use (
                $sessionClass,
                $subscriptionFk,
                $subscribableType,
                $dryRun,
                &$stamped,
                &$stats
            ) {
                $cyclesBySub = $this->loadCyclesForSubscriptions(
                    $subscribableType,
                    $sessions->pluck($subscriptionFk)->unique()->all(),
                );
                $countedAtBySession = $this->loadCountedAtForSessions(
                    $sessions->pluck('id')->all(),
                );

                $idsByCycle = [];
                foreach ($sessions as $session) {
                    $cycles = $cyclesBySub[(int) $session->{$subscriptionFk}] ?? collect();
                    $cycleId = $this->resolveCycleForSession(
                        $cycles,
                        $countedAtBySession[$session->id] ?? null,
                        $session->created_at,
                        $session->scheduled_at,
                    );

                    if (! $cycleId) {
                        $stats['unattributed']++;
                        $this->line("  [unattributed] ".class_basename($sessionClass)." #{$session->id} sub={$session->{$subscriptionFk}}");

                        continue;
                    }

                    $idsByCycle[$cycleId][] = $session->id;
                }

                foreach ($idsByCycle as $cycleId => $ids) {
                    $count = count($ids);
                    $stamped += $count;

                    if ($dryRun) {
                        continue;
                    }

                    $sessionClass::query()
                        ->whereIn('id', $ids)
                        ->whereNull('subscription_cycle_id')
                        ->update(['subscription_cycle_id' => $cycleId]);
                }
            });

        return $stamped;
    }

    /**
     * Backfill MeetingAttendance rows from their session row's already-stamped
     * subscription_cycle_id. Run after the session passes have completed.
     */
    private function backfillMeetingAttendances(bool $dryRun, int $chunk): int
    {
        $stamped = 0;

        MeetingAttendance::query()
            ->whereNull('subscription_cycle_id')
            ->whereNotNull('session_id')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($dryRun, &$stamped) {
                $sessionLookup = $this->buildSessionCycleLookup($rows);

                $idsByCycle = [];
                foreach ($rows as $row) {
                    $key = $row->session_type.'|'.$row->session_id;
                    $cycleId = $sessionLookup[$key] ?? null;

                    if (! $cycleId) {
                        continue;
                    }

                    $idsByCycle[$cycleId][] = $row->id;
                }

                foreach ($idsByCycle as $cycleId => $ids) {
                    $stamped += count($ids);

                    if ($dryRun) {
                        continue;
                    }

                    MeetingAttendance::query()
                        ->whereIn('id', $ids)
                        ->whereNull('subscription_cycle_id')
                        ->update(['subscription_cycle_id' => $cycleId]);
                }
            });

        return $stamped;
    }

    /**
     * @param  Collection<int, SubscriptionCycle>  $cycles
     */
    private function resolveCycleForSession(
        Collection $cycles,
        ?CarbonInterface $countedAt,
        ?CarbonInterface $createdAt,
        ?CarbonInterface $scheduledAt,
    ): ?int {
        if ($cycles->isEmpty()) {
            return null;
        }

        // Priority 1 — counting timestamp: pick the cycle that was active
        // when useSession() ran (not yet archived OR archived after counting).
        if ($countedAt) {
            foreach ($cycles as $cycle) {
                $archivedAt = $cycle->archived_at;
                $activeAtCount = $archivedAt === null || $archivedAt->greaterThanOrEqualTo($countedAt);

                if ($activeAtCount
                    && $cycle->starts_at !== null
                    && $countedAt->greaterThanOrEqualTo($cycle->starts_at)
                ) {
                    return (int) $cycle->id;
                }
            }
        }

        // Priority 2 — created_at containment (cycle the session was minted under).
        if ($createdAt) {
            foreach ($cycles as $cycle) {
                if ($this->cycleContains($cycle, $createdAt)) {
                    return (int) $cycle->id;
                }
            }
        }

        // Priority 3 — scheduled_at containment (last-resort heuristic).
        if ($scheduledAt) {
            foreach ($cycles as $cycle) {
                if ($this->cycleContains($cycle, $scheduledAt)) {
                    return (int) $cycle->id;
                }
            }
        }

        return null;
    }

    /**
     * Half-open interval [starts_at, ends_at) so the boundary moment between
     * two cycles is unambiguously assigned to the next cycle.
     */
    private function cycleContains(SubscriptionCycle $cycle, CarbonInterface $when): bool
    {
        if ($cycle->starts_at === null || $cycle->ends_at === null) {
            return false;
        }

        return $when->greaterThanOrEqualTo($cycle->starts_at)
            && $when->lessThan($cycle->ends_at);
    }

    /**
     * @return array<int, Collection<int, SubscriptionCycle>>
     */
    private function loadCyclesForSubscriptions(string $subscribableType, array $subIds): array
    {
        if (empty($subIds)) {
            return [];
        }

        return SubscriptionCycle::query()
            ->where('subscribable_type', $subscribableType)
            ->whereIn('subscribable_id', $subIds)
            ->orderBy('cycle_number')
            ->get()
            ->groupBy(fn (SubscriptionCycle $c) => (int) $c->subscribable_id)
            ->all();
    }

    /**
     * @return array<int, CarbonInterface>
     */
    private function loadCountedAtForSessions(array $sessionIds): array
    {
        if (empty($sessionIds)) {
            return [];
        }

        return MeetingAttendance::query()
            ->whereIn('session_id', $sessionIds)
            ->where('user_type', 'student')
            ->whereNotNull('subscription_counted_at')
            ->orderBy('subscription_counted_at')
            ->get(['session_id', 'subscription_counted_at'])
            ->reduce(function (array $carry, MeetingAttendance $row) {
                $carry[$row->session_id] ??= $row->subscription_counted_at;
                return $carry;
            }, []);
    }

    /**
     * Build a (session_type|session_id) → subscription_cycle_id lookup so an
     * attendance row can inherit from its session row in two queries per chunk.
     */
    private function buildSessionCycleLookup($attendanceRows): array
    {
        $quranIds = [];
        $academicIds = [];

        foreach ($attendanceRows as $row) {
            $sessionId = $row->session_id;
            if (! $sessionId) {
                continue;
            }

            $type = $row->session_type;
            if ($type === 'group' || $type === 'individual' || $type === 'trial') {
                $quranIds[] = $sessionId;
            } elseif ($type === 'academic') {
                $academicIds[] = $sessionId;
            }
        }

        $lookup = [];

        if (! empty($quranIds)) {
            QuranSession::query()
                ->whereIn('id', array_unique($quranIds))
                ->whereNotNull('subscription_cycle_id')
                ->get(['id', 'session_type', 'subscription_cycle_id'])
                ->each(function ($session) use (&$lookup) {
                    $key = ($session->session_type ?? 'individual').'|'.$session->id;
                    $lookup[$key] = (int) $session->subscription_cycle_id;
                });
        }

        if (! empty($academicIds)) {
            AcademicSession::query()
                ->whereIn('id', array_unique($academicIds))
                ->whereNotNull('subscription_cycle_id')
                ->get(['id', 'subscription_cycle_id'])
                ->each(function ($session) use (&$lookup) {
                    $lookup['academic|'.$session->id] = (int) $session->subscription_cycle_id;
                });
        }

        return $lookup;
    }
}
