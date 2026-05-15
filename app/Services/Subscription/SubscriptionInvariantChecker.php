<?php

namespace App\Services\Subscription;

use App\Enums\BillingCycle;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionAuditLog;
use App\Models\SubscriptionCycle;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * SubscriptionInvariantChecker — runs the numbered invariants from
 * docs/subscription-invariants.md §2 against a single subscription and
 * returns a list of structured violations.
 *
 * Phase A.4 deliverable (Phase A.3 left a stub returning []).
 *
 * Violation shape (machine-readable; Phase D's dashboard reads this):
 *   [
 *       'code'     => 'INV-A1',
 *       'severity' => 'error' | 'warning' | 'info',
 *       'message'  => 'Human-readable explanation',
 *       'context'  => [...field-by-field detail...],
 *   ]
 *
 * Each invariant group is implemented as a discrete private method so the
 * orchestrator can compose them cleanly and the daily report
 * (`subscriptions:invariant-check`) can attribute violations to a group.
 *
 * Read-only — this class NEVER mutates subscription state. SubscriptionReconciler
 * is the sole writer of derived fields; the checker only inspects.
 *
 * Group I (request-time scheduling auth) is enforced in middleware/policies —
 * not in subscription state — so it is intentionally NOT checked here.
 * Group J (UX surface contract) is a per-view rendering rule, not a model
 * invariant, so it is also out of scope.
 *
 * Group D is skipped for CourseSubscription rows because Course has no
 * `package()` relation and no cycle-pricing snapshot in the v2 contract.
 */
class SubscriptionInvariantChecker
{
    /**
     * Allowed pricing_source values per INV-D1.
     */
    private const PRICING_SOURCES = ['package', 'sale_price', 'manual_override'];

    /**
     * Subscription-row fields the reconciler mirrors from currentCycle.
     * INV-A1 asserts each one matches the cycle's corresponding column.
     *
     * Keys = subscription column, values = cycle column.
     */
    private const MIRRORED_FIELDS = [
        'sessions_used' => 'sessions_used',
        'total_sessions' => 'total_sessions',
        'starts_at' => 'starts_at',
        'ends_at' => 'ends_at',
    ];

    /**
     * Run every invariant group against $sub.
     *
     * @return array<int, array<string, mixed>> List of violations; empty array = all invariants hold.
     */
    public function check(BaseSubscription $sub): array
    {
        $violations = [];

        // Course subs don't carry session quotas / cycles in the v2 model;
        // they're guarded by guard methods inside each group.
        $violations = array_merge($violations, $this->checkGroupA($sub));
        $violations = array_merge($violations, $this->checkGroupB($sub));
        $violations = array_merge($violations, $this->checkGroupC($sub));
        $violations = array_merge($violations, $this->checkGroupD($sub));
        $violations = array_merge($violations, $this->checkGroupE($sub));
        $violations = array_merge($violations, $this->checkGroupF($sub));
        $violations = array_merge($violations, $this->checkGroupG($sub));
        $violations = array_merge($violations, $this->checkGroupH($sub));
        $violations = array_merge($violations, $this->checkGroupJ($sub));

        return $violations;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Group A — Canonical state (R1)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function checkGroupA(BaseSubscription $sub): array
    {
        $violations = [];
        $currentCycle = $this->loadCurrentCycle($sub);

        // INV-A1: subscription row mirrors currentCycle.
        if ($currentCycle instanceof SubscriptionCycle) {
            foreach (self::MIRRORED_FIELDS as $subField => $cycleField) {
                $subValue = $sub->getAttribute($subField);
                $cycleValue = $currentCycle->getAttribute($cycleField);
                if (! $this->mirrorEquals($subValue, $cycleValue)) {
                    $violations[] = $this->violation(
                        'INV-A1',
                        sprintf(
                            'Subscription.%s does not mirror currentCycle.%s.',
                            $subField,
                            $cycleField,
                        ),
                        [
                            'subscription_id' => $sub->getKey(),
                            'cycle_id' => $currentCycle->getKey(),
                            'field' => $subField,
                            'sub_value' => $this->scalarize($subValue),
                            'cycle_value' => $this->scalarize($cycleValue),
                        ],
                    );
                }
            }

            // payment_status mapping (cycle string → enum) — checked through enum
            // mapping so we tolerate either enum or string on the cycle column.
            $expectedSubPaymentStatus = $this->mapCyclePaymentStatusToEnum($currentCycle->payment_status);
            $actualSubPaymentStatus = $sub->payment_status instanceof SubscriptionPaymentStatus
                ? $sub->payment_status
                : SubscriptionPaymentStatus::tryFrom((string) $sub->payment_status);

            if ($expectedSubPaymentStatus !== $actualSubPaymentStatus) {
                $violations[] = $this->violation(
                    'INV-A1',
                    'Subscription.payment_status does not mirror currentCycle.payment_status.',
                    [
                        'subscription_id' => $sub->getKey(),
                        'cycle_id' => $currentCycle->getKey(),
                        'sub_payment_status' => $actualSubPaymentStatus?->value,
                        'cycle_payment_status' => $currentCycle->payment_status,
                    ],
                );
            }

            // sessions_remaining must equal total - used (cross-checked from cycle).
            $expectedRemaining = max(0, (int) $currentCycle->total_sessions - (int) $currentCycle->sessions_used);
            if ((int) ($sub->sessions_remaining ?? 0) !== $expectedRemaining) {
                $violations[] = $this->violation(
                    'INV-A1',
                    'Subscription.sessions_remaining does not match (total - used) on currentCycle.',
                    [
                        'subscription_id' => $sub->getKey(),
                        'cycle_id' => $currentCycle->getKey(),
                        'sub_sessions_remaining' => (int) ($sub->sessions_remaining ?? 0),
                        'expected' => $expectedRemaining,
                    ],
                );
            }
        }

        // INV-A2: no lie state — sub.payment_status=PAID AND cycle.payment_status=PENDING
        // while status is ACTIVE.
        if ($currentCycle instanceof SubscriptionCycle
            && $sub->status === SessionSubscriptionStatus::ACTIVE
            && $sub->payment_status === SubscriptionPaymentStatus::PAID
            && $currentCycle->payment_status === SubscriptionCycle::PAYMENT_PENDING) {
            $violations[] = $this->violation(
                'INV-A2',
                'Lie state: subscription claims PAID but currentCycle is PENDING.',
                [
                    'subscription_id' => $sub->getKey(),
                    'cycle_id' => $currentCycle->getKey(),
                ],
            );
        }

        // INV-A3: at most one cycle in STATE_ACTIVE.
        $activeCycleCount = $this->cyclesQuery($sub)
            ->where('cycle_state', SubscriptionCycle::STATE_ACTIVE)
            ->count();
        if ($activeCycleCount > 1) {
            $violations[] = $this->violation(
                'INV-A3',
                'More than one cycle is in cycle_state=active.',
                [
                    'subscription_id' => $sub->getKey(),
                    'active_cycle_count' => $activeCycleCount,
                ],
            );
        }

        // INV-A4: at most one cycle in STATE_QUEUED.
        $queuedCycleCount = $this->cyclesQuery($sub)
            ->where('cycle_state', SubscriptionCycle::STATE_QUEUED)
            ->count();
        if ($queuedCycleCount > 1) {
            $violations[] = $this->violation(
                'INV-A4',
                'More than one cycle is in cycle_state=queued.',
                [
                    'subscription_id' => $sub->getKey(),
                    'queued_cycle_count' => $queuedCycleCount,
                ],
            );
        }

        // INV-A5: queued.starts_at == current.ends_at (cycle continuity).
        $queued = $this->cyclesQuery($sub)
            ->where('cycle_state', SubscriptionCycle::STATE_QUEUED)
            ->first();
        if ($queued instanceof SubscriptionCycle
            && $currentCycle instanceof SubscriptionCycle
            && $currentCycle->ends_at !== null
            && $queued->starts_at !== null
            && ! $this->datesEqual($queued->starts_at, $currentCycle->ends_at)) {
            $violations[] = $this->violation(
                'INV-A5',
                'Queued cycle starts_at does not equal currentCycle ends_at (cycle discontinuity).',
                [
                    'subscription_id' => $sub->getKey(),
                    'current_cycle_id' => $currentCycle->getKey(),
                    'queued_cycle_id' => $queued->getKey(),
                    'current_ends_at' => $this->scalarize($currentCycle->ends_at),
                    'queued_starts_at' => $this->scalarize($queued->starts_at),
                ],
            );
        }

        // INV-A6: starts_at/ends_at must not be NULL once the sub has been
        // activated. Heuristic per spec: any sub whose status is not PENDING
        // (the only "never activated" state in the session-status enum) MUST
        // have both dates populated. CourseSubscription uses EnrollmentStatus
        // so we resolve via getPendingStatus() to stay enum-typed.
        if (! $this->isInPendingState($sub)) {
            if ($sub->starts_at === null || $sub->ends_at === null) {
                $violations[] = $this->violation(
                    'INV-A6',
                    'Subscription has been activated but starts_at/ends_at is NULL.',
                    [
                        'subscription_id' => $sub->getKey(),
                        'status' => $this->statusValue($sub),
                        'starts_at' => $this->scalarize($sub->starts_at),
                        'ends_at' => $this->scalarize($sub->ends_at),
                    ],
                );
            }
        }

        // INV-A7: deterministic SubscriptionViewState — enforced by virtue of
        // viewStateFor() being a pure function. Not checkable from data alone;
        // documented here intentionally and covered by Phase B property tests.

        return $violations;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Group B — Counting (R2)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function checkGroupB(BaseSubscription $sub): array
    {
        $violations = [];

        if ($sub instanceof CourseSubscription) {
            // Course subs do not use session_consumption / cycles.
            return $violations;
        }

        $subscriptionType = $sub->getMorphClass();
        $subscriptionId = $sub->getKey();

        // INV-B1: at most one row per (session, subscription). The unique key
        // enforces this at DB level, but we surface dup rows defensively in
        // case the index was ever dropped.
        $duplicates = SessionConsumption::query()
            ->select(['session_id', 'session_type', DB::raw('COUNT(*) as cnt')])
            ->where('subscription_id', $subscriptionId)
            ->where('subscription_type', $subscriptionType)
            ->groupBy('session_id', 'session_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $violations[] = $this->violation(
                'INV-B1',
                'Multiple session_consumption rows exist for the same (session, subscription) pair.',
                [
                    'subscription_id' => $subscriptionId,
                    'session_id' => (int) $dup->session_id,
                    'session_type' => $dup->session_type,
                    'count' => (int) $dup->cnt,
                ],
            );
        }

        // INV-B2: orphan `subscription_counted` flags — sessions marked counted
        // on the legacy boolean but with NO active session_consumption row.
        // Best-effort: only run when the legacy column exists (migration window).
        $sessionsRel = method_exists($sub, 'sessions') ? $sub->sessions() : null;
        if ($sessionsRel !== null) {
            $sessionsTable = $sessionsRel->getRelated()->getTable();
            if (Schema::hasColumn($sessionsTable, 'subscription_counted')) {
                try {
                    $sessionMorph = $sessionsRel->getRelated()->getMorphClass();
                    $flaggedIds = $sessionsRel
                        ->getQuery()
                        ->where('subscription_counted', true)
                        ->pluck('id');
                    if ($flaggedIds->isNotEmpty()) {
                        $countedIds = SessionConsumption::query()
                            ->where('subscription_id', $subscriptionId)
                            ->where('subscription_type', $subscriptionType)
                            ->where('session_type', $sessionMorph)
                            ->whereIn('session_id', $flaggedIds->all())
                            ->whereNull('reversed_at')
                            ->pluck('session_id');
                        $missing = $flaggedIds->diff($countedIds);
                        if ($missing->isNotEmpty()) {
                            $violations[] = $this->violation(
                                'INV-B2',
                                'Sessions flagged subscription_counted=true with no active session_consumption row.',
                                [
                                    'subscription_id' => $subscriptionId,
                                    'session_type' => $sessionMorph,
                                    'orphan_session_ids' => array_values(array_slice($missing->all(), 0, 25)),
                                    'orphan_count' => $missing->count(),
                                ],
                            );
                        }
                    }
                } catch (Throwable $e) {
                    // Legacy schema/column drift — surface as info, don't fail.
                    $violations[] = $this->violation(
                        'INV-B2',
                        'Could not evaluate orphan subscription_counted flags (legacy schema mismatch).',
                        [
                            'subscription_id' => $subscriptionId,
                            'error' => $e->getMessage(),
                        ],
                        severity: 'info',
                    );
                }
            }
        }

        // INV-B3 + INV-B4: per-cycle counter integrity.
        $cycles = $this->cyclesQuery($sub)->get();
        foreach ($cycles as $cycle) {
            /** @var SubscriptionCycle $cycle */
            $actualActive = SessionConsumption::query()
                ->where('cycle_id', $cycle->getKey())
                ->whereNull('reversed_at')
                ->count();

            // INV-B3 — softened to severity=warning for pre-v2-flip cycles
            // that haven't been backfilled into session_consumption yet. The
            // legacy attendance writer never produced consumption rows for
            // them, so a mismatch is the EXPECTED data shape until backfill.
            if ((int) $cycle->sessions_used !== $actualActive) {
                $violations[] = $this->violation(
                    'INV-B3',
                    'cycle.sessions_used does not equal COUNT(session_consumption WHERE cycle_id=? AND reversed_at IS NULL).',
                    [
                        'subscription_id' => $subscriptionId,
                        'cycle_id' => $cycle->getKey(),
                        'cycle_sessions_used' => (int) $cycle->sessions_used,
                        'active_consumption_count' => $actualActive,
                        'legacy_cycle' => $this->isLegacyConsumptionCycle($cycle),
                    ],
                    severity: $this->isLegacyConsumptionCycle($cycle) ? 'warning' : 'error',
                );
            }

            // INV-B4
            $expectedRemaining = (int) $cycle->total_sessions - (int) $cycle->sessions_used;
            if ($expectedRemaining < 0) {
                $violations[] = $this->violation(
                    'INV-B4',
                    'cycle.sessions_used exceeds total_sessions (negative remaining).',
                    [
                        'subscription_id' => $subscriptionId,
                        'cycle_id' => $cycle->getKey(),
                        'total_sessions' => (int) $cycle->total_sessions,
                        'sessions_used' => (int) $cycle->sessions_used,
                        'computed_remaining' => $expectedRemaining,
                    ],
                );
            }

            // INV-B6: counters never reset on promotion.
            // Heuristic: a cycle with cycle_number > 1 and sessions_used == 0
            // is suspicious IF there are session_consumption rows that target
            // it AND those rows are all reversed (i.e. the counter dropped to
            // zero from a non-zero value while the cycle was being promoted).
            if ((int) $cycle->cycle_number > 1
                && (int) $cycle->sessions_used === 0
                && (int) $cycle->total_sessions > 0) {
                $hasAnyConsumption = SessionConsumption::query()
                    ->where('cycle_id', $cycle->getKey())
                    ->exists();
                if ($hasAnyConsumption && $actualActive === 0) {
                    $violations[] = $this->violation(
                        'INV-B6',
                        'Cycle has consumption history but sessions_used is 0 — possible counter reset on promotion.',
                        [
                            'subscription_id' => $subscriptionId,
                            'cycle_id' => $cycle->getKey(),
                            'cycle_number' => (int) $cycle->cycle_number,
                        ],
                        severity: 'warning',
                    );
                }
            }
        }

        // INV-B5: atomic reversal — reversed_at, reversed_reason, reversed_by_user_id
        // must all be populated together (or all null). Partial reversal forbidden.
        $partials = SessionConsumption::query()
            ->where('subscription_id', $subscriptionId)
            ->where('subscription_type', $subscriptionType)
            ->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNotNull('reversed_at')
                        ->where(function ($qqq) {
                            $qqq->whereNull('reversed_reason')
                                ->orWhereNull('reversed_by_user_id');
                        });
                })->orWhere(function ($qq) {
                    $qq->whereNull('reversed_at')
                        ->where(function ($qqq) {
                            $qqq->whereNotNull('reversed_reason')
                                ->orWhereNotNull('reversed_by_user_id');
                        });
                });
            })
            ->limit(25)
            ->pluck('id');

        foreach ($partials as $partialId) {
            $violations[] = $this->violation(
                'INV-B5',
                'session_consumption row has partial reversal fields (must be all-or-nothing).',
                [
                    'subscription_id' => $subscriptionId,
                    'session_consumption_id' => (int) $partialId,
                ],
            );
        }

        return $violations;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Group C — Cron + interleaving (R3)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * INV-C1/C2/C3 are runtime-enforcement properties:
     *   - C1: architecture test asserts every mutator wraps in SubscriptionLock::for().
     *   - C2: SubscriptionReconciler::sync() is the last call in every locked block;
     *         enforced by code review + Pest architecture test.
     *   - C3: cron commands acquire the per-sub lock with a ≤2s wait timeout;
     *         on timeout they audit-log `cron_skipped_locked`.
     *
     * This checker can only do a best-effort audit of recent audit-log activity:
     * scan the last 24h of subscription_audit_log for mutator entries on this sub
     * and surface any that look suspicious (e.g. a mutation that lacks the
     * paired lock-acquisition envelope). Surfaces as severity=info because the
     * audit log isn't 100% authoritative for lock acquisition — Phase D will
     * tighten this once SubscriptionLock emits its own log lines.
     *
     * @return array<int, array<string, mixed>>
     */
    private function checkGroupC(BaseSubscription $sub): array
    {
        $violations = [];

        if (! Schema::hasTable('subscription_audit_log')) {
            return $violations;
        }

        try {
            $recent = SubscriptionAuditLog::query()
                ->forSubscription($sub)
                ->where('created_at', '>=', now()->subHours(24))
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(['action', 'source', 'created_at', 'has_violations']);

            $mutators = $recent->filter(function ($row) {
                return ! in_array($row->action, ['view', 'noop', 'cron_skipped_locked'], true);
            });

            foreach ($mutators as $row) {
                if ($row->has_violations) {
                    $violations[] = $this->violation(
                        'INV-C2',
                        'Subscription mutator in last 24h committed despite invariant violations.',
                        [
                            'subscription_id' => $sub->getKey(),
                            'action' => $row->action,
                            'source' => $row->source,
                            'created_at' => $row->created_at?->toIso8601String(),
                        ],
                        severity: 'info',
                    );
                }
            }
        } catch (Throwable $e) {
            // Audit-log read shouldn't break the checker; surface as info.
            $violations[] = $this->violation(
                'INV-C2',
                'Could not read subscription_audit_log for lock/audit cross-check.',
                [
                    'subscription_id' => $sub->getKey(),
                    'error' => $e->getMessage(),
                ],
                severity: 'info',
            );
        }

        return $violations;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Group D — Pricing trust (R4)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Course subscriptions are intentionally skipped — they have no package()
     * relation and the v2 contract scopes Group D to session-based subs.
     *
     * @return array<int, array<string, mixed>>
     */
    private function checkGroupD(BaseSubscription $sub): array
    {
        $violations = [];

        if ($sub instanceof CourseSubscription) {
            return $violations;
        }

        $cycles = $this->cyclesQuery($sub)->get();
        if ($cycles->isEmpty()) {
            return $violations;
        }

        // PricingResolver::resolvePriceFromPackage is a pure static helper.
        // We use `app(...)` only as the contract requested entry point for
        // course-sub gracefulness; the actual call site uses ::resolveExpectedPackagePrice
        // which handles missing package gracefully.
        $package = method_exists($sub, 'package') ? $sub->package : null;

        foreach ($cycles as $cycle) {
            /** @var SubscriptionCycle $cycle */

            // INV-D1: pricing_source not null and in enum.
            $pricingSource = $cycle->pricing_source ?? null;
            if (! is_string($pricingSource) || ! in_array($pricingSource, self::PRICING_SOURCES, true)) {
                $violations[] = $this->violation(
                    'INV-D1',
                    'cycle.pricing_source is null or not in allowed enum.',
                    [
                        'subscription_id' => $sub->getKey(),
                        'cycle_id' => $cycle->getKey(),
                        'pricing_source' => $pricingSource,
                        'allowed' => self::PRICING_SOURCES,
                    ],
                );

                // No further D checks for this cycle — its source label is unknown.
                continue;
            }

            // INV-D2: source-specific contract.
            if ($pricingSource === 'package') {
                // Compute expected price from the package snapshot. Fall back
                // to the live package only when the snapshot is missing (older
                // rows). Either way: a non-zero diff means the cycle's price
                // is at odds with the recorded package source.
                $expected = $this->resolveExpectedPackagePrice($cycle, $package);
                if ($expected !== null) {
                    $actual = (float) $cycle->final_price;
                    if (! $this->priceEquals($actual, $expected)) {
                        $violations[] = $this->violation(
                            'INV-D2',
                            'cycle.final_price disagrees with PricingResolver while pricing_source=package.',
                            [
                                'subscription_id' => $sub->getKey(),
                                'cycle_id' => $cycle->getKey(),
                                'actual_final_price' => $actual,
                                'expected_final_price' => $expected,
                                'discount_amount' => (float) ($cycle->discount_amount ?? 0),
                                'billing_cycle' => $cycle->billing_cycle,
                            ],
                        );
                    }
                }
            } else {
                // sale_price | manual_override → both override fields required.
                $reason = $cycle->pricing_override_reason ?? null;
                $actorId = $cycle->pricing_override_actor_id ?? null;
                if (! is_string($reason) || $reason === '' || $actorId === null) {
                    $violations[] = $this->violation(
                        'INV-D2',
                        'cycle.pricing_source is an override but reason/actor are not both populated.',
                        [
                            'subscription_id' => $sub->getKey(),
                            'cycle_id' => $cycle->getKey(),
                            'pricing_source' => $pricingSource,
                            'pricing_override_reason' => $reason,
                            'pricing_override_actor_id' => $actorId,
                        ],
                    );
                }
            }

            // INV-D3: non-negative final_price + currency matches academy.
            $finalPrice = (float) ($cycle->final_price ?? 0);
            if ($finalPrice < 0) {
                $violations[] = $this->violation(
                    'INV-D3',
                    'cycle.final_price is negative.',
                    [
                        'subscription_id' => $sub->getKey(),
                        'cycle_id' => $cycle->getKey(),
                        'final_price' => $finalPrice,
                    ],
                );
            }
            $academyCurrency = $sub->academy?->currency;
            if ($academyCurrency !== null && $cycle->currency !== null) {
                $academyCurrencyString = $academyCurrency instanceof \BackedEnum
                    ? (string) $academyCurrency->value
                    : (string) $academyCurrency;
                if (strtoupper((string) $cycle->currency) !== strtoupper($academyCurrencyString)) {
                    $violations[] = $this->violation(
                        'INV-D3',
                        'cycle.currency does not match academy currency.',
                        [
                            'subscription_id' => $sub->getKey(),
                            'cycle_id' => $cycle->getKey(),
                            'cycle_currency' => $cycle->currency,
                            'academy_currency' => $academyCurrencyString,
                        ],
                        severity: 'warning',
                    );
                }
            }

            // INV-D4: snapshot integrity — currently the `package_id` column on
            // the cycle IS the snapshot reference. A live package price drift
            // is expected behaviour per the doc (historical cycles keep their
            // old final_price), so we only flag when the package_id is null
            // for a 'package'-sourced cycle (means we lost the snapshot link).
            //
            // Softened to severity=warning for legacy cycles: the
            // 2026_05_14 pricing_trust migration defaulted pricing_source
            // to 'package' across the historical population without
            // backfilling package_id. Until 2026_05_15_000002 backfill runs,
            // these are EXPECTED to NULL out — flagging error would drown
            // the inspector in noise.
            if ($pricingSource === 'package' && $cycle->package_id === null) {
                $violations[] = $this->violation(
                    'INV-D4',
                    'cycle has pricing_source=package but package_id snapshot is NULL.',
                    [
                        'subscription_id' => $sub->getKey(),
                        'cycle_id' => $cycle->getKey(),
                        'legacy_cycle' => $this->isLegacyConsumptionCycle($cycle),
                    ],
                    severity: $this->isLegacyConsumptionCycle($cycle) ? 'warning' : 'error',
                );
            }
        }

        return $violations;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Group E — Package-change propagation (R5)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * INV-E1: every future scheduled session pointing at a cycle has
     *         duration_minutes == anchor cycle's package.session_duration_minutes.
     * INV-E2 + INV-E3 are job-output assertions — covered indirectly: if E1
     * fails, the recommended remediation is to run RebuildFutureSessionsForCycle
     * for the affected cycle.
     *
     * @return array<int, array<string, mixed>>
     */
    private function checkGroupE(BaseSubscription $sub): array
    {
        $violations = [];

        if ($sub instanceof CourseSubscription) {
            return $violations;
        }

        if (! method_exists($sub, 'sessions')) {
            return $violations;
        }

        $sessionsRel = $sub->sessions();
        $sessionsTable = $sessionsRel->getRelated()->getTable();

        if (! Schema::hasColumn($sessionsTable, 'subscription_cycle_id')
            || ! Schema::hasColumn($sessionsTable, 'duration_minutes')) {
            return $violations;
        }

        $futureSessions = $sessionsRel
            ->getQuery()
            ->whereIn('status', [
                SessionStatus::SCHEDULED->value,
                SessionStatus::READY->value,
                SessionStatus::UNSCHEDULED->value,
            ])
            ->where('scheduled_at', '>', now())
            ->whereNotNull('subscription_cycle_id')
            ->limit(200)
            ->get(['id', 'subscription_cycle_id', 'duration_minutes']);

        if ($futureSessions->isEmpty()) {
            return $violations;
        }

        $cyclesById = SubscriptionCycle::query()
            ->whereIn('id', $futureSessions->pluck('subscription_cycle_id')->unique()->all())
            ->get()
            ->keyBy('id');

        foreach ($futureSessions as $session) {
            $cycle = $cyclesById->get($session->subscription_cycle_id);
            if (! $cycle instanceof SubscriptionCycle) {
                continue;
            }

            $expectedDuration = $this->resolvePackageSessionDuration($sub, $cycle);
            if ($expectedDuration === null) {
                continue;
            }

            if ((int) $session->duration_minutes !== (int) $expectedDuration) {
                $violations[] = $this->violation(
                    'INV-E1',
                    'Future scheduled session duration_minutes does not match anchor cycle package.session_duration_minutes.',
                    [
                        'subscription_id' => $sub->getKey(),
                        'cycle_id' => (int) $cycle->getKey(),
                        'session_id' => (int) $session->id,
                        'session_duration_minutes' => (int) $session->duration_minutes,
                        'expected_duration_minutes' => (int) $expectedDuration,
                        'remediation' => 'Queue RebuildFutureSessionsForCycle for this cycle.',
                    ],
                );
            }
        }

        return $violations;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Group F — Pause / grace / extend (P6)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function checkGroupF(BaseSubscription $sub): array
    {
        $violations = [];

        // INV-F6: expired subs MUST NOT have status == PAUSED. Easy data check.
        if ($sub->status === SessionSubscriptionStatus::PAUSED
            && $sub->ends_at !== null
            && $sub->ends_at->isPast()
            && ! $sub->isInGracePeriod()) {
            $violations[] = $this->violation(
                'INV-F6',
                'Subscription is past ends_at (no grace) but status is PAUSED — should be EXPIRED.',
                [
                    'subscription_id' => $sub->getKey(),
                    'status' => $this->statusValue($sub),
                    'ends_at' => $this->scalarize($sub->ends_at),
                ],
            );
        }

        // INV-F5: no future scheduled sessions within a paused window.
        if ($sub->status === SessionSubscriptionStatus::PAUSED
            && ! $sub instanceof CourseSubscription
            && method_exists($sub, 'sessions')) {
            $pausedAt = $sub->paused_at ?? null;
            if ($pausedAt instanceof CarbonInterface) {
                $futureCount = $sub->sessions()
                    ->getQuery()
                    ->whereIn('status', [
                        SessionStatus::SCHEDULED->value,
                        SessionStatus::READY->value,
                    ])
                    ->where('scheduled_at', '>=', $pausedAt)
                    ->count();
                if ($futureCount > 0) {
                    $violations[] = $this->violation(
                        'INV-F5',
                        'Subscription is PAUSED but future scheduled/ready sessions exist inside the paused window.',
                        [
                            'subscription_id' => $sub->getKey(),
                            'paused_at' => $pausedAt->toIso8601String(),
                            'future_session_count' => $futureCount,
                        ],
                    );
                }
            }
        }

        // INV-F1, F2, F3, F4 require comparing audit-log before/after states.
        // We surface a best-effort check on F2 only (extend never mutates ends_at)
        // because it's the cheapest signal. The others are covered by Phase B
        // property tests where audit-log history is reliably present.
        if (Schema::hasTable('subscription_audit_log')) {
            try {
                $extendEntries = SubscriptionAuditLog::query()
                    ->forSubscription($sub)
                    ->where('action', 'extend')
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get(['before_state', 'after_state', 'created_at']);

                foreach ($extendEntries as $entry) {
                    $beforeEnds = $entry->before_state['ends_at'] ?? null;
                    $afterEnds = $entry->after_state['ends_at'] ?? null;
                    if ($beforeEnds !== null && $afterEnds !== null && $beforeEnds !== $afterEnds) {
                        $violations[] = $this->violation(
                            'INV-F2',
                            'extend() audit-log entry shows ends_at was mutated (extend should ONLY write grace_period_ends_at).',
                            [
                                'subscription_id' => $sub->getKey(),
                                'created_at' => $entry->created_at?->toIso8601String(),
                                'ends_at_before' => $beforeEnds,
                                'ends_at_after' => $afterEnds,
                            ],
                        );
                    }
                }
            } catch (Throwable) {
                // Silent — audit-log read failure shouldn't surface as F2.
            }
        }

        return $violations;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Group G — Cancel & re-entry (P3, P4, P8)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function checkGroupG(BaseSubscription $sub): array
    {
        $violations = [];

        // INV-G1: routes-level — checker can only surface a soft signal if the
        // audit log shows a cancel action whose actor_user_id is the student
        // themselves (i.e. the actor matches the subscription's student_id).
        // This is best-effort because not every actor is captured.
        if (Schema::hasTable('subscription_audit_log')) {
            try {
                $studentCancels = SubscriptionAuditLog::query()
                    ->forSubscription($sub)
                    ->where('action', 'cancel')
                    ->where('actor_user_id', $sub->student_id)
                    ->limit(5)
                    ->get(['actor_user_id', 'created_at', 'source']);
                foreach ($studentCancels as $row) {
                    $violations[] = $this->violation(
                        'INV-G1',
                        'Cancel action attributed to the student themselves — student-initiated cancellation is forbidden.',
                        [
                            'subscription_id' => $sub->getKey(),
                            'actor_user_id' => $row->actor_user_id,
                            'source' => $row->source,
                            'created_at' => $row->created_at?->toIso8601String(),
                        ],
                    );
                }
            } catch (Throwable) {
                // No-op.
            }
        }

        // INV-G2: cancelled subs must have cancelled_at + cancellation_reason.
        // (cancelled_by_user_id is not a column on the current schema; the
        // audit-log carries actor. Spec mentions it for the future-shaped
        // sub-row schema, so we only check what currently exists.)
        if ($this->isCancelledState($sub)) {
            if ($sub->cancelled_at === null) {
                $violations[] = $this->violation(
                    'INV-G2',
                    'Cancelled subscription has NULL cancelled_at.',
                    [
                        'subscription_id' => $sub->getKey(),
                    ],
                );
            }
            if (! is_string($sub->cancellation_reason) || $sub->cancellation_reason === '') {
                $violations[] = $this->violation(
                    'INV-G2',
                    'Cancelled subscription has empty cancellation_reason.',
                    [
                        'subscription_id' => $sub->getKey(),
                    ],
                    severity: 'warning',
                );
            }
        }

        // INV-G3: resubscribe after expire — audit-log integrity. Best-effort:
        // if status=ACTIVE but the previous audit entry on this row was an
        // 'expire' action without an intervening 'resubscribe' / 'renew', flag.
        // Skipped here because correctly detecting it needs the full audit
        // history; Phase C/D dashboard already surfaces this.

        // INV-G4: a hybrid cycle that expired unpaid should be FAILED+ARCHIVED+
        // sub.status=EXPIRED.
        $currentCycle = $this->loadCurrentCycle($sub);
        if ($currentCycle instanceof SubscriptionCycle
            && $currentCycle->payment_status === SubscriptionCycle::PAYMENT_PENDING
            && $currentCycle->ends_at !== null
            && $currentCycle->ends_at->isPast()
            && $currentCycle->cycle_state !== SubscriptionCycle::STATE_ARCHIVED
            && ! $this->isExpiredState($sub)) {
            $violations[] = $this->violation(
                'INV-G4',
                'Hybrid cycle past ends_at with payment_status=pending was not transitioned to FAILED+ARCHIVED+EXPIRED.',
                [
                    'subscription_id' => $sub->getKey(),
                    'cycle_id' => $currentCycle->getKey(),
                    'cycle_state' => $currentCycle->cycle_state,
                    'cycle_payment_status' => $currentCycle->payment_status,
                    'cycle_ends_at' => $this->scalarize($currentCycle->ends_at),
                    'sub_status' => $this->statusValue($sub),
                ],
            );
        }

        return $violations;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Group H — Retired packages (P7)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * INV-H1 + INV-H2 are UI gates (renewal screen must hide retired packages
     * for students; admin/supervisor flows may still use the snapshot). Neither
     * is observable from subscription state, so this group is documented as
     * out-of-scope for the model checker.
     *
     * @return array<int, array<string, mixed>>
     */
    private function checkGroupH(BaseSubscription $sub): array
    {
        unset($sub); // intentionally unused — group is UI-only

        return [];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Group J — UX surface (R1 → A.7)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * INV-J1 + INV-J2 are surface contracts (every display renders one badge,
     * one primary action, one helper line; API formatter returns view_state +
     * primary_action). These are view-layer rules, not subscription state, so
     * they're enforced via UI tests + API formatter tests in Phase B — not
     * here.
     *
     * @return array<int, array<string, mixed>>
     */
    private function checkGroupJ(BaseSubscription $sub): array
    {
        unset($sub); // intentionally unused — group is view-only

        return [];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function violation(
        string $code,
        string $message,
        array $context = [],
        string $severity = 'error',
    ): array {
        // Issue #2: every invariant violation surfaces in the supervisor cycle
        // inspector. The hardcoded English `$message` becomes the fallback,
        // but a localized lookup is attempted first under the conventional
        // `supervisor.subscriptions.invariants.<CODE>` key. Translation
        // placeholders mirror the violation context.
        $key = 'supervisor.subscriptions.invariants.'.$code;
        $localized = __($key, $this->translatableContext($context));
        if (is_string($localized) && $localized !== $key) {
            $message = $localized;
        }

        return [
            'code' => $code,
            'severity' => $severity,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * Pull the scalar context entries safe for use as :placeholder
     * substitutions. Carbon, enums and nested arrays are excluded — the
     * translation layer can't render them and they are always available in
     * the structured `context` payload anyway.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, scalar>
     */
    private function translatableContext(array $context): array
    {
        $out = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function loadCurrentCycle(BaseSubscription $sub): ?SubscriptionCycle
    {
        if (! property_exists($sub, 'current_cycle_id') && ! isset($sub->current_cycle_id)) {
            return null;
        }
        if ($sub->current_cycle_id === null) {
            return null;
        }
        if ($sub->relationLoaded('currentCycle')) {
            $cycle = $sub->currentCycle;
        } else {
            $cycle = $sub->currentCycle()->first();
        }

        return $cycle instanceof SubscriptionCycle ? $cycle : null;
    }

    /**
     * Polymorphic-safe cycles query for this subscription.
     */
    private function cyclesQuery(BaseSubscription $sub): \Illuminate\Database\Eloquent\Builder
    {
        return SubscriptionCycle::query()
            ->where('subscribable_type', $sub->getMorphClass())
            ->where('subscribable_id', $sub->getKey());
    }

    private function mapCyclePaymentStatusToEnum(?string $cyclePaymentStatus): SubscriptionPaymentStatus
    {
        return match ($cyclePaymentStatus) {
            SubscriptionCycle::PAYMENT_PAID => SubscriptionPaymentStatus::PAID,
            SubscriptionCycle::PAYMENT_FAILED => SubscriptionPaymentStatus::FAILED,
            default => SubscriptionPaymentStatus::PENDING,
        };
    }

    /**
     * Equality for mirrored fields, tolerating Carbon vs string vs int.
     */
    private function mirrorEquals(mixed $subValue, mixed $cycleValue): bool
    {
        if ($subValue instanceof CarbonInterface || $cycleValue instanceof CarbonInterface) {
            $a = $subValue instanceof CarbonInterface ? $subValue->getTimestamp() : null;
            $b = $cycleValue instanceof CarbonInterface ? $cycleValue->getTimestamp() : null;

            return $a !== null && $b !== null && $a === $b;
        }

        if (is_numeric($subValue) && is_numeric($cycleValue)) {
            return (int) $subValue === (int) $cycleValue;
        }

        return $subValue == $cycleValue; // intentional loose compare for null-vs-0 edge cases
    }

    private function datesEqual(CarbonInterface $a, CarbonInterface $b): bool
    {
        return $a->getTimestamp() === $b->getTimestamp();
    }

    private function priceEquals(float $a, float $b): bool
    {
        return abs($a - $b) < 0.01;
    }

    /**
     * Render a value into something JSON-safe for the violation context.
     */
    private function scalarize(mixed $value): mixed
    {
        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    private function statusValue(BaseSubscription $sub): mixed
    {
        $status = $sub->status;
        if ($status instanceof \BackedEnum) {
            return $status->value;
        }

        return $status;
    }

    /**
     * Mirrors {@see SubscriptionReconciler::isLegacyConsumptionCycle}: a cycle
     * is "legacy" if it predates the v2 flip cutoff AND has not yet had its
     * legacy attendance backfilled into session_consumption. We use this here
     * to soften INV-B3 + INV-D4 from `error` to `warning` so the supervisor
     * inspector doesn't drown in expected-shape noise on pre-flip cycles.
     */
    private function isLegacyConsumptionCycle(SubscriptionCycle $cycle): bool
    {
        if ($cycle->v2_consumption_complete) {
            return false;
        }

        $cutoffRaw = config('subscriptions.v2_flip_cutoff');
        if (! is_string($cutoffRaw) || $cutoffRaw === '') {
            return false;
        }

        try {
            $cutoff = Carbon::parse($cutoffRaw);
        } catch (Throwable) {
            return false;
        }

        $createdAt = $cycle->created_at;
        if (! $createdAt instanceof CarbonInterface) {
            return false;
        }

        return $createdAt->lt($cutoff);
    }

    private function isInPendingState(BaseSubscription $sub): bool
    {
        $status = $sub->status;
        // Enum-typed comparison covers all three concrete subclasses:
        // QuranSubscription + AcademicSubscription use SessionSubscriptionStatus,
        // CourseSubscription uses EnrollmentStatus. Both expose case 'pending'.
        if ($status instanceof SessionSubscriptionStatus) {
            return $status === SessionSubscriptionStatus::PENDING;
        }
        if ($status instanceof \BackedEnum) {
            return $status->value === 'pending';
        }

        return is_string($status) && $status === 'pending';
    }

    private function isCancelledState(BaseSubscription $sub): bool
    {
        $status = $sub->status;
        if ($status instanceof SessionSubscriptionStatus) {
            return $status === SessionSubscriptionStatus::CANCELLED;
        }
        if ($status instanceof \BackedEnum) {
            return $status->value === 'cancelled';
        }

        return false;
    }

    private function isExpiredState(BaseSubscription $sub): bool
    {
        $status = $sub->status;
        if ($status instanceof SessionSubscriptionStatus) {
            return $status === SessionSubscriptionStatus::EXPIRED;
        }
        if ($status instanceof \BackedEnum) {
            return $status->value === 'expired';
        }

        return false;
    }

    /**
     * Resolve the expected package-derived price for a cycle, or null if it
     * cannot be determined safely (no package linkage, no billing cycle).
     */
    private function resolveExpectedPackagePrice(
        SubscriptionCycle $cycle,
        mixed $fallbackPackage,
    ): ?float {
        $billingCycleValue = $cycle->billing_cycle;
        $billingCycle = $billingCycleValue instanceof BillingCycle
            ? $billingCycleValue
            : BillingCycle::tryFrom((string) $billingCycleValue);
        if ($billingCycle === null) {
            return null;
        }

        // Prefer the cycle's package_snapshot (the recorded source). Fall back
        // to the live package model only when the snapshot is missing.
        $snapshot = $cycle->package_snapshot;
        if (is_array($snapshot) && ! empty($snapshot)) {
            $basePrice = PricingResolver::resolvePriceFromPackage($snapshot, $billingCycle);
        } elseif (is_object($fallbackPackage)) {
            $basePrice = PricingResolver::resolvePriceFromPackage($fallbackPackage, $billingCycle);
        } else {
            return null;
        }

        $discount = (float) ($cycle->discount_amount ?? 0);

        return max(0.0, (float) $basePrice - $discount);
    }

    /**
     * Resolve the package's session_duration_minutes for INV-E1. Prefers the
     * cycle's package_snapshot (so we evaluate against the snapshot at cycle
     * creation, not whatever the live package says today). Falls back to the
     * live package via the subscription.
     */
    private function resolvePackageSessionDuration(
        BaseSubscription $sub,
        SubscriptionCycle $cycle,
    ): ?int {
        $snapshot = $cycle->package_snapshot;
        if (is_array($snapshot) && isset($snapshot['session_duration_minutes'])) {
            return (int) $snapshot['session_duration_minutes'];
        }

        if (method_exists($sub, 'package')) {
            $pkg = $sub->package;
            if (is_object($pkg) && isset($pkg->session_duration_minutes)) {
                return (int) $pkg->session_duration_minutes;
            }
        }

        // Subscription row also snapshots session_duration_minutes (per
        // BaseSubscription::$baseFillable). Use it as a last-resort signal.
        $rowDuration = $sub->getAttribute('session_duration_minutes');
        if (is_numeric($rowDuration)) {
            return (int) $rowDuration;
        }

        return null;
    }
}
