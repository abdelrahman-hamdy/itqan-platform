<?php

namespace App\Services\Subscription;

use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use App\Support\Subscriptions\SubscriptionSnapshot;

/**
 * Read-only manifest assembler for the post-v2-flip subscription audit.
 *
 * Returns the full picture of one subscription as a single nested array:
 *   - row-level sub state
 *   - all cycles + their pricing snapshot
 *   - all sessions that reference the sub, with their evidence sources
 *     (attendance, reports, recordings, legacy counted flag, status)
 *   - all payments
 *   - all session_consumption rows (active + reversed)
 *   - all subscription_audit_log entries
 *   - derived: canonical view state, drift indicators per cycle
 *
 * STRICTLY READ-ONLY. Used by:
 *   - php artisan subscriptions:audit-one --id=X
 *   - php artisan subscriptions:audit-all-subs
 *   - the per-pattern fix scripts that come out of CHECKPOINT (read-side only;
 *     write-side scripts construct their own SQL).
 */
final class SubscriptionManifestService
{
    public function __construct(
        private readonly SubscriptionPresentation $presentation,
    ) {}

    /**
     * Build the manifest for a subscription. Caller passes the loaded model;
     * we eager-load relations on it but never mutate.
     *
     * @return array{
     *     subscription: array<string, mixed>,
     *     view_state: string,
     *     view_state_label: string,
     *     manifest: array<string, mixed>,
     *     drift: array<int, array<string, mixed>>,
     * }
     */
    public function build(BaseSubscription $sub): array
    {
        $manifest = SubscriptionSnapshot::captureManifest($sub);

        $viewState = $this->presentation->viewStateFor($sub);

        return [
            'subscription_id' => (int) $sub->getKey(),
            'subscription_type' => $sub->getMorphClass(),
            'view_state' => $viewState->value,
            'view_state_label' => $viewState->label(),
            'manifest' => $manifest,
            'drift' => $this->computeDrift($manifest),
        ];
    }

    /**
     * Look up a subscription by its (id, morph) pair. Returns null if not
     * found; the audit command surfaces a clean error message in that case.
     */
    public function findByIdAndMorph(int $id, string $morph): ?BaseSubscription
    {
        $class = match ($morph) {
            'quran_subscription', QuranSubscription::class => QuranSubscription::class,
            'academic_subscription', AcademicSubscription::class => AcademicSubscription::class,
            'course_subscription', CourseSubscription::class => CourseSubscription::class,
            default => null,
        };

        if ($class === null) {
            return null;
        }

        return $class::query()->find($id);
    }

    /**
     * Locate a subscription when only the id is known. Tries each morph in
     * turn and returns the first match. Slower than findByIdAndMorph but
     * convenient for one-off CLI invocations.
     */
    public function findById(int $id): ?BaseSubscription
    {
        foreach ([QuranSubscription::class, AcademicSubscription::class, CourseSubscription::class] as $class) {
            $sub = $class::query()->find($id);
            if ($sub !== null) {
                return $sub;
            }
        }

        return null;
    }

    /**
     * Per-cycle drift indicators: differences between the cycle's stored
     * sessions_used vs. the count of non-reversed session_consumption rows
     * that target it. A drifting cycle is the canonical signal that the
     * legacy mutator path wrote to the cycle aggregate without writing a
     * matching consumption row (or vice versa).
     *
     * @param  array<string,mixed>  $manifest
     * @return array<int, array<string,mixed>>
     */
    private function computeDrift(array $manifest): array
    {
        $cycleById = [];
        foreach ($manifest['cycles'] ?? [] as $cycle) {
            $cycleById[(int) $cycle['id']] = $cycle;
        }

        $consumptionsPerCycle = [];
        foreach ($manifest['consumptions'] ?? [] as $row) {
            if (! empty($row['reversed_at'])) {
                continue;
            }
            $cid = (int) $row['cycle_id'];
            $consumptionsPerCycle[$cid] = ($consumptionsPerCycle[$cid] ?? 0) + 1;
        }

        $sessionsPerCycle = [];
        $legacyCountedPerCycle = [];
        foreach ($manifest['sessions'] ?? [] as $session) {
            $cid = (int) ($session['cycle_id'] ?? 0);
            if ($cid === 0) {
                continue;
            }
            $sessionsPerCycle[$cid] = ($sessionsPerCycle[$cid] ?? 0) + 1;
            if (! empty($session['legacy_subscription_counted'])) {
                $legacyCountedPerCycle[$cid] = ($legacyCountedPerCycle[$cid] ?? 0) + 1;
            }
        }

        $drift = [];
        foreach ($cycleById as $cid => $cycle) {
            $cycleSessionsUsed = (int) ($cycle['sessions_used'] ?? 0);
            $consumptionCount = $consumptionsPerCycle[$cid] ?? 0;
            $sessionCount = $sessionsPerCycle[$cid] ?? 0;
            $legacyCount = $legacyCountedPerCycle[$cid] ?? 0;

            $drift[] = [
                'cycle_id' => $cid,
                'cycle_number' => $cycle['cycle_number'],
                'cycle_state' => $cycle['cycle_state'],
                'v2_consumption_complete' => $cycle['v2_consumption_complete'] ?? false,
                'cycle_sessions_used' => $cycleSessionsUsed,
                'consumption_rows' => $consumptionCount,
                'sessions_on_cycle' => $sessionCount,
                'legacy_counted_sessions' => $legacyCount,
                'aggregate_minus_consumption_diff' => $cycleSessionsUsed - $consumptionCount,
                'is_drifting' => $cycleSessionsUsed !== $consumptionCount,
                'kind' => $this->classifyCycleDrift(
                    $cycleSessionsUsed,
                    $consumptionCount,
                    $legacyCount,
                    (bool) ($cycle['v2_consumption_complete'] ?? false),
                ),
            ];
        }

        return $drift;
    }

    /**
     * Coarse classification of cycle drift kinds. Phase 2/2b fix scripts
     * dispatch off this label.
     */
    private function classifyCycleDrift(
        int $cycleUsed,
        int $consumptions,
        int $legacyCounted,
        bool $v2ConsumptionComplete,
    ): string {
        if ($cycleUsed === $consumptions) {
            return 'IN_SYNC';
        }
        if ($consumptions === 0 && $cycleUsed > 0 && $legacyCounted >= $cycleUsed) {
            return 'LEGACY_AGGREGATE_NO_ROWS';
        }
        if ($consumptions === 0 && $cycleUsed > 0) {
            return 'AGGREGATE_NO_EVIDENCE';
        }
        if ($consumptions > 0 && $cycleUsed === 0) {
            return 'ROWS_NO_AGGREGATE';
        }
        if ($consumptions > $cycleUsed) {
            return 'ROWS_GREATER_THAN_AGGREGATE';
        }

        return $v2ConsumptionComplete ? 'V2_COMPLETE_BUT_DRIFTING' : 'MIXED_DRIFT';
    }
}
