<?php

namespace App\Services\Subscription;

/**
 * Per-session evidence aggregator for the post-v2-flip audit.
 *
 * Given one element from SubscriptionSnapshot::captureManifest()['sessions'],
 * each evidence source emits a vote (COUNT / DONT_COUNT / NO_SIGNAL) with a
 * confidence (HIGH / MED / LOW). Aggregation rules:
 *
 *   1. If an active session_consumption row exists for the student, the
 *      verdict is AUTHORITATIVE — the canonical truth is already on disk.
 *   2. Otherwise, the verdict is HIGH-confidence COUNT (or DONT_COUNT) only
 *      when every HIGH-confidence vote agrees AND no HIGH-confidence vote
 *      disagrees. Any HIGH conflict drops the verdict to UNCERTAIN.
 *   3. With no HIGH votes, MED votes vote. If unanimous, MED confidence.
 *      Mixed MED → UNCERTAIN.
 *   4. LOW votes alone never produce an AUTO verdict — they only resolve
 *      ties.
 *   5. No signal at all (a future scheduled session with no attendance, no
 *      report, no recording, no legacy flag) → NO_SIGNAL.
 *
 * The downstream audit-one / audit-all commands interpret AUTO verdicts
 * (HIGH + agreement) as auto-applicable; everything else goes to the
 * admin-review queue.
 */
final class SessionEvidenceReconciler
{
    public const VERDICT_COUNT = 'COUNT';

    public const VERDICT_DONT_COUNT = 'DONT_COUNT';

    public const VERDICT_AUTHORITATIVE = 'AUTHORITATIVE_FROM_CONSUMPTION';

    public const VERDICT_UNCERTAIN = 'UNCERTAIN';

    public const VERDICT_NO_SIGNAL = 'NO_SIGNAL';

    public const CONFIDENCE_HIGH = 'HIGH';

    public const CONFIDENCE_MED = 'MED';

    public const CONFIDENCE_LOW = 'LOW';

    public const CONFIDENCE_NONE = 'NONE';

    /**
     * @param  array<string,mixed>  $sessionEntry  one element of manifest.sessions
     * @param  list<array<string,mixed>>  $consumptions  manifest.consumptions filtered to this session
     * @return array{
     *     verdict: string,
     *     confidence: string,
     *     votes: list<array{source:string,vote:string,confidence:string,reason:string}>,
     *     reasons: list<string>,
     *     conflicts: list<string>,
     * }
     */
    public function reconcile(array $sessionEntry, int $studentUserId, array $consumptions): array
    {
        $votes = [];
        $reasons = [];

        // ── Rule 1: authoritative consumption row wins outright ──
        foreach ($consumptions as $c) {
            if ((int) $c['session_id'] !== (int) $sessionEntry['id']) {
                continue;
            }
            if ((int) ($c['student_user_id'] ?? 0) !== $studentUserId) {
                continue;
            }
            if (! empty($c['reversed_at'])) {
                continue;
            }

            // We have a non-reversed consumption row for this (session, student).
            $reasons[] = sprintf(
                'session_consumption #%d (source=%s, type=%s) — authoritative',
                $c['id'],
                $c['source'],
                $c['consumption_type'],
            );

            return [
                'verdict' => self::VERDICT_AUTHORITATIVE,
                'confidence' => self::CONFIDENCE_HIGH,
                'votes' => [[
                    'source' => 'session_consumption',
                    'vote' => self::VERDICT_COUNT,
                    'confidence' => self::CONFIDENCE_HIGH,
                    'reason' => $reasons[0],
                ]],
                'reasons' => $reasons,
                'conflicts' => [],
            ];
        }

        // ── Rule 2-3: evidence votes ──
        foreach ($sessionEntry['attendance'] ?? [] as $att) {
            if ((int) ($att['user_id'] ?? 0) !== $studentUserId) {
                continue;
            }
            $vote = $this->voteFromAttendanceStatus($att['attendance_status'] ?? null);
            if ($vote === null) {
                continue;
            }
            $votes[] = [
                'source' => 'meeting_attendance',
                'vote' => $vote['vote'],
                'confidence' => $vote['confidence'],
                'reason' => sprintf('attendance.status=%s', $att['attendance_status'] ?? 'null'),
            ];
        }

        foreach ($sessionEntry['reports'] ?? [] as $report) {
            if ((int) ($report['student_id'] ?? 0) !== $studentUserId) {
                continue;
            }
            $vote = $this->voteFromAttendanceStatus($report['attendance_status'] ?? null);
            if ($vote === null) {
                continue;
            }
            $votes[] = [
                'source' => 'student_session_report',
                'vote' => $vote['vote'],
                // Reports were unreliable historically; cap at MED.
                'confidence' => $vote['confidence'] === self::CONFIDENCE_HIGH
                    ? self::CONFIDENCE_MED
                    : $vote['confidence'],
                'reason' => sprintf('report.attendance_status=%s', $report['attendance_status'] ?? 'null'),
            ];
        }

        if (! empty($sessionEntry['legacy_subscription_counted'])) {
            $votes[] = [
                'source' => 'legacy_subscription_counted',
                'vote' => self::VERDICT_COUNT,
                'confidence' => self::CONFIDENCE_MED,
                'reason' => 'session.subscription_counted=true (deprecated, MED only)',
            ];
        }

        if (! empty($sessionEntry['has_recording'])) {
            $votes[] = [
                'source' => 'session_recording',
                'vote' => self::VERDICT_COUNT,
                'confidence' => self::CONFIDENCE_LOW,
                'reason' => 'session_recordings row exists',
            ];
        }

        if (($sessionEntry['status'] ?? null) === 'completed') {
            $votes[] = [
                'source' => 'session_status',
                'vote' => self::VERDICT_COUNT,
                'confidence' => self::CONFIDENCE_MED,
                'reason' => 'session.status=completed',
            ];
        }
        if (($sessionEntry['status'] ?? null) === 'cancelled') {
            $votes[] = [
                'source' => 'session_status',
                'vote' => self::VERDICT_DONT_COUNT,
                'confidence' => self::CONFIDENCE_MED,
                'reason' => 'session.status=cancelled',
            ];
        }

        if ($votes === []) {
            return [
                'verdict' => self::VERDICT_NO_SIGNAL,
                'confidence' => self::CONFIDENCE_NONE,
                'votes' => [],
                'reasons' => ['no evidence rows reference this (session, student)'],
                'conflicts' => [],
            ];
        }

        return $this->aggregate($votes);
    }

    /**
     * Translate a stored attendance status into a vote + confidence tuple.
     * Returns null if the status is null / unknown (NO_SIGNAL).
     *
     * @return array{vote:string,confidence:string}|null
     */
    private function voteFromAttendanceStatus(mixed $status): ?array
    {
        $key = is_string($status) ? strtolower($status) : null;

        return match ($key) {
            'attended' => ['vote' => self::VERDICT_COUNT, 'confidence' => self::CONFIDENCE_HIGH],
            'late' => ['vote' => self::VERDICT_COUNT, 'confidence' => self::CONFIDENCE_HIGH],
            'left' => ['vote' => self::VERDICT_COUNT, 'confidence' => self::CONFIDENCE_HIGH],
            'partially_attended' => ['vote' => self::VERDICT_COUNT, 'confidence' => self::CONFIDENCE_HIGH],
            'absent' => ['vote' => self::VERDICT_DONT_COUNT, 'confidence' => self::CONFIDENCE_HIGH],
            default => null,
        };
    }

    /**
     * Aggregate a non-empty vote list per the rules in the class docblock.
     *
     * @param  list<array{source:string,vote:string,confidence:string,reason:string}>  $votes
     * @return array{verdict:string,confidence:string,votes:list<array{source:string,vote:string,confidence:string,reason:string}>,reasons:list<string>,conflicts:list<string>}
     */
    private function aggregate(array $votes): array
    {
        $byConfidence = [
            self::CONFIDENCE_HIGH => [],
            self::CONFIDENCE_MED => [],
            self::CONFIDENCE_LOW => [],
        ];
        foreach ($votes as $vote) {
            $byConfidence[$vote['confidence']][] = $vote;
        }

        foreach ([self::CONFIDENCE_HIGH, self::CONFIDENCE_MED] as $tier) {
            $bucket = $byConfidence[$tier];
            if ($bucket === []) {
                continue;
            }
            $unique = array_unique(array_column($bucket, 'vote'));
            if (count($unique) === 1) {
                return [
                    'verdict' => $unique[array_key_first($unique)],
                    'confidence' => $tier,
                    'votes' => $votes,
                    'reasons' => array_column($bucket, 'reason'),
                    'conflicts' => [],
                ];
            }

            // Conflict at this tier → UNCERTAIN, surface the conflicting reasons.
            $conflicts = array_map(
                fn (array $v) => sprintf('%s votes %s (%s)', $v['source'], $v['vote'], $v['reason']),
                $bucket,
            );

            return [
                'verdict' => self::VERDICT_UNCERTAIN,
                'confidence' => $tier,
                'votes' => $votes,
                'reasons' => array_map(fn (array $v) => $v['reason'], $votes),
                'conflicts' => $conflicts,
            ];
        }

        // Only LOW signals — never auto-apply.
        return [
            'verdict' => self::VERDICT_UNCERTAIN,
            'confidence' => self::CONFIDENCE_LOW,
            'votes' => $votes,
            'reasons' => array_map(fn (array $v) => $v['reason'], $votes),
            'conflicts' => ['only LOW-confidence signals available'],
        ];
    }
}
