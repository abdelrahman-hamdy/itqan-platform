<?php

declare(strict_types=1);

namespace App\Support\Subscriptions;

use App\Models\BaseSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionConsumption;
use Carbon\Carbon;

/**
 * Pure-logic conflict detector for the supervisor cycle editor (F2).
 *
 * Given a cycle row and an admin-supplied patch, returns the list of edits
 * that would break a v2 invariant or orphan dependent rows. Empty list = the
 * patch is safe to apply.
 *
 * Block-on-conflict policy per the plan: when conflicts exist the supervisor
 * controller returns the inspector page with banners + inline dependency-fix
 * actions, NEVER auto-cascades a write. This class has no DB writes — it
 * only inspects state.
 *
 * INV-A5 (queued.starts_at == active.ends_at), INV-B3 (sessions_used is
 * read-derived), INV-B4 (no negative sessions_remaining), and INV-A4 (cycles
 * don't time-overlap on the same subscription) are the invariants this
 * helper protects.
 */
final class CycleEditValidator
{
    public function __construct(
        private readonly SubscriptionConsumption $consumption,
    ) {}

    /**
     * Conflict shape:
     *
     *   [
     *     'code'    => 'CYCLE-EDIT-XXX',
     *     'field'   => 'starts_at' | 'ends_at' | 'total_sessions',
     *     'message' => 'human-readable description',
     *     'context' => [...],
     *   ]
     *
     * @param  array<string, mixed>  $patch  Subset of cycle columns (already validated by the form request).
     * @return array<int, array<string, mixed>>
     */
    public function validate(
        BaseSubscription $subscription,
        SubscriptionCycle $cycle,
        array $patch,
    ): array {
        $conflicts = [];

        if (array_key_exists('starts_at', $patch)) {
            $conflicts = array_merge($conflicts, $this->checkStartsAt($subscription, $cycle, $patch['starts_at']));
        }

        if (array_key_exists('ends_at', $patch)) {
            $conflicts = array_merge($conflicts, $this->checkEndsAt($subscription, $cycle, $patch['ends_at']));
        }

        if (array_key_exists('total_sessions', $patch)) {
            $conflicts = array_merge($conflicts, $this->checkTotalSessions($subscription, $cycle, (int) $patch['total_sessions']));
        }

        return $conflicts;
    }

    /**
     * starts_at:
     *   (a) Forward shift: block if a session anchored to this cycle has
     *       scheduled_at < new starts_at (would orphan past sessions out of
     *       window).
     *   (b) Backward shift: block if it crosses into a prior cycle's window
     *       on this subscription (INV-A4 — cycles don't overlap).
     *
     * @return array<int, array<string, mixed>>
     */
    private function checkStartsAt(BaseSubscription $subscription, SubscriptionCycle $cycle, mixed $rawStartsAt): array
    {
        $newStartsAt = $this->toCarbon($rawStartsAt);
        if ($newStartsAt === null) {
            return [];
        }

        $conflicts = [];

        // (a) Forward shift — any session before the new starts_at?
        if ($cycle->starts_at && $newStartsAt->gt($cycle->starts_at)) {
            $orphaned = $subscription->sessions()
                ->where('subscription_cycle_id', $cycle->id)
                ->where('scheduled_at', '<', $newStartsAt)
                ->count();

            if ($orphaned > 0) {
                $conflicts[] = [
                    'code' => 'CYCLE-EDIT-STARTS-FORWARD',
                    'field' => 'starts_at',
                    'message' => __('supervisor.subscriptions.cycle_edit_errors.starts_forward', [
                        'count' => $orphaned,
                    ]),
                    'context' => [
                        'cycle_id' => $cycle->id,
                        'orphaned_sessions' => $orphaned,
                        'new_starts_at' => $newStartsAt->toIso8601String(),
                    ],
                ];
            }
        }

        // (b) Backward shift — overlaps prior cycle on same subscription?
        if ($cycle->starts_at && $newStartsAt->lt($cycle->starts_at)) {
            $priorOverlap = SubscriptionCycle::query()
                ->where('subscribable_type', $subscription->getMorphClass())
                ->where('subscribable_id', $subscription->id)
                ->where('id', '!=', $cycle->id)
                ->where('ends_at', '>', $newStartsAt)
                ->where('starts_at', '<', $cycle->starts_at)
                ->exists();

            if ($priorOverlap) {
                $conflicts[] = [
                    'code' => 'CYCLE-EDIT-STARTS-OVERLAP',
                    'field' => 'starts_at',
                    'message' => __('supervisor.subscriptions.cycle_edit_errors.starts_overlap'),
                    'context' => [
                        'cycle_id' => $cycle->id,
                        'new_starts_at' => $newStartsAt->toIso8601String(),
                    ],
                ];
            }
        }

        return $conflicts;
    }

    /**
     * ends_at:
     *   (a) Backward shift: block if a session anchored to this cycle has
     *       scheduled_at > new ends_at.
     *   (b) Forward shift: block if a queued cycle exists with a session
     *       before the new ends_at (would overlap window).
     *   (c) Block if new ends_at <= now() AND a queued cycle exists — the
     *       advance-cycles cron would auto-promote prematurely.
     *
     * @return array<int, array<string, mixed>>
     */
    private function checkEndsAt(BaseSubscription $subscription, SubscriptionCycle $cycle, mixed $rawEndsAt): array
    {
        $newEndsAt = $this->toCarbon($rawEndsAt);
        if ($newEndsAt === null) {
            return [];
        }

        $conflicts = [];

        $queued = $subscription->queuedCycle()->first();
        $queuedAnchor = ($queued !== null && $queued->id !== $cycle->id) ? $queued : null;

        $strandedShift = $cycle->ends_at && $newEndsAt->lt($cycle->ends_at);
        $forwardShift = $cycle->ends_at && $newEndsAt->gt($cycle->ends_at);

        // (a) Stranded-future (backward) and (b) queued-overlap (forward) both
        // hit `cycle_sessions` with different filters. Merge into one
        // grouped-by-cycle query when both might fire so we pay 1 roundtrip
        // instead of 2.
        $strandedFuture = 0;
        $queuedSessionsInOverlap = 0;

        if ($strandedShift || ($forwardShift && $queuedAnchor)) {
            $cycleIds = array_filter([
                $strandedShift ? $cycle->id : null,
                ($forwardShift && $queuedAnchor) ? $queuedAnchor->id : null,
            ]);

            $counts = $subscription->sessions()
                ->whereIn('subscription_cycle_id', $cycleIds)
                ->where(function ($q) use ($cycle, $queuedAnchor, $newEndsAt, $strandedShift, $forwardShift) {
                    if ($strandedShift) {
                        $q->orWhere(function ($q1) use ($cycle, $newEndsAt) {
                            $q1->where('subscription_cycle_id', $cycle->id)
                                ->where('scheduled_at', '>', $newEndsAt);
                        });
                    }
                    if ($forwardShift && $queuedAnchor) {
                        $q->orWhere(function ($q1) use ($queuedAnchor, $newEndsAt) {
                            $q1->where('subscription_cycle_id', $queuedAnchor->id)
                                ->where('scheduled_at', '<', $newEndsAt);
                        });
                    }
                })
                ->selectRaw('subscription_cycle_id, count(*) as cnt')
                ->groupBy('subscription_cycle_id')
                ->pluck('cnt', 'subscription_cycle_id');

            $strandedFuture = $strandedShift ? (int) ($counts[$cycle->id] ?? 0) : 0;
            $queuedSessionsInOverlap = ($forwardShift && $queuedAnchor) ? (int) ($counts[$queuedAnchor->id] ?? 0) : 0;
        }

        // (a) Backward shift — sessions scheduled past the new ends_at?
        if ($strandedFuture > 0) {
            $conflicts[] = [
                'code' => 'CYCLE-EDIT-ENDS-BACKWARD',
                'field' => 'ends_at',
                'message' => __('supervisor.subscriptions.cycle_edit_errors.ends_backward', [
                    'count' => $strandedFuture,
                ]),
                'context' => [
                    'cycle_id' => $cycle->id,
                    'stranded_sessions' => $strandedFuture,
                    'new_ends_at' => $newEndsAt->toIso8601String(),
                ],
            ];
        }

        if ($queuedAnchor !== null) {
            // (b) Forward shift past queued.starts_at would window-overlap.
            if ($queuedSessionsInOverlap > 0) {
                $conflicts[] = [
                    'code' => 'CYCLE-EDIT-ENDS-FORWARD-QUEUED',
                    'field' => 'ends_at',
                    'message' => __('supervisor.subscriptions.cycle_edit_errors.ends_forward_queued', [
                        'count' => $queuedSessionsInOverlap,
                    ]),
                    'context' => [
                        'cycle_id' => $cycle->id,
                        'queued_cycle_id' => $queuedAnchor->id,
                        'overlapping_sessions' => $queuedSessionsInOverlap,
                        'new_ends_at' => $newEndsAt->toIso8601String(),
                    ],
                ];
            }

            // (c) Premature auto-promote.
            if ($newEndsAt->lte(Carbon::now())) {
                $conflicts[] = [
                    'code' => 'CYCLE-EDIT-ENDS-PREMATURE-PROMOTE',
                    'field' => 'ends_at',
                    'message' => __('supervisor.subscriptions.cycle_edit_errors.ends_premature_promote'),
                    'context' => [
                        'cycle_id' => $cycle->id,
                        'queued_cycle_id' => $queuedAnchor->id,
                        'new_ends_at' => $newEndsAt->toIso8601String(),
                    ],
                ];
            }
        }

        return $conflicts;
    }

    /**
     * total_sessions:
     *   (a) Block if new < (live active consumption count) — INV-B4 + INV-B3.
     *   (b) Block if new < (active consumption count + future scheduled
     *       sessions on this cycle) — moving the quota below the operator's
     *       own plan corrupts the cycle.
     *
     * @return array<int, array<string, mixed>>
     */
    private function checkTotalSessions(BaseSubscription $subscription, SubscriptionCycle $cycle, int $newTotal): array
    {
        $conflicts = [];

        // INV-B3 canonical count source — delegates to the same query the
        // reconciler uses so the definition can't drift here.
        $activeConsumed = $this->consumption->cycleConsumedCount($cycle);

        if ($newTotal < $activeConsumed) {
            $conflicts[] = [
                'code' => 'CYCLE-EDIT-TOTAL-LT-USED',
                'field' => 'total_sessions',
                'message' => __('supervisor.subscriptions.cycle_edit_errors.total_lt_used', [
                    'used' => $activeConsumed,
                    'reverse' => $activeConsumed - $newTotal,
                ]),
                'context' => [
                    'cycle_id' => $cycle->id,
                    'requested_total' => $newTotal,
                    'active_consumption_rows' => $activeConsumed,
                ],
            ];
        }

        $futureScheduled = $subscription->sessions()
            ->where('subscription_cycle_id', $cycle->id)
            ->where('scheduled_at', '>', Carbon::now())
            ->whereIn('status', [\App\Enums\SessionStatus::SCHEDULED->value, \App\Enums\SessionStatus::READY->value])
            ->count();

        if ($newTotal < $activeConsumed + $futureScheduled) {
            $conflicts[] = [
                'code' => 'CYCLE-EDIT-TOTAL-LT-COMMITTED',
                'field' => 'total_sessions',
                'message' => __('supervisor.subscriptions.cycle_edit_errors.total_lt_committed', [
                    'used' => $activeConsumed,
                    'future' => $futureScheduled,
                ]),
                'context' => [
                    'cycle_id' => $cycle->id,
                    'requested_total' => $newTotal,
                    'active_consumption_rows' => $activeConsumed,
                    'future_scheduled' => $futureScheduled,
                ],
            ];
        }

        return $conflicts;
    }

    /**
     * Accepts Carbon, string, or null. Returns null on null/empty so the
     * caller can skip the field cleanly.
     */
    private function toCarbon(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse((string) $value);
    }
}
