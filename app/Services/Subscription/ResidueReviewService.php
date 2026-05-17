<?php

namespace App\Services\Subscription;

use App\Console\Commands\Subscriptions\Audit\ClassifyResidueDrift;
use App\Models\BackfillLog;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Service for the supervisor residue-review flow surfaced at
 * /manage/residue-review (C.2 of the 2026-05-17 final cleanup plan).
 *
 * Surfaces the NEEDS_REVIEW and BACKUP_SHOWS_DIFFERENT verdicts emitted
 * by `subscriptions:classify-residue-drift` (A.2) — the cases that the
 * deterministic auto-fixers (B.3 AUTO_BACKFILL, B.4 AUTO_FLIP_OFF, B.2
 * AUTO_ATTACH_*) cannot safely close without human judgment.
 *
 * Three actions per row:
 *   - `force_count`   → write a SessionConsumption (source=admin_manual)
 *                       and set quran_sessions.subscription_counted=true.
 *                       Reconcile the sub afterwards. Requires sub+cycle.
 *   - `force_uncount` → reverse any active SessionConsumption + flip
 *                       subscription_counted=false. Reconcile the sub.
 *   - `defer`         → write a BackfillLog marker (bug_id=
 *                       `residue-review-defer-2026-05-17`) so the row
 *                       falls off the queue. No data writes.
 *
 * Reviewed sessions fall off automatically because the entries query
 * excludes any session that already has a decision marker.
 */
class ResidueReviewService
{
    public const BUG_ID_FORCE_COUNT = 'residue-force-count-2026-05-17';

    public const BUG_ID_FORCE_UNCOUNT = 'residue-force-uncount-26-05-17';

    public const BUG_ID_DEFER = 'residue-review-defer-2026-05-17';

    public function __construct(
        private readonly SubscriptionReconciler $reconciler,
        private readonly SubscriptionConsumption $consumption,
    ) {}

    /**
     * Default location for the classifier CSV (overridable in tests).
     */
    public function defaultCsvPath(): string
    {
        return 'audit/residue-classification-2026-05-17.csv';
    }

    /**
     * List the residue rows awaiting supervisor decision.
     *
     * Reads the latest classifier CSV, filters to NEEDS_REVIEW +
     * BACKUP_SHOWS_DIFFERENT, drops anything that already has a decision
     * marker in backfill_log, and enriches with session / subscription /
     * cycle / student names.
     *
     * @return list<array<string, mixed>>
     */
    public function entries(?string $csvPath = null): array
    {
        $path = $csvPath ?? $this->defaultCsvPath();
        if (! Storage::disk('local')->exists($path)) {
            return [];
        }

        $rows = $this->loadCsv($path);
        $candidates = array_values(array_filter(
            $rows,
            fn (array $r) => in_array($r['verdict'], [
                ClassifyResidueDrift::VERDICT_NEEDS_REVIEW,
                ClassifyResidueDrift::VERDICT_BACKUP_SHOWS_DIFFERENT,
            ], true),
        ));

        if ($candidates === []) {
            return [];
        }

        $sessionIds = array_map(fn ($r) => (int) $r['session_id'], $candidates);

        // Drop already-decided rows.
        $decidedIds = BackfillLog::query()
            ->whereIn('bug_id', [self::BUG_ID_FORCE_COUNT, self::BUG_ID_FORCE_UNCOUNT, self::BUG_ID_DEFER])
            ->where('table_name', 'quran_sessions')
            ->whereIn('row_id', $sessionIds)
            ->pluck('row_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $decided = array_flip($decidedIds);

        $sessions = QuranSession::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $sessionIds)
            ->get(['id', 'student_id', 'quran_teacher_id', 'quran_subscription_id', 'subscription_cycle_id', 'subscription_counted', 'scheduled_at', 'status', 'session_type'])
            ->keyBy('id');

        $subIds = $sessions->pluck('quran_subscription_id')->filter()->unique()->all();
        $subs = QuranSubscription::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $subIds)
            ->get(['id', 'status', 'total_sessions', 'sessions_used', 'starts_at', 'ends_at'])
            ->keyBy('id');

        $cycleIds = $sessions->pluck('subscription_cycle_id')->filter()->unique()->all();
        $cycles = SubscriptionCycle::query()
            ->whereIn('id', $cycleIds)
            ->get(['id', 'cycle_state', 'payment_status', 'sessions_used', 'total_sessions', 'starts_at', 'ends_at'])
            ->keyBy('id');

        $userIds = $sessions->pluck('student_id')
            ->merge($sessions->pluck('quran_teacher_id')->filter())
            ->unique()
            ->all();
        $users = User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        $out = [];
        foreach ($candidates as $r) {
            $sid = (int) $r['session_id'];
            if (isset($decided[$sid])) {
                continue;
            }

            $session = $sessions->get($sid);
            if ($session === null) {
                continue;
            }

            $sub = $session->quran_subscription_id !== null ? $subs->get((int) $session->quran_subscription_id) : null;
            $cycle = $session->subscription_cycle_id !== null ? $cycles->get((int) $session->subscription_cycle_id) : null;
            $student = $users->get((int) $session->student_id);
            $teacher = $session->quran_teacher_id !== null ? $users->get((int) $session->quran_teacher_id) : null;

            $out[] = [
                'session_id' => $sid,
                'verdict' => $r['verdict'],
                'evidence' => $r['evidence'],
                'student_id' => (int) $session->student_id,
                'student_name' => $this->userName($student) ?: '#'.$session->student_id,
                'teacher_id' => $session->quran_teacher_id,
                'teacher_name' => $teacher ? $this->userName($teacher) : null,
                'sub_id' => $session->quran_subscription_id,
                'sub_status' => $sub?->status,
                'cycle_id' => $session->subscription_cycle_id,
                'cycle_state' => $cycle?->cycle_state,
                'cycle_used' => $cycle ? (int) $cycle->sessions_used : null,
                'cycle_total' => $cycle ? (int) $cycle->total_sessions : null,
                'session_status' => $session->status,
                'scheduled_at' => $session->scheduled_at,
                'counted' => (bool) $session->subscription_counted,
                'baseline_diff' => $this->extractBaselineDiff($r['evidence']),
                'can_force_count' => $session->quran_subscription_id !== null
                    && $session->subscription_cycle_id !== null,
            ];
        }

        return $out;
    }

    /**
     * Apply a supervisor decision to a single residue row.
     *
     * @return array{session_id:int, action:string, sub_id:?int, cycle_id:?int, note:?string}
     */
    public function recordDecision(
        int $sessionId,
        string $action,
        int $supervisorUserId,
        ?string $note = null,
    ): array {
        if (! in_array($action, ['force_count', 'force_uncount', 'defer'], true)) {
            throw new \InvalidArgumentException('Unknown action: '.$action);
        }

        $session = QuranSession::query()
            ->withoutGlobalScopes()
            ->findOrFail($sessionId);

        if ($action === 'defer') {
            BackfillLog::create([
                'bug_id' => self::BUG_ID_DEFER,
                'table_name' => 'quran_sessions',
                'row_id' => $session->id,
                'column_name' => 'residue_review_decision',
                'original_value' => null,
                'new_value' => json_encode([
                    'decision' => 'defer',
                    'reviewed_by_user_id' => $supervisorUserId,
                    'note' => $note,
                    'at' => now()->toDateTimeString(),
                ]),
                'backfill_command' => 'web:residue-review',
                'ran_at' => now(),
            ]);

            return [
                'session_id' => (int) $session->id,
                'action' => 'defer',
                'sub_id' => $session->quran_subscription_id !== null ? (int) $session->quran_subscription_id : null,
                'cycle_id' => $session->subscription_cycle_id !== null ? (int) $session->subscription_cycle_id : null,
                'note' => $note,
            ];
        }

        if ($action === 'force_count') {
            if ($session->quran_subscription_id === null || $session->subscription_cycle_id === null) {
                throw new \InvalidArgumentException(
                    'force_count requires both quran_subscription_id and subscription_cycle_id on the session.',
                );
            }

            $sub = QuranSubscription::query()
                ->withoutGlobalScopes()
                ->with('currentCycle')
                ->findOrFail((int) $session->quran_subscription_id);

            $student = User::query()->findOrFail((int) $session->student_id);
            $reverser = User::query()->findOrFail($supervisorUserId);

            DB::transaction(function () use ($session, $sub, $student, $reverser, $supervisorUserId, $note) {
                // Idempotent — promote if a row already exists, otherwise insert.
                $this->consumption->record(
                    session: $session,
                    student: $student,
                    sub: $sub,
                    source: SessionConsumption::SOURCE_ADMIN_MANUAL,
                    sourceUser: $reverser,
                    consumptionType: SessionConsumption::TYPE_ATTENDED,
                );

                if (! $session->subscription_counted) {
                    BackfillLog::create([
                        'bug_id' => self::BUG_ID_FORCE_COUNT,
                        'table_name' => 'quran_sessions',
                        'row_id' => $session->id,
                        'column_name' => 'subscription_counted',
                        'original_value' => '0',
                        'new_value' => '1',
                        'backfill_command' => 'web:residue-review',
                        'ran_at' => now(),
                    ]);

                    QuranSession::query()
                        ->withoutGlobalScopes()
                        ->whereKey($session->id)
                        ->update(['subscription_counted' => true]);
                }

                // One marker row so the queue filter excludes the session
                // even when no other column was mutated.
                BackfillLog::create([
                    'bug_id' => self::BUG_ID_FORCE_COUNT,
                    'table_name' => 'quran_sessions',
                    'row_id' => $session->id,
                    'column_name' => 'residue_review_decision',
                    'original_value' => null,
                    'new_value' => json_encode([
                        'decision' => 'force_count',
                        'reviewed_by_user_id' => $supervisorUserId,
                        'note' => $note,
                        'at' => now()->toDateTimeString(),
                    ]),
                    'backfill_command' => 'web:residue-review',
                    'ran_at' => now(),
                ]);
            });

            return [
                'session_id' => (int) $session->id,
                'action' => 'force_count',
                'sub_id' => (int) $session->quran_subscription_id,
                'cycle_id' => (int) $session->subscription_cycle_id,
                'note' => $note,
            ];
        }

        // force_uncount
        $sub = $session->quran_subscription_id !== null
            ? QuranSubscription::query()->withoutGlobalScopes()->with('currentCycle')->find((int) $session->quran_subscription_id)
            : null;
        $reverser = User::query()->findOrFail($supervisorUserId);

        DB::transaction(function () use ($session, $sub, $reverser, $supervisorUserId, $note) {
            $active = SessionConsumption::query()
                ->where('session_id', $session->id)
                ->where('session_type', 'quran_session')
                ->whereNull('reversed_at')
                ->get();

            foreach ($active as $row) {
                $this->consumption->reverse($row, 'residue_review_force_uncount', $reverser);
            }

            if ($session->subscription_counted) {
                BackfillLog::create([
                    'bug_id' => self::BUG_ID_FORCE_UNCOUNT,
                    'table_name' => 'quran_sessions',
                    'row_id' => $session->id,
                    'column_name' => 'subscription_counted',
                    'original_value' => '1',
                    'new_value' => '0',
                    'backfill_command' => 'web:residue-review',
                    'ran_at' => now(),
                ]);

                QuranSession::query()
                    ->withoutGlobalScopes()
                    ->whereKey($session->id)
                    ->update(['subscription_counted' => false]);
            }

            BackfillLog::create([
                'bug_id' => self::BUG_ID_FORCE_UNCOUNT,
                'table_name' => 'quran_sessions',
                'row_id' => $session->id,
                'column_name' => 'residue_review_decision',
                'original_value' => null,
                'new_value' => json_encode([
                    'decision' => 'force_uncount',
                    'reviewed_by_user_id' => $supervisorUserId,
                    'note' => $note,
                    'reversed_count' => $active->count(),
                    'at' => now()->toDateTimeString(),
                ]),
                'backfill_command' => 'web:residue-review',
                'ran_at' => now(),
            ]);

            if ($sub instanceof QuranSubscription) {
                $this->reconciler->sync($sub->fresh(['currentCycle']));
            }
        });

        return [
            'session_id' => (int) $session->id,
            'action' => 'force_uncount',
            'sub_id' => $session->quran_subscription_id !== null ? (int) $session->quran_subscription_id : null,
            'cycle_id' => $session->subscription_cycle_id !== null ? (int) $session->subscription_cycle_id : null,
            'note' => $note,
        ];
    }

    private function userName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name : null;
    }

    /**
     * @param  list<string>  $evidence
     */
    private function extractBaselineDiff(array $evidence): ?string
    {
        foreach ($evidence as $tag) {
            if (str_starts_with($tag, 'baseline=')) {
                return substr($tag, strlen('baseline='));
            }
        }

        return null;
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
