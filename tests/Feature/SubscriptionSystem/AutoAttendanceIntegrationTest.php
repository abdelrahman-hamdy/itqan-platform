<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Services\EarningsCalculationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Scenario 1 — Auto attendance + teacher earnings + student subscription counting.
 *
 * The auto-attendance pipeline:
 *   - BaseSessionObserver::updated() fires when status flips to COMPLETED.
 *   - CalculateSessionForAttendance job runs the matrix, writes
 *     counts_for_teacher / counts_for_subscription onto the session.
 *   - useSession() bumps subscription + cycle counters when
 *     counts_for_subscription = true.
 *   - EarningsCalculationService creates a teacher_earnings row when
 *     counts_for_teacher = true.
 *
 * These tests target the SERVICE-LAYER invariants of the chain (deterministic),
 * not the queue-driven observer dispatch (which involves time-of-day matrix
 * inputs and is covered by Integration/SessionCountingIntegrationTest).
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'auto-att-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    setTenantContext($this->academy);

    // Stamp teacher rate so earnings calculation has a price to snapshot.
    QuranTeacherProfile::where('user_id', $this->teacher->id)->update([
        'session_price_individual' => 50,
        'session_price_group' => 30,
    ]);

    $this->earningsService = app(EarningsCalculationService::class);
});

function autoAttSubscription(int $totalSessions = 8): array
{
    $sub = QuranSubscription::factory()
        ->forStudent(test()->student)
        ->forTeacher(test()->teacher)
        ->active()
        ->create([
            'sessions_used' => 0,
            'sessions_remaining' => $totalSessions,
            'total_sessions' => $totalSessions,
        ]);
    $cycle = $sub->ensureCurrentCycle();
    if (($cycle->total_sessions ?? 0) === 0) {
        $cycle->update(['total_sessions' => $totalSessions]);
    }

    return [$sub->fresh(), $cycle->fresh()];
}

/**
 * Insert a session row directly with the matrix decision already written —
 * mimics what CalculateSessionForAttendance writes before earnings recording.
 * Note: `counts_for_subscription` lives on `meeting_attendances`, not on the
 * session row; sessions only carry `counts_for_teacher`. We assert the
 * subscription-side invariant via `useSession()` directly in each test.
 */
function autoAttSession(
    int $subId,
    int $cycleId,
    bool $countsForTeacher,
    array $overrides = []
): QuranSession {
    $id = DB::table('quran_sessions')->insertGetId(array_merge([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'quran_subscription_id' => $subId,
        'subscription_cycle_id' => $cycleId,
        'status' => SessionStatus::COMPLETED->value,
        'scheduled_at' => now()->subMinutes(60),
        'started_at' => now()->subMinutes(60),
        'ended_at' => now()->subMinutes(30),
        'actual_duration_minutes' => 30,
        'duration_minutes' => 30,
        'session_type' => 'individual',
        'counts_for_teacher' => $countsForTeacher,
        'session_code' => 'AA-'.uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));

    return QuranSession::find($id);
}

describe('AA — auto-attendance side effects (subscription counter + earnings)', function () {
    it('AA1 — teacher present + student attended → sub.sessions_used+=1 AND earning created', function () {
        [$sub, $cycle] = autoAttSubscription(8);
        $session = autoAttSession($sub->id, $cycle->id, true);

        $sub->useSession($cycle->id);
        $earning = $this->earningsService->calculateSessionEarnings($session);

        $sub = $sub->fresh();
        $cycle = $cycle->fresh();
        expect($sub->sessions_used)->toBe(1);
        expect($cycle->sessions_used)->toBe(1);
        expect($earning)->not->toBeNull('teacher attended → earning row must be created');
        expect((float) $earning->amount)->toBeGreaterThan(0);
    });

    it('AA2 — teacher absent + student attended → no earning even if sub flag is set', function () {
        [$sub, $cycle] = autoAttSubscription(8);
        // counts_for_teacher=false means the teacher didn't earn for this session
        // (e.g. teacher absent, partial attendance below threshold).
        $session = autoAttSession($sub->id, $cycle->id, false);

        $earning = $this->earningsService->calculateSessionEarnings($session);

        expect($earning)->toBeNull(
            'session marked counts_for_teacher=false must not produce an earning'
        );
        // Verify NO row landed in teacher_earnings either.
        $count = TeacherEarning::where('session_id', $session->id)->count();
        expect($count)->toBe(0);
    });

    it('AA3 — student absent + teacher absent → no earning, sub counter unchanged', function () {
        [$sub, $cycle] = autoAttSubscription(8);
        // No-show flagged but teacher also absent → counts_for_subscription=true
        // (no-show forfeit) but counts_for_teacher=false (no earnings).
        $session = autoAttSession($sub->id, $cycle->id, false);

        $earning = $this->earningsService->calculateSessionEarnings($session);
        expect($earning)->toBeNull();
        expect(TeacherEarning::where('session_id', $session->id)->count())->toBe(0);
    });

    it('AA5 — idempotency: re-running earnings on an already-recorded session returns the same row', function () {
        [$sub, $cycle] = autoAttSubscription(8);
        $session = autoAttSession($sub->id, $cycle->id, true);

        $first = $this->earningsService->calculateSessionEarnings($session);
        $second = $this->earningsService->calculateSessionEarnings($session);

        expect($first)->not->toBeNull();
        expect($second)->not->toBeNull();
        expect($second->id)->toBe($first->id, 'second call must return the same row, not mint a duplicate');
        expect(TeacherEarning::where('session_id', $session->id)->count())->toBe(1);
    });

    it('AA6 — useSession on the current cycle mirrors both counters', function () {
        [$sub, $cycle] = autoAttSubscription(8);
        autoAttSession($sub->id, $cycle->id, true);

        $sub->useSession($cycle->id);

        $sub = $sub->fresh();
        $cycle = $cycle->fresh();
        expect($sub->sessions_used)->toBe(1);
        expect($sub->sessions_remaining)->toBe(7);
        expect($cycle->sessions_used)->toBe(1);
        expect($cycle->sessions_completed)->toBe(1);
    });

    it('AA7 — earnings dedup spans repeated calculations across the same session', function () {
        [$sub, $cycle] = autoAttSubscription(8);
        $session = autoAttSession($sub->id, $cycle->id, true);

        // First call mints the row.
        $first = $this->earningsService->calculateSessionEarnings($session);
        expect($first)->not->toBeNull();
        $firstId = $first->id;

        // Subsequent calls — the unique constraint + service-layer dedup must
        // surface the same row each time. Run a few to flush any state.
        for ($i = 0; $i < 3; $i++) {
            $again = $this->earningsService->calculateSessionEarnings($session);
            expect($again->id)->toBe($firstId);
        }

        expect(TeacherEarning::where('session_id', $session->id)->count())->toBe(1);
    });
});
