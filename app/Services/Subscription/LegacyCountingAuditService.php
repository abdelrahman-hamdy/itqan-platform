<?php

namespace App\Services\Subscription;

use App\Models\AcademicSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Models\TeacherEarning;
use Illuminate\Support\Facades\DB;

/**
 * Read-only audit service for the legacy counting columns that Phase 4
 * intends to drop:
 *
 *   - subscription_cycles.v2_consumption_complete
 *   - quran_sessions.subscription_counted / subscription_counted_at
 *   - academic_sessions.subscription_counted / subscription_counted_at
 *   - meeting_attendances.subscription_counted_at
 *
 * The audit page (/manage/legacy-counting-audit) surfaces:
 *   - Drift cases: legacy flag says counted but the new session_consumption
 *     row doesn't exist (or vice versa). Each row needs admin judgment
 *     before the columns can be dropped.
 *   - Migration progress: cycle-level v2_consumption_complete coverage.
 *   - Risk cases that block any auto-cleanup (earnings already paid out,
 *     etc.).
 *
 * Returns plain arrays consumed by the blade view. No mutations.
 */
class LegacyCountingAuditService
{
    /**
     * @return array{
     *   summary: array<string, int>,
     *   drift_legacy_not_consumption: list<array<string, mixed>>,
     *   drift_consumption_not_legacy: list<array<string, mixed>>,
     *   attendance_drift: list<array<string, mixed>>,
     *   cycles_pending_v2_migration: list<array<string, mixed>>,
     *   stuck_with_earnings: list<array<string, mixed>>,
     * }
     */
    public function buildReport(): array
    {
        return [
            'summary' => $this->buildSummary(),
            'drift_legacy_not_consumption' => $this->legacyCountedWithoutConsumption(),
            'drift_consumption_not_legacy' => $this->consumptionWithoutLegacyFlag(),
            'attendance_drift' => $this->attendanceCountedWithoutConsumption(),
            'cycles_pending_v2_migration' => $this->cyclesPendingV2Migration(),
            'stuck_with_earnings' => $this->stuckSessionsWithEarnings(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function buildSummary(): array
    {
        return [
            'cycles_total' => SubscriptionCycle::withoutGlobalScopes()->count(),
            'cycles_v2_complete' => SubscriptionCycle::withoutGlobalScopes()
                ->where('v2_consumption_complete', true)
                ->count(),
            'cycles_v2_pending' => SubscriptionCycle::withoutGlobalScopes()
                ->where(function ($q) {
                    $q->where('v2_consumption_complete', false)->orWhereNull('v2_consumption_complete');
                })
                ->count(),
            'quran_sessions_legacy_counted' => QuranSession::query()
                ->withoutGlobalScopes()
                ->where('subscription_counted', true)
                ->count(),
            'academic_sessions_legacy_counted' => AcademicSession::query()
                ->withoutGlobalScopes()
                ->where('subscription_counted', true)
                ->count(),
            'meeting_attendances_legacy_counted' => MeetingAttendance::query()
                ->whereNotNull('subscription_counted_at')
                ->count(),
            'session_consumption_active' => SessionConsumption::query()
                ->whereNull('reversed_at')
                ->count(),
        ];
    }

    /**
     * Sessions where the legacy flag says counted but no session_consumption
     * row backs it up. Highest-risk drift: dropping the column erases the
     * only record that this session was counted.
     *
     * @return list<array<string, mixed>>
     */
    private function legacyCountedWithoutConsumption(): array
    {
        $rows = [];

        foreach (['quran' => QuranSession::class, 'academic' => AcademicSession::class] as $tag => $modelClass) {
            $morph = (new $modelClass)->getMorphClass();
            $records = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('subscription_counted', true)
                ->whereNotIn('id', function ($sub) use ($morph) {
                    $sub->select('session_id')
                        ->from('session_consumption')
                        ->where('session_type', $morph)
                        ->whereNull('reversed_at');
                })
                ->orderBy('id')
                ->limit(200)
                ->get(['id', 'status', 'scheduled_at', 'subscription_cycle_id', 'academy_id']);

            foreach ($records as $r) {
                $rows[] = [
                    'kind' => $tag,
                    'session_id' => $r->id,
                    'status' => $r->status instanceof \BackedEnum ? $r->status->value : $r->status,
                    'scheduled_at' => $r->scheduled_at?->toDateTimeString(),
                    'subscription_counted_at' => null,
                    'cycle_id' => $r->subscription_cycle_id,
                    'academy_id' => $r->academy_id,
                    'risk' => 'high',
                    'risk_reason' => 'flag says counted but no consumption row — dropping column erases the only proof',
                    'recommended' => 'Create a backfill consumption row before drop; OR admin confirms session was not actually counted (flag was wrong)',
                ];
            }
        }

        return $rows;
    }

    /**
     * The inverse: a session_consumption row exists but the legacy flag is
     * still false/null. Low-risk drift (the new path is canonical) but
     * indicates the legacy writer was skipped — useful signal for the
     * code-drop sequencing.
     *
     * @return list<array<string, mixed>>
     */
    private function consumptionWithoutLegacyFlag(): array
    {
        $rows = SessionConsumption::query()
            ->whereNull('reversed_at')
            ->whereIn('session_type', ['quran_session', 'academic_session'])
            ->orderBy('id')
            ->limit(200)
            ->get(['session_id', 'session_type', 'subscription_id', 'cycle_id', 'consumed_at']);

        $result = [];
        foreach ($rows as $row) {
            $sessionModel = $row->session_type === 'quran_session' ? QuranSession::class : AcademicSession::class;
            $session = $sessionModel::query()
                ->withoutGlobalScopes()
                ->whereKey($row->session_id)
                ->first(['id', 'subscription_counted', 'status', 'scheduled_at', 'academy_id']);

            if ($session === null || $session->subscription_counted === true) {
                continue;
            }

            $result[] = [
                'kind' => str_replace('_session', '', $row->session_type),
                'session_id' => $row->session_id,
                'status' => $session->status instanceof \BackedEnum ? $session->status->value : $session->status,
                'scheduled_at' => $session->scheduled_at?->toDateTimeString(),
                'cycle_id' => $row->cycle_id,
                'academy_id' => $session->academy_id,
                'consumed_at' => $row->consumed_at?->toDateTimeString(),
                'risk' => 'low',
                'risk_reason' => 'consumption row is canonical; legacy flag is stale (likely a writer that skipped the dual-write)',
                'recommended' => 'Safe to drop column; consumption row is authoritative',
            ];
        }

        return $result;
    }

    /**
     * meeting_attendances rows with subscription_counted_at set but no
     * matching session_consumption row.
     *
     * @return list<array<string, mixed>>
     */
    private function attendanceCountedWithoutConsumption(): array
    {
        $rows = MeetingAttendance::query()
            ->whereNotNull('subscription_counted_at')
            ->whereNotIn(DB::raw('CONCAT(session_id,":",session_type)'), function ($sub) {
                $sub->select(DB::raw('CONCAT(session_id,":",session_type)'))
                    ->from('session_consumption')
                    ->whereNull('reversed_at');
            })
            ->orderBy('id')
            ->limit(200)
            ->get(['id', 'session_id', 'session_type', 'user_id', 'subscription_counted_at']);

        return $rows->map(fn ($r) => [
            'attendance_id' => $r->id,
            'session_id' => $r->session_id,
            'session_type' => $r->session_type,
            'user_id' => $r->user_id,
            'counted_at' => $r->subscription_counted_at?->toDateTimeString(),
            'risk' => 'medium',
            'risk_reason' => 'attendance counted via legacy path; no consumption row',
            'recommended' => 'Admin: was the session actually attended? If yes, create consumption row. If no, clear the flag.',
        ])->all();
    }

    /**
     * Cycles where v2_consumption_complete is not yet true. These are
     * not-yet-migrated to the canonical counting path. Drop the column only
     * after all cycles are migrated OR after the gate logic stops checking
     * the column.
     *
     * @return list<array<string, mixed>>
     */
    private function cyclesPendingV2Migration(): array
    {
        $rows = SubscriptionCycle::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('v2_consumption_complete', false)->orWhereNull('v2_consumption_complete');
            })
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get(['id', 'subscribable_type', 'subscribable_id', 'cycle_state', 'payment_status', 'sessions_used', 'total_sessions', 'created_at']);

        return $rows->map(fn ($r) => [
            'cycle_id' => $r->id,
            'subscribable' => $r->subscribable_type.'#'.$r->subscribable_id,
            'state' => $r->cycle_state,
            'payment' => $r->payment_status,
            'used_of_total' => $r->sessions_used.'/'.$r->total_sessions,
            'created' => $r->created_at?->toDateTimeString(),
            'risk' => $r->cycle_state === 'active' ? 'medium' : 'low',
            'risk_reason' => $r->cycle_state === 'active'
                ? 'active cycle still using legacy counting path'
                : 'archived/queued cycle never migrated',
            'recommended' => 'Backfill v2_consumption_complete=true after verifying counts match session_consumption rows',
        ])->all();
    }

    /**
     * The 22 stuck-scheduled sessions that have teacher_earnings rows —
     * surfaced from Phase A's safety-gate skip. Each one needs admin to
     * decide: was the session actually held (status should be COMPLETED)
     * or were the earnings created in error (need reversal)?
     *
     * @return list<array<string, mixed>>
     */
    private function stuckSessionsWithEarnings(): array
    {
        $sessionIds = QuranSession::query()
            ->withoutGlobalScopes()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<', now()->subMinutes(5))
            ->pluck('id');

        if ($sessionIds->isEmpty()) {
            return [];
        }

        $earnings = TeacherEarning::query()
            ->withoutGlobalScopes()
            ->whereIn('session_id', $sessionIds)
            ->where('session_type', 'quran_session')
            ->whereNull('deleted_at')
            ->get(['id', 'session_id', 'session_type', 'teacher_id', 'amount', 'created_at']);

        $sessionsById = QuranSession::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $earnings->pluck('session_id'))
            ->get(['id', 'scheduled_at', 'quran_subscription_id', 'academy_id', 'quran_teacher_id'])
            ->keyBy('id');

        return $earnings->map(function ($e) use ($sessionsById) {
            $s = $sessionsById->get($e->session_id);

            return [
                'earning_id' => $e->id,
                'session_id' => $e->session_id,
                'session_type' => $e->session_type,
                'scheduled_at' => $s?->scheduled_at?->toDateTimeString(),
                'subscription_id' => $s?->quran_subscription_id,
                'academy_id' => $s?->academy_id,
                'teacher_id' => $e->teacher_id,
                'amount' => $e->amount,
                'currency' => 'SAR',
                'earning_created' => $e->created_at?->toDateTimeString(),
                'risk' => 'high',
                'risk_reason' => 'session stuck in scheduled status BUT teacher earning was already created — possible payout',
                'recommended' => 'Admin: confirm whether the session actually happened. If yes → flip session to COMPLETED + create consumption row. If no → soft-delete the earning + cancel the session.',
            ];
        })->all();
    }
}
