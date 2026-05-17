<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionConsumption;

/**
 * Counter-consistency invariants for the subscription↔session counting layer.
 * Post-Phase-4: the canonical record is `session_consumption` (INV-B1+B3), so
 * the invariant collapses to:
 *
 *   cycle.sessions_used == COUNT(session_consumption WHERE cycle_id=?
 *                                  AND reversed_at IS NULL)
 *   sub.sessions_used   == cycle.sessions_used  (when cycle is current)
 *
 * The invariant must hold across:
 *  - COMPLETED → CANCELLED (with reverse) (Bug #4 hypothesis)
 *  - COMPLETED → CANCELLED → make-up scheduled → make-up COMPLETED (Bug #4)
 *  - Admin toggle uncount + recount via SessionCountingService (Bug #8)
 *  - Observer re-fire (e.g., status flip COMPLETED → SCHEDULED → COMPLETED)
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'counter-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

/** Create a sub with a materialized current cycle. */
function counterSub(\App\Models\User $student, \App\Models\User $teacher, int $totalSessions = 8): array
{
    $sub = QuranSubscription::factory()
        ->forStudent($student)
        ->forTeacher($teacher)
        ->active()
        ->create([
            'sessions_used' => 0,
            'sessions_remaining' => $totalSessions,
            'total_sessions' => $totalSessions,
            'payment_status' => \App\Enums\SubscriptionPaymentStatus::PAID,
        ]);

    $cycle = $sub->ensureCurrentCycle();
    if (! $cycle->total_sessions) {
        $cycle->update(['total_sessions' => $totalSessions]);
    }

    return [$sub->fresh(), $cycle->fresh()];
}

/** Record canonical consumption for a session via the v2 writer. */
function counterRecord(QuranSession $session, \App\Models\User $student, QuranSubscription $sub): void
{
    app(SubscriptionConsumption::class)->record(
        $session,
        $student,
        $sub,
        source: SessionConsumption::SOURCE_AUTO_ATTENDANCE,
        sourceUser: null,
        consumptionType: SessionConsumption::TYPE_ATTENDED,
    );
}

/** Asserts the cycle-counter invariant for a given subscription. */
function assertCounterInvariant(QuranSubscription $sub): void
{
    $cycle = SubscriptionCycle::find($sub->current_cycle_id);
    expect($cycle)->not->toBeNull();

    $activeConsumption = SessionConsumption::query()
        ->where('cycle_id', $cycle->id)
        ->whereNull('reversed_at')
        ->count();

    expect($cycle->sessions_used)->toBe($activeConsumption, sprintf(
        'cycle.sessions_used=%d but COUNT(active session_consumption)=%d (drift!)',
        $cycle->sessions_used,
        $activeConsumption
    ));
    expect($sub->fresh()->sessions_used)->toBe($activeConsumption, sprintf(
        'sub.sessions_used=%d but COUNT(active session_consumption)=%d (drift!)',
        $sub->fresh()->sessions_used,
        $activeConsumption
    ));
}

describe('Bug #4 — cancel-after-complete drift', function () {
    it('B4-1 — COMPLETED → CANCELLED reverses the cycle counter cleanly', function () {
        [$sub, $cycle] = counterSub($this->student, $this->teacher, 8);

        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $cycle->id,
            'scheduled_at' => now()->subMinutes(30),
            'status' => SessionStatus::SCHEDULED,
        ]);

        // Mark COMPLETED + write the canonical consumption row through the v2 writer.
        $session->update(['status' => SessionStatus::COMPLETED]);
        counterRecord($session->fresh(), $this->student, $sub);
        expect($sub->fresh()->sessions_used)->toBe(1);
        expect($cycle->fresh()->sessions_used)->toBe(1);
        assertCounterInvariant($sub->fresh());

        // Flip to CANCELLED — observer reverses via reverseSubscriptionAndEarnings
        // (which now iterates active SessionConsumption rows).
        $session->fresh()->update(['status' => SessionStatus::CANCELLED]);

        assertCounterInvariant($sub->fresh());
        expect($sub->fresh()->sessions_used)->toBe(0);
        expect($cycle->fresh()->sessions_used)->toBe(0);
    });

    it('B4-2 — COMPLETED → CANCELLED → make-up COMPLETED keeps cycle at 1, never 2', function () {
        [$sub, $cycle] = counterSub($this->student, $this->teacher, 8);

        $original = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $cycle->id,
            'scheduled_at' => now()->subMinutes(30),
            'status' => SessionStatus::SCHEDULED,
        ]);

        $original->update(['status' => SessionStatus::COMPLETED]);
        counterRecord($original->fresh(), $this->student, $sub);
        expect($cycle->fresh()->sessions_used)->toBe(1);

        $original->fresh()->update(['status' => SessionStatus::CANCELLED]);
        expect($cycle->fresh()->sessions_used)->toBe(0);

        $makeup = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $cycle->id,
            'scheduled_at' => now()->subMinutes(20),
            'status' => SessionStatus::SCHEDULED,
        ]);
        $makeup->update(['status' => SessionStatus::COMPLETED]);
        counterRecord($makeup->fresh(), $this->student, $sub);

        assertCounterInvariant($sub->fresh());
        expect($cycle->fresh()->sessions_used)->toBe(1);
        expect($sub->fresh()->sessions_used)->toBe(1);
    });

    it('B4-3 — reverse path handles NULL subscription_cycle_id correctly (legacy sessions)', function () {
        // Simulates Sub 1024's prod state: legacy sessions with NULL cycle_id.
        // The reverse path is now agnostic to cycle_id on the session row
        // because the consumption row carries its own cycle anchor (INV-B1).
        [$sub, $cycle] = counterSub($this->student, $this->teacher, 8);

        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => null, // legacy
            'scheduled_at' => now()->subMinutes(30),
            'status' => SessionStatus::SCHEDULED,
        ]);

        $session->update(['status' => SessionStatus::COMPLETED]);
        counterRecord($session->fresh(), $this->student, $sub);
        expect($sub->fresh()->sessions_used)->toBe(1);

        $session->fresh()->update(['status' => SessionStatus::CANCELLED]);

        assertCounterInvariant($sub->fresh());
    });
});

describe('Bug #8 — admin toggle path drift', function () {
    it('B8-1 — toggle uncount + recount via SessionCountingService is symmetric', function () {
        [$sub, $cycle] = counterSub($this->student, $this->teacher, 8);

        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $cycle->id,
            'scheduled_at' => now()->subMinutes(30),
            'status' => SessionStatus::COMPLETED,
        ]);

        counterRecord($session->fresh(), $this->student, $sub);
        expect($sub->fresh()->sessions_used)->toBe(1);
        assertCounterInvariant($sub->fresh());

        $attendance = $session->meetingAttendances()->firstOrCreate(
            ['user_id' => $this->student->id, 'user_type' => 'student'],
            [
                'academy_id' => $this->academy->id,
                'session_type' => 'individual',
                'attendance_status' => 'attended',
                'counts_for_subscription' => true,
                'total_duration_minutes' => 30,
            ]
        );

        app(\App\Services\SessionCountingService::class)->setCountsForSubscription(
            $attendance->fresh(),
            $session->fresh(),
            false,
            auth()->id() ?? 1
        );

        assertCounterInvariant($sub->fresh());
        expect($sub->fresh()->sessions_used)->toBe(0);

        app(\App\Services\SessionCountingService::class)->setCountsForSubscription(
            $attendance->fresh(),
            $session->fresh(),
            true,
            auth()->id() ?? 1
        );

        assertCounterInvariant($sub->fresh());
        expect($sub->fresh()->sessions_used)->toBe(1);
    });

    it('B8-2 — observer re-fire on COMPLETED→SCHEDULED→COMPLETED does not double-count', function () {
        [$sub, $cycle] = counterSub($this->student, $this->teacher, 8);

        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $cycle->id,
            'scheduled_at' => now()->subMinutes(30),
            'status' => SessionStatus::SCHEDULED,
        ]);

        // Trip 1: SCHEDULED → COMPLETED
        $session->update(['status' => SessionStatus::COMPLETED]);
        counterRecord($session->fresh(), $this->student, $sub);
        expect($sub->fresh()->sessions_used)->toBe(1);

        // Trip 2: COMPLETED → SCHEDULED (observer reverses the consumption row)
        $session->fresh()->update(['status' => SessionStatus::SCHEDULED]);
        expect($sub->fresh()->sessions_used)->toBe(0);
        assertCounterInvariant($sub->fresh());

        // Trip 3: SCHEDULED → COMPLETED again — re-record via v2 writer
        $session->fresh()->update(['status' => SessionStatus::COMPLETED]);
        counterRecord($session->fresh(), $this->student, $sub);
        expect($sub->fresh()->sessions_used)->toBe(1);
        assertCounterInvariant($sub->fresh());
    });

    // (removed: B8-3 manufactured the drift state directly via withoutEvents.
    //  It is not a real code-path test — kept the discussion of the prod drift
    //  in docs/subscription-bugs-found.md instead.)
});
