<?php

namespace App\Services\Subscription;

use App\Models\BackfillLog;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Service for the supervisor preset-sessions review flow surfaced at
 * /manage/preset-sessions-review.
 *
 * Lists every admin-wizard-created subscription whose current cycle does
 * NOT carry the pre-platform preserved-offset metadata (sub-1153 bug).
 * Each row exposes a single field where the supervisor enters the actual
 * pre-platform consumption count. The submitter:
 *   - stamps cycle.metadata.pre_platform_consumption_preserved + preserved_value
 *   - writes cycle.sessions_used = (active consumption count + preserved_value)
 *   - reconciles the parent subscription
 *   - records a BackfillLog row for rollback
 *
 * Reviewed subs automatically fall off the at-risk list because the
 * metadata flag is the predicate.
 */
class PresetSessionsReviewService
{
    /**
     * Returns the list of subs that need supervisor review.
     *
     * @return list<array<string, mixed>>
     */
    public function atRiskSubs(): array
    {
        $rows = DB::select('
            SELECT s.id AS sub_id, s.student_id, s.quran_teacher_id AS teacher_id,
                   s.total_sessions, s.sessions_used AS sub_used, s.status,
                   s.starts_at, s.ends_at, s.created_at AS sub_created_at,
                   c.id AS cycle_id, c.sessions_used AS cycle_used,
                   c.payment_status AS cycle_payment, c.metadata AS cycle_metadata,
                   c.created_at AS cycle_created_at
            FROM quran_subscriptions s
            INNER JOIN subscription_cycles c ON c.id = s.current_cycle_id
            WHERE s.purchase_source = "admin"
              AND c.metadata LIKE "%materialized_from_subscription%"
              AND c.metadata NOT LIKE "%pre_platform_consumption_preserved%"
              AND c.metadata NOT LIKE "%unaccounted_sessions_used%"
              AND s.deleted_at IS NULL
            ORDER BY c.created_at DESC
        ');

        $studentIds = array_unique(array_map(fn ($r) => (int) $r->student_id, $rows));
        $teacherIds = array_filter(array_unique(array_map(fn ($r) => (int) $r->teacher_id, $rows)));
        $userIds = array_merge($studentIds, $teacherIds);

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        $consumptionCounts = SessionConsumption::query()
            ->whereIn('subscription_id', array_map(fn ($r) => (int) $r->sub_id, $rows))
            ->where('subscription_type', 'quran_subscription')
            ->whereNull('reversed_at')
            ->groupBy('subscription_id')
            ->selectRaw('subscription_id, COUNT(*) AS n')
            ->pluck('n', 'subscription_id');

        return array_map(function ($r) use ($users, $consumptionCounts) {
            return [
                'sub_id' => (int) $r->sub_id,
                'student_id' => (int) $r->student_id,
                'student_name' => $users[$r->student_id]->name ?? '#'.$r->student_id,
                'teacher_id' => $r->teacher_id ? (int) $r->teacher_id : null,
                'teacher_name' => $r->teacher_id && isset($users[$r->teacher_id]) ? $users[$r->teacher_id]->name : null,
                'total_sessions' => (int) $r->total_sessions,
                'sub_sessions_used' => (int) $r->sub_used,
                'cycle_id' => (int) $r->cycle_id,
                'cycle_sessions_used' => (int) $r->cycle_used,
                'active_consumption' => (int) ($consumptionCounts[$r->sub_id] ?? 0),
                'status' => $r->status,
                'cycle_payment_status' => $r->cycle_payment,
                'starts_at' => $r->starts_at,
                'ends_at' => $r->ends_at,
                'sub_created_at' => $r->sub_created_at,
            ];
        }, $rows);
    }

    /**
     * Apply a supervisor's preset decision: stamp the cycle metadata,
     * write sessions_used, and re-mirror the subscription.
     *
     * Returns the new sub state (used + remaining) so the controller can
     * surface it back to the supervisor.
     *
     * @return array{sub_id:int, sessions_used:int, sessions_remaining:int}
     */
    public function recordDecision(
        int $subId,
        int $preservedValue,
        int $supervisorUserId,
        ?string $note = null,
    ): array {
        $sub = QuranSubscription::query()->withoutGlobalScopes()->findOrFail($subId);
        $cycle = SubscriptionCycle::query()->findOrFail($sub->current_cycle_id);

        if ($preservedValue < 0) {
            throw new \InvalidArgumentException('preserved_value must be >= 0');
        }
        if ($preservedValue >= (int) $sub->total_sessions) {
            throw new \InvalidArgumentException(sprintf(
                'preserved_value (%d) must be less than total_sessions (%d)',
                $preservedValue,
                $sub->total_sessions,
            ));
        }

        $activeConsumption = SessionConsumption::query()
            ->where('subscription_id', $sub->getKey())
            ->where('subscription_type', $sub->getMorphClass())
            ->whereNull('reversed_at')
            ->count();

        $newCycleUsed = $activeConsumption + $preservedValue;

        DB::transaction(function () use ($sub, $cycle, $preservedValue, $newCycleUsed, $supervisorUserId, $note) {
            $originalMetadata = $cycle->metadata;
            $originalSessionsUsed = (int) $cycle->sessions_used;

            $metadata = (array) ($cycle->metadata ?? []);
            $metadata['pre_platform_consumption_preserved'] = true;
            $metadata['preserved_value'] = $preservedValue;
            $metadata['preserved_at'] = now()->toDateTimeString();
            $metadata['preserved_source'] = 'supervisor_review';
            $metadata['preserved_by_user_id'] = $supervisorUserId;
            if ($note !== null && $note !== '') {
                $metadata['preserved_note'] = mb_substr($note, 0, 500);
            }

            BackfillLog::create([
                'bug_id' => 'preset-review-2026-05-16',
                'table_name' => 'subscription_cycles',
                'row_id' => $cycle->id,
                'column_name' => 'sessions_used_metadata',
                'original_value' => json_encode([
                    'sessions_used' => $originalSessionsUsed,
                    'metadata' => $originalMetadata,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_value' => json_encode([
                    'sessions_used' => $newCycleUsed,
                    'metadata' => $metadata,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'backfill_command' => 'web:preset-sessions-review',
                'ran_at' => now(),
            ]);

            $cycle->metadata = $metadata;
            $cycle->sessions_used = $newCycleUsed;
            $cycle->save();

            app(SubscriptionReconciler::class)->sync($sub->fresh(['currentCycle']));
        });

        $fresh = $sub->fresh();

        return [
            'sub_id' => $fresh->id,
            'sessions_used' => (int) $fresh->sessions_used,
            'sessions_remaining' => max(0, (int) $fresh->total_sessions - (int) $fresh->sessions_used),
        ];
    }
}
