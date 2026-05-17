<?php

namespace App\Console\Commands\Subscriptions\Audit;

use App\Models\MeetingAttendance;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Models\TeacherEarning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Per-session classifier for the final cleanup of residue subscription drift
 * (2026-05-17 evening — categories 3 + 4 of the 6-issue plan).
 *
 * Walks every quran_session that today's prior phases could not classify
 * automatically and emits one of seven verdicts per row, modeled on the
 * canonical no-show=paid matrix in CalculateSessionForAttendance::
 * calculateTeacherAttendanceAndSetFlags():
 *
 *   - AUTO_BACKFILL          — sub+cycle set, MA exists with
 *                              counts_for_subscription != 0, the matrix says
 *                              this session should consume; create a
 *                              session_consumption row via the existing
 *                              fix-classified-drift writer (B.3).
 *
 *   - AUTO_FLIP_OFF          — counted=1 but no MA, no teacher_earning, no
 *                              consumption: the session never happened.
 *                              Flip subscription_counted=false (B.4); does
 *                              not touch cycle/sub counters.
 *
 *   - AUTO_ATTACH_SUB        — orphan (no quran_subscription_id) but exactly
 *                              one active sub of the student covers
 *                              scheduled_at. Stamp sub_id (+ cycle if
 *                              resolvable) in B.2, then re-classify.
 *
 *   - AUTO_ATTACH_CYCLE      — sub set but cycle NULL, exactly one cycle of
 *                              the sub covers scheduled_at. Stamp cycle in
 *                              B.2.
 *
 *   - MATRIX_EXCLUDED        — MA.counts_for_subscription = 0; already
 *                              handled by today's reverse-matrix-excluded
 *                              reversal — surfaced here as a sanity check.
 *
 *   - BACKUP_SHOWS_DIFFERENT — the 2026-05-15 baseline DB
 *                              (--baseline-db) shows different
 *                              subscription_counted / subscription_cycle_id /
 *                              quran_subscription_id state. Caller must
 *                              decide whether prod or baseline is the truth.
 *
 *   - NEEDS_REVIEW           — multiple subs match, missing both MA and
 *                              earning while counted=1, or any other
 *                              ambiguity. Cleared in bulk by the Step 3
 *                              sweep (`subscriptions:fix-final-counted-flip`).
 *
 * Output: CSV at storage/app/audit/residue-classification-{date}.csv with
 * one row per session: session_id, verdict, sub_id, cycle_id, evidence,
 * scheduled_at, student_id, counted, has_ma, has_earning, has_consumption.
 *
 * Pure read-only — no writes. Phase A.2 of the plan.
 */
class ClassifyResidueDrift extends Command
{
    protected $signature = 'subscriptions:classify-residue-drift
                            {--baseline-db= : Optional MySQL DB name with the 2026-05-15 backup loaded (for BACKUP_SHOWS_DIFFERENT verdict)}
                            {--output=residue-classification-2026-05-17.csv : Filename under storage/app/audit/ for the output CSV}
                            {--limit=0 : Limit candidate rows scanned (0 = no limit; for local smoke tests)}';

    protected $description = 'Read-only classifier that walks residual drift rows and emits a CSV with per-session verdicts (A.2 of final cleanup plan).';

    public const VERDICT_AUTO_BACKFILL = 'AUTO_BACKFILL';

    public const VERDICT_AUTO_FLIP_OFF = 'AUTO_FLIP_OFF';

    public const VERDICT_AUTO_ATTACH_SUB = 'AUTO_ATTACH_SUB';

    public const VERDICT_AUTO_ATTACH_CYCLE = 'AUTO_ATTACH_CYCLE';

    public const VERDICT_MATRIX_EXCLUDED = 'MATRIX_EXCLUDED';

    public const VERDICT_BACKUP_SHOWS_DIFFERENT = 'BACKUP_SHOWS_DIFFERENT';

    public const VERDICT_NEEDS_REVIEW = 'NEEDS_REVIEW';

    public function handle(): int
    {
        $baselineDb = $this->option('baseline-db');
        if ($baselineDb !== null && $baselineDb !== '') {
            $this->ensureBaselineDbReadable($baselineDb);
        }

        $limit = (int) $this->option('limit');
        $output = (string) $this->option('output');

        $candidates = $this->fetchCandidates($limit);
        $this->info(sprintf('Scanning %d candidate sessions…', count($candidates)));
        $this->newLine();

        $counters = array_fill_keys([
            self::VERDICT_AUTO_BACKFILL,
            self::VERDICT_AUTO_FLIP_OFF,
            self::VERDICT_AUTO_ATTACH_SUB,
            self::VERDICT_AUTO_ATTACH_CYCLE,
            self::VERDICT_MATRIX_EXCLUDED,
            self::VERDICT_BACKUP_SHOWS_DIFFERENT,
            self::VERDICT_NEEDS_REVIEW,
        ], 0);

        $rows = [];
        foreach ($candidates as $c) {
            $verdict = $this->classify($c, $baselineDb);
            $counters[$verdict['class']]++;
            $rows[] = $verdict;
        }

        Storage::disk('local')->makeDirectory('audit');
        $path = 'audit/'.$output;
        Storage::disk('local')->put($path, $this->renderCsv($rows));

        $this->info('Per-verdict counts:');
        foreach ($counters as $verdict => $n) {
            $this->line(sprintf('  %-25s %d', $verdict, $n));
        }
        $this->newLine();
        $this->info(sprintf('Wrote %d rows to storage/app/%s', count($rows), $path));

        return self::SUCCESS;
    }

    /**
     * Pull every session that today's prior phases left in residue state:
     *
     *   (A) subscription_counted = 1 AND no active consumption row, OR
     *   (B) subscription_counted = 0 but quran_subscription_id IS NULL
     *       (orphan candidates surfaced by the cleanup audit).
     *
     * @return array<int,object>
     */
    private function fetchCandidates(int $limit): array
    {
        $sql = <<<'SQL'
            SELECT qs.id AS session_id,
                   qs.student_id,
                   qs.quran_subscription_id AS subscription_id,
                   qs.subscription_cycle_id AS cycle_id,
                   qs.subscription_counted AS counted,
                   qs.scheduled_at,
                   qs.ended_at,
                   qs.status,
                   qs.session_type
            FROM quran_sessions qs
            WHERE qs.deleted_at IS NULL
              AND (
                  (qs.subscription_counted = 1 AND NOT EXISTS (
                      SELECT 1 FROM session_consumption sc
                      WHERE sc.session_id = qs.id
                        AND sc.session_type = 'quran_session'
                        AND sc.reversed_at IS NULL
                  ))
                  OR
                  (qs.quran_subscription_id IS NULL)
              )
            ORDER BY qs.id
        SQL;

        if ($limit > 0) {
            $sql .= ' LIMIT '.$limit;
        }

        return DB::select($sql);
    }

    /**
     * @return array{class:string,session_id:int,subscription_id:?int,cycle_id:?int,counted:int,evidence:list<string>,scheduled_at:?string,student_id:int}
     */
    private function classify(object $c, ?string $baselineDb): array
    {
        $sessionId = (int) $c->session_id;
        $studentId = (int) $c->student_id;
        $subId = $c->subscription_id !== null ? (int) $c->subscription_id : null;
        $cycleId = $c->cycle_id !== null ? (int) $c->cycle_id : null;
        $counted = (int) $c->counted;
        $scheduledAt = $c->scheduled_at;

        $evidence = [];

        // ── signal: student MeetingAttendance row for this session ──────────
        $ma = MeetingAttendance::query()
            ->where('session_id', $sessionId)
            ->whereIn('session_type', ['individual', 'group', 'trial'])
            ->where('user_id', $studentId)
            ->where('user_type', 'student')
            ->first(['id', 'attendance_status', 'counts_for_subscription']);

        $hasMa = $ma !== null;
        $maStatus = $hasMa ? $this->stringify($ma->attendance_status) : null;
        if ($hasMa) {
            $evidence[] = sprintf('ma.status=%s', $maStatus ?? '');
            $evidence[] = sprintf('ma.counts=%d', (int) $ma->counts_for_subscription);
        }

        // ── signal: teacher_earnings ledger entry pointing at this session ──
        $hasEarning = TeacherEarning::query()
            ->where('session_id', $sessionId)
            ->where('session_type', 'quran_session')
            ->exists();
        if ($hasEarning) {
            $evidence[] = 'has_earning';
        }

        // ── signal: existing consumption row (any state) ────────────────────
        $hasActiveConsumption = SessionConsumption::query()
            ->where('session_id', $sessionId)
            ->where('session_type', 'quran_session')
            ->whereNull('reversed_at')
            ->exists();
        $hasAnyConsumption = SessionConsumption::query()
            ->where('session_id', $sessionId)
            ->where('session_type', 'quran_session')
            ->exists();
        if ($hasActiveConsumption) {
            $evidence[] = 'has_active_consumption';
        } elseif ($hasAnyConsumption) {
            $evidence[] = 'has_reversed_consumption';
        }

        // ── rule 1: MATRIX_EXCLUDED — sanity-check class ────────────────────
        if ($hasMa && (int) $ma->counts_for_subscription === 0) {
            return $this->row(self::VERDICT_MATRIX_EXCLUDED, $c, $evidence);
        }

        // ── rule 2: orphan sub_id — try AUTO_ATTACH_SUB ─────────────────────
        if ($subId === null) {
            $matching = $this->findSubsCoveringSession($studentId, $scheduledAt);
            $evidence[] = sprintf('candidate_subs=%d', count($matching));
            if (count($matching) === 1) {
                return $this->row(
                    self::VERDICT_AUTO_ATTACH_SUB,
                    $c,
                    array_merge($evidence, ['attach_sub_id='.$matching[0]['sub_id']]),
                );
            }

            return $this->row(self::VERDICT_NEEDS_REVIEW, $c, array_merge($evidence, ['reason=orphan_no_unique_sub']));
        }

        // ── rule 3: cycle NULL but sub set — try AUTO_ATTACH_CYCLE ──────────
        if ($cycleId === null) {
            $matching = $this->findCyclesCoveringSession($subId, $scheduledAt);
            $evidence[] = sprintf('candidate_cycles=%d', count($matching));
            if (count($matching) === 1) {
                return $this->row(
                    self::VERDICT_AUTO_ATTACH_CYCLE,
                    $c,
                    array_merge($evidence, ['attach_cycle_id='.$matching[0]]),
                );
            }

            return $this->row(self::VERDICT_NEEDS_REVIEW, $c, array_merge($evidence, ['reason=cycle_null_no_unique_match']));
        }

        // ── rule 4: AUTO_BACKFILL — sub+cycle set, MA implies session ran ──
        $matrixSaysConsumes = $hasMa
            && (int) $ma->counts_for_subscription !== 0
            && in_array($maStatus, ['attended', 'late', 'left', 'absent', 'partially_attended'], true);

        if ($counted === 1 && ! $hasActiveConsumption && $matrixSaysConsumes) {
            // Backup cross-reference (optional): warn if baseline disagrees
            // about which sub/cycle this row was charged to.
            $baselineDiff = $this->checkBaselineDifference($baselineDb, $sessionId, $subId, $cycleId, $counted);
            if ($baselineDiff !== null) {
                return $this->row(
                    self::VERDICT_BACKUP_SHOWS_DIFFERENT,
                    $c,
                    array_merge($evidence, ['baseline='.$baselineDiff]),
                );
            }

            return $this->row(self::VERDICT_AUTO_BACKFILL, $c, $evidence);
        }

        // ── rule 5: AUTO_FLIP_OFF — phantom counted with no evidence ──────
        if ($counted === 1 && ! $hasMa && ! $hasEarning && ! $hasActiveConsumption) {
            return $this->row(self::VERDICT_AUTO_FLIP_OFF, $c, array_merge($evidence, ['reason=phantom_no_evidence']));
        }

        // ── fallback: NEEDS_REVIEW ──────────────────────────────────────────
        $reason = 'unmatched';
        if ($counted === 1 && $hasEarning && ! $hasMa) {
            $reason = 'counted_with_earning_no_ma';
        } elseif ($counted === 1 && ! $matrixSaysConsumes && $hasMa) {
            $reason = 'counted_but_matrix_does_not_consume';
        } elseif ($counted === 0 && $hasActiveConsumption) {
            $reason = 'consumption_without_flag';
        }

        return $this->row(self::VERDICT_NEEDS_REVIEW, $c, array_merge($evidence, ['reason='.$reason]));
    }

    /**
     * Active QuranSubscriptions of the student whose [starts_at..ends_at]
     * window covers the session's scheduled_at.
     *
     * @return list<array{sub_id:int,cycle_id:?int}>
     */
    private function findSubsCoveringSession(int $studentId, ?string $scheduledAt): array
    {
        if ($scheduledAt === null) {
            return [];
        }

        return QuranSubscription::query()
            ->withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->where('starts_at', '<=', $scheduledAt)
            ->where(function ($q) use ($scheduledAt) {
                $q->where('ends_at', '>=', $scheduledAt)
                    ->orWhereNull('ends_at');
            })
            ->whereNull('deleted_at')
            ->get(['id', 'current_cycle_id'])
            ->map(fn ($s) => [
                'sub_id' => (int) $s->id,
                'cycle_id' => $s->current_cycle_id !== null ? (int) $s->current_cycle_id : null,
            ])
            ->all();
    }

    /**
     * Cycle IDs of the subscription whose [starts_at..ends_at] window covers
     * the session's scheduled_at. Only non-deleted cycles are considered.
     *
     * @return list<int>
     */
    private function findCyclesCoveringSession(int $subId, ?string $scheduledAt): array
    {
        if ($scheduledAt === null) {
            return [];
        }

        return SubscriptionCycle::query()
            ->where('subscribable_id', $subId)
            ->where('subscribable_type', (new QuranSubscription)->getMorphClass())
            ->whereNull('deleted_at')
            ->where('starts_at', '<=', $scheduledAt)
            ->where('ends_at', '>=', $scheduledAt)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Cross-reference the 2026-05-15 baseline DB. Returns a short delta
     * string if any of counted / cycle_id / subscription_id differ, null
     * if they match (or if no baseline DB was given).
     */
    private function checkBaselineDifference(
        ?string $baselineDb,
        int $sessionId,
        ?int $subId,
        ?int $cycleId,
        int $counted,
    ): ?string {
        if ($baselineDb === null || $baselineDb === '') {
            return null;
        }

        $row = DB::selectOne(sprintf(
            'SELECT subscription_counted AS counted,
                    quran_subscription_id AS sub_id,
                    subscription_cycle_id AS cycle_id
             FROM `%s`.quran_sessions
             WHERE id = ? AND deleted_at IS NULL',
            $baselineDb,
        ), [$sessionId]);

        if ($row === null) {
            return 'absent_in_baseline';
        }

        $diffs = [];
        if ((int) $row->counted !== $counted) {
            $diffs[] = sprintf('counted %d→%d', (int) $row->counted, $counted);
        }
        $baselineSub = $row->sub_id !== null ? (int) $row->sub_id : null;
        if ($baselineSub !== $subId) {
            $diffs[] = sprintf('sub %s→%s', $baselineSub ?? 'NULL', $subId ?? 'NULL');
        }
        $baselineCycle = $row->cycle_id !== null ? (int) $row->cycle_id : null;
        if ($baselineCycle !== $cycleId) {
            $diffs[] = sprintf('cycle %s→%s', $baselineCycle ?? 'NULL', $cycleId ?? 'NULL');
        }

        return $diffs === [] ? null : implode('|', $diffs);
    }

    private function ensureBaselineDbReadable(string $baselineDb): void
    {
        try {
            DB::selectOne(sprintf('SELECT 1 FROM `%s`.quran_sessions LIMIT 1', $baselineDb));
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf(
                'Baseline DB %s.quran_sessions not readable: %s',
                $baselineDb,
                $e->getMessage(),
            ));
        }
    }

    /**
     * Coerce an enum/string/null to its plain string value so sprintf and
     * in_array work consistently regardless of model casts.
     */
    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }
        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        return (string) $value;
    }

    /**
     * @param  list<string>  $evidence
     * @return array{class:string,session_id:int,subscription_id:?int,cycle_id:?int,counted:int,evidence:list<string>,scheduled_at:?string,student_id:int}
     */
    private function row(string $class, object $c, array $evidence): array
    {
        return [
            'class' => $class,
            'session_id' => (int) $c->session_id,
            'subscription_id' => $c->subscription_id !== null ? (int) $c->subscription_id : null,
            'cycle_id' => $c->cycle_id !== null ? (int) $c->cycle_id : null,
            'counted' => (int) $c->counted,
            'student_id' => (int) $c->student_id,
            'scheduled_at' => $c->scheduled_at,
            'evidence' => $evidence,
        ];
    }

    /**
     * @param  list<array<string,mixed>>  $rows
     */
    private function renderCsv(array $rows): string
    {
        $header = [
            'verdict',
            'session_id',
            'subscription_id',
            'cycle_id',
            'counted',
            'student_id',
            'scheduled_at',
            'evidence',
        ];

        $lines = [implode(',', $header)];
        foreach ($rows as $r) {
            $lines[] = implode(',', [
                $r['class'],
                $r['session_id'],
                $r['subscription_id'] ?? '',
                $r['cycle_id'] ?? '',
                $r['counted'],
                $r['student_id'],
                $r['scheduled_at'] ?? '',
                '"'.str_replace('"', '""', implode(';', $r['evidence'])).'"',
            ]);
        }

        return implode("\n", $lines)."\n";
    }
}
