<?php

namespace App\Services\Subscription;

use App\Models\BackfillLog;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Models\User;
use App\Support\Subscriptions\CycleDriftClassifier;
use Illuminate\Support\Facades\DB;

/**
 * Service for the supervisor "overflow cycles review" flow surfaced at
 * /manage/overflow-cycles-review.
 *
 * An "overflow cycle" is one where adding the legacy-counted-but-not-consumed
 * drift sessions as consumption rows would push the cycle's `sessions_used`
 * above its `total_sessions`. The {@see \App\Console\Commands\Subscriptions\Fix\LegacyCountingDrift}
 * command intentionally skips these — three different historical causes can
 * produce overflow, and only an admin can pick the right one:
 *
 *  1. The supervisor extended the package after the fact without updating
 *     `total_sessions` → pick **bump_total**.
 *  2. The legacy path over-counted (e.g. double-completion) → pick
 *     **forgive_n** with the duplicate count.
 *  3. The supervisor is still unsure → **defer** (no-op).
 *
 * Reviewed cycles fall off the list automatically — after `bump_total` the
 * subsequent drift-fix run materialises consumption rows; after `forgive_n`
 * the drift count drops by N. Either way the cycle is no longer in overflow.
 *
 * BackfillLog row per write for rollback.
 */
class OverflowCyclesReviewService
{
    private const BUG_ID = 'overflow-review-2026-05-17';

    public function __construct(
        private readonly SubscriptionReconciler $reconciler,
    ) {}

    /**
     * List the overflow cycles awaiting supervisor decision.
     *
     * @return list<array<string, mixed>>
     */
    public function overflowCycles(): array
    {
        $drift = DB::select("
            SELECT qs.id AS session_id,
                   qs.quran_subscription_id AS subscription_id,
                   qs.subscription_cycle_id AS cycle_id,
                   qs.student_id,
                   qs.scheduled_at
            FROM quran_sessions qs
            WHERE qs.subscription_counted = 1
              AND qs.deleted_at IS NULL
              AND qs.subscription_cycle_id IS NOT NULL
              AND qs.quran_subscription_id IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1 FROM session_consumption sc
                  WHERE sc.session_id = qs.id
                    AND sc.session_type = 'quran_session'
                    AND sc.reversed_at IS NULL
              )
        ");

        $byCycle = collect($drift)->groupBy('cycle_id');
        if ($byCycle->isEmpty()) {
            return [];
        }

        $cycles = SubscriptionCycle::query()
            ->whereIn('id', $byCycle->keys()->all())
            ->get()
            ->keyBy('id');

        $subIds = collect($drift)->pluck('subscription_id')->filter()->unique();
        $subs = QuranSubscription::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $subIds->all())
            ->get(['id', 'student_id', 'quran_teacher_id', 'total_sessions', 'status', 'starts_at', 'ends_at'])
            ->keyBy('id');

        $userIds = collect($subs)
            ->flatMap(fn ($s) => [$s->student_id, $s->quran_teacher_id])
            ->filter()
            ->unique();
        $users = User::query()
            ->whereIn('id', $userIds->all())
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        $overflow = [];
        foreach ($byCycle as $cycleId => $rows) {
            $cycle = $cycles->get((int) $cycleId);
            if (! $cycle) {
                continue;
            }
            $drift = $rows->count();
            $used = (int) $cycle->sessions_used;
            $total = (int) $cycle->total_sessions;
            $wouldBe = $used + $drift;
            if ($wouldBe <= $total) {
                continue;
            }

            $sub = $subs->get((int) $rows->first()->subscription_id);
            $studentName = $sub && $users->has($sub->student_id)
                ? trim(($users[$sub->student_id]->first_name ?? '').' '.($users[$sub->student_id]->last_name ?? ''))
                : null;
            $teacherName = $sub && $sub->quran_teacher_id && $users->has($sub->quran_teacher_id)
                ? trim(($users[$sub->quran_teacher_id]->first_name ?? '').' '.($users[$sub->quran_teacher_id]->last_name ?? ''))
                : null;

            $verdict = $sub ? $this->classifierVerdict($sub, $cycle, $drift) : null;

            $overflow[] = [
                'cycle_id' => (int) $cycleId,
                'sub_id' => $sub?->id,
                'student_id' => $sub?->student_id,
                'student_name' => $studentName ?: ($sub ? '#'.$sub->student_id : '—'),
                'teacher_id' => $sub?->quran_teacher_id,
                'teacher_name' => $teacherName ?: ($sub?->quran_teacher_id ? '#'.$sub->quran_teacher_id : null),
                'sub_status' => $sub?->status,
                'starts_at' => $sub?->starts_at,
                'ends_at' => $sub?->ends_at,
                'cycle_state' => $cycle->cycle_state,
                'cycle_payment_status' => $cycle->payment_status,
                'used' => $used,
                'drift' => $drift,
                'would_be_used' => $wouldBe,
                'total' => $total,
                'overflow_by' => $wouldBe - $total,
                'drift_session_ids' => $rows->pluck('session_id')->map(fn ($id) => (int) $id)->all(),
                // C.1: classifier-driven default for the supervisor's action
                // dropdown. RE_DRIFT / CONFIRMED_BUG → bump_total (the drift
                // is real and the cycle simply needs more headroom).
                // FORGIVING_UNDERCOUNT / PRESET_SUSPECT / SOFT_DELETED_EXPLAINED
                // → forgive_n (the drift is double-counting). Everything else
                // defaults to defer so a human picks.
                'classifier_class' => $verdict['class'] ?? null,
                'classifier_reason_ar' => $verdict['reason_ar'] ?? null,
                'classifier_evidence' => $verdict['evidence'] ?? [],
                'recommended_action' => $this->recommendedAction($verdict['class'] ?? null),
            ];
        }

        usort($overflow, fn ($a, $b) => $b['overflow_by'] <=> $a['overflow_by']);

        return $overflow;
    }

    /**
     * Run the deterministic CycleDriftClassifier on the overflow cycle and
     * surface its verdict so the supervisor sees a default action +
     * Arabic explanation without leaving the page.
     *
     * @return array{class:string,gap:int,evidence:list<string>,reason_ar:string}
     */
    private function classifierVerdict(QuranSubscription $sub, SubscriptionCycle $cycle, int $drift): array
    {
        $actualCounted = SessionConsumption::query()
            ->where('cycle_id', $cycle->id)
            ->whereNull('reversed_at')
            ->count();

        $softDeleted = QuranSession::query()
            ->withoutGlobalScopes()
            ->where('subscription_cycle_id', $cycle->id)
            ->where('subscription_counted', true)
            ->whereNotNull('deleted_at')
            ->count();

        $priorRepairs = BackfillLog::query()
            ->where('table_name', 'subscription_cycles')
            ->where('row_id', $cycle->id)
            ->whereNotIn('bug_id', ['overflow-review-2026-05-17'])
            ->count();

        $metadata = (array) ($sub->metadata ?? []);
        $shownExhausted = (bool) ($metadata['sessions_exhausted'] ?? false);

        return CycleDriftClassifier::classify([
            'stored_used' => (int) $cycle->sessions_used + $drift,
            'actual_counted' => $actualCounted,
            'soft_deleted_counted' => $softDeleted,
            'prior_repairs' => $priorRepairs,
            'shown_exhausted' => $shownExhausted,
            'purchase_source' => $sub->purchase_source ?? '',
            'cycle_number' => (int) ($cycle->cycle_number ?? 1),
            'cycle_state' => $cycle->cycle_state,
            'cycle_created_at' => $cycle->created_at,
        ]);
    }

    /**
     * Map a classifier verdict to the supervisor-form's default action.
     *
     * The mapping mirrors the plan's intent:
     *   - RE_DRIFT / CONFIRMED_BUG: the drift IS real consumption that the
     *     cycle never made room for → bump the total.
     *   - PRESET_SUSPECT / FORGIVING_UNDERCOUNT / SOFT_DELETED_EXPLAINED:
     *     the drift is over-counting → forgive N from the legacy flag.
     *   - Everything else (PRE_REFACTOR_AMBIGUOUS, ARCHIVED_NOISE,
     *     NEEDS_REVIEW): defer so a human picks.
     */
    private function recommendedAction(?string $class): string
    {
        return match ($class) {
            CycleDriftClassifier::CLASS_RE_DRIFT,
            CycleDriftClassifier::CLASS_CONFIRMED_BUG => 'bump_total',

            CycleDriftClassifier::CLASS_PRESET_SUSPECT,
            CycleDriftClassifier::CLASS_FORGIVING_UNDERCOUNT,
            CycleDriftClassifier::CLASS_SOFT_DELETED_EXPLAINED => 'forgive_n',

            default => 'defer',
        };
    }

    /**
     * Apply a supervisor decision to a single overflow cycle.
     *
     * - `bump_total`: total_sessions += drift_count. (Subsequent drift-fix run
     *   materialises the consumption rows; no overflow remains.)
     * - `forgive_n`: pick the N most-recent drift sessions in this cycle and
     *   flip subscription_counted=false. Their slots return to the cycle's
     *   capacity, and the next drift-fix run handles the remainder.
     * - `defer`: no-op (returns immediately).
     *
     * @return array{cycle_id:int, action:string, drift_after:int, total_after:int}
     */
    public function recordDecision(
        int $cycleId,
        string $action,
        ?int $forgiveCount,
        int $supervisorUserId,
        ?string $note,
    ): array {
        if (! in_array($action, ['bump_total', 'forgive_n', 'defer'], true)) {
            throw new \InvalidArgumentException('Unknown action: '.$action);
        }

        if ($action === 'defer') {
            $cycle = SubscriptionCycle::query()->findOrFail($cycleId);

            return [
                'cycle_id' => $cycleId,
                'action' => 'defer',
                'drift_after' => $this->driftCountForCycle($cycleId),
                'total_after' => (int) $cycle->total_sessions,
            ];
        }

        $cycle = SubscriptionCycle::query()->lockForUpdate()->findOrFail($cycleId);
        $subId = (int) $cycle->subscribable_id;
        if ($cycle->subscribable_type !== (new QuranSubscription)->getMorphClass()) {
            throw new \InvalidArgumentException('Only quran cycles are supported.');
        }

        $driftIds = $this->driftSessionIdsForCycle($cycleId);
        if (empty($driftIds)) {
            return [
                'cycle_id' => $cycleId,
                'action' => $action,
                'drift_after' => 0,
                'total_after' => (int) $cycle->total_sessions,
            ];
        }

        if ($action === 'bump_total') {
            $bumpBy = count($driftIds);
            $originalTotal = (int) $cycle->total_sessions;
            $newTotal = $originalTotal + $bumpBy;

            DB::transaction(function () use ($cycle, $newTotal, $originalTotal, $bumpBy, $supervisorUserId, $note) {
                BackfillLog::create([
                    'bug_id' => self::BUG_ID,
                    'table_name' => 'subscription_cycles',
                    'row_id' => $cycle->id,
                    'column_name' => 'total_sessions',
                    'original_value' => (string) $originalTotal,
                    'new_value' => (string) $newTotal,
                    'backfill_command' => 'web:overflow-cycles-review',
                    'ran_at' => now(),
                ]);
                $metadata = (array) ($cycle->metadata ?? []);
                $metadata['overflow_review'] = [
                    'action' => 'bump_total',
                    'bumped_by' => $bumpBy,
                    'reviewed_at' => now()->toDateTimeString(),
                    'reviewed_by_user_id' => $supervisorUserId,
                    'note' => $note ? mb_substr($note, 0, 500) : null,
                ];
                $cycle->total_sessions = $newTotal;
                $cycle->metadata = $metadata;
                $cycle->save();
            });

            $sub = QuranSubscription::withoutGlobalScopes()->with('currentCycle')->find($subId);
            if ($sub) {
                $sub->total_sessions = (int) $sub->total_sessions + $bumpBy;
                $sub->save();
                $this->reconciler->sync($sub->fresh(['currentCycle']));
            }

            return [
                'cycle_id' => $cycleId,
                'action' => 'bump_total',
                'drift_after' => $this->driftCountForCycle($cycleId),
                'total_after' => $newTotal,
            ];
        }

        // forgive_n
        $n = (int) ($forgiveCount ?? 0);
        if ($n <= 0) {
            throw new \InvalidArgumentException('forgive_n requires a positive count');
        }
        if ($n > count($driftIds)) {
            throw new \InvalidArgumentException(sprintf(
                'forgive count (%d) exceeds drift count (%d) for cycle #%d',
                $n,
                count($driftIds),
                $cycleId,
            ));
        }

        $toForgive = QuranSession::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $driftIds)
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->limit($n)
            ->get(['id', 'subscription_counted']);

        DB::transaction(function () use ($cycle, $toForgive, $supervisorUserId, $note) {
            foreach ($toForgive as $session) {
                BackfillLog::create([
                    'bug_id' => self::BUG_ID,
                    'table_name' => 'quran_sessions',
                    'row_id' => $session->id,
                    'column_name' => 'subscription_counted',
                    'original_value' => $session->subscription_counted ? '1' : '0',
                    'new_value' => '0',
                    'backfill_command' => 'web:overflow-cycles-review',
                    'ran_at' => now(),
                ]);

                QuranSession::query()
                    ->withoutGlobalScopes()
                    ->whereKey($session->id)
                    ->update(['subscription_counted' => false]);
            }

            $metadata = (array) ($cycle->metadata ?? []);
            $metadata['overflow_review'] = [
                'action' => 'forgive_n',
                'forgiven_count' => $toForgive->count(),
                'forgiven_session_ids' => $toForgive->pluck('id')->all(),
                'reviewed_at' => now()->toDateTimeString(),
                'reviewed_by_user_id' => $supervisorUserId,
                'note' => $note ? mb_substr($note, 0, 500) : null,
            ];
            $cycle->metadata = $metadata;
            $cycle->save();
        });

        $sub = QuranSubscription::withoutGlobalScopes()->with('currentCycle')->find($subId);
        if ($sub) {
            $this->reconciler->sync($sub);
        }

        return [
            'cycle_id' => $cycleId,
            'action' => 'forgive_n',
            'drift_after' => $this->driftCountForCycle($cycleId),
            'total_after' => (int) $cycle->total_sessions,
        ];
    }

    private function driftSessionIdsForCycle(int $cycleId): array
    {
        return QuranSession::query()
            ->withoutGlobalScopes()
            ->where('subscription_cycle_id', $cycleId)
            ->where('subscription_counted', true)
            ->whereNotIn('id', function ($q) {
                $q->select('session_id')
                    ->from('session_consumption')
                    ->where('session_type', 'quran_session')
                    ->whereNull('reversed_at');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function driftCountForCycle(int $cycleId): int
    {
        return count($this->driftSessionIdsForCycle($cycleId));
    }
}
