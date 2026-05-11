<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;

/**
 * Counter-consistency invariants for the subscription↔session counting layer.
 * Asserts the CORRECT expected behavior — tests should fail if the bugs
 * reported in `docs/subscription-bugs-found.md` (#4 and #8) are real.
 *
 * The core invariant under test:
 *   cycle.sessions_used == COUNT(session WHERE session.subscription_cycle_id = cycle.id
 *                                       AND session.subscription_counted = 1)
 *
 * AND mirror for the subscription row:
 *   sub.sessions_used == cycle.sessions_used  (when cycle is the active cycle)
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

/** Asserts the cycle-counter invariant for a given subscription. */
function assertCounterInvariant(QuranSubscription $sub): void
{
    $cycle = SubscriptionCycle::find($sub->current_cycle_id);
    expect($cycle)->not->toBeNull();

    $countedSessionsInCycle = QuranSession::query()
        ->where('quran_subscription_id', $sub->id)
        ->where('subscription_counted', true)
        ->where(function ($q) use ($cycle) {
            $q->where('subscription_cycle_id', $cycle->id)
                ->orWhereNull('subscription_cycle_id');
        })
        ->count();

    expect($cycle->sessions_used)->toBe($countedSessionsInCycle, sprintf(
        'cycle.sessions_used=%d but COUNT(session.counted=1)=%d (drift!)',
        $cycle->sessions_used,
        $countedSessionsInCycle
    ));
    expect($sub->fresh()->sessions_used)->toBe($countedSessionsInCycle, sprintf(
        'sub.sessions_used=%d but COUNT(session.counted=1)=%d (drift!)',
        $sub->fresh()->sessions_used,
        $countedSessionsInCycle
    ));
}

describe('Bug #4 — cancel-after-complete drift', function () {
    it('B4-1 — COMPLETED → CANCELLED reverses the cycle counter cleanly', function () {
        [$sub, $cycle] = counterSub($this->student, $this->teacher, 8);

        // Step 1: create + count one session via the observer path
        $session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $cycle->id,
            'scheduled_at' => now()->subMinutes(30),
            'status' => SessionStatus::SCHEDULED,
        ]);

        // Mark COMPLETED — observer should fire useSession via job, but we
        // call updateSubscriptionUsage directly to keep the test deterministic.
        $session->update(['status' => SessionStatus::COMPLETED]);
        $session = $session->fresh();
        $session->updateSubscriptionUsage();
        $session = $session->fresh();
        expect($sub->fresh()->sessions_used)->toBe(1);
        expect($cycle->fresh()->sessions_used)->toBe(1);
        assertCounterInvariant($sub->fresh());

        // Step 2: flip to CANCELLED — observer reverses via reverseSubscriptionAndEarnings
        $session->update(['status' => SessionStatus::CANCELLED]);

        // Invariant must hold: 0 counted sessions, 0 used on cycle and sub
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

        // COMPLETED the original (reload between steps to mirror production)
        $original->update(['status' => SessionStatus::COMPLETED]);
        $original = $original->fresh();
        $original->updateSubscriptionUsage();
        $original = $original->fresh();
        expect($cycle->fresh()->sessions_used)->toBe(1);

        // CANCEL it — reverse fires via observer
        $original->update(['status' => SessionStatus::CANCELLED]);
        expect($cycle->fresh()->sessions_used)->toBe(0);

        // Schedule a make-up + complete it
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
        $makeup = $makeup->fresh();
        $makeup->updateSubscriptionUsage();

        // Invariant: only 1 counted session in the cycle, so cycle.used must equal 1
        assertCounterInvariant($sub->fresh());
        expect($cycle->fresh()->sessions_used)->toBe(1);
        expect($sub->fresh()->sessions_used)->toBe(1);
    });

    it('B4-3 — reverse path handles NULL subscription_cycle_id correctly (legacy sessions)', function () {
        // Simulates Sub 1024's prod state: legacy sessions with NULL cycle_id
        // were created before the cycle column was populated. The reverse
        // path uses subscription_cycle_id ?? current_cycle_id — which can
        // target the wrong cycle if the sub has rolled forward.
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
        $session = $session->fresh();
        $session->updateSubscriptionUsage();
        $session = $session->fresh();
        expect($sub->fresh()->sessions_used)->toBe(1);

        // Cancel — reverse fires with cycleId=NULL → falls back to current_cycle_id
        $session->update(['status' => SessionStatus::CANCELLED]);

        // Invariant must hold even with legacy session
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
            'subscription_counted' => false,
        ]);

        // Count it the canonical way
        $session->fresh()->updateSubscriptionUsage();
        expect($sub->fresh()->sessions_used)->toBe(1);
        assertCounterInvariant($sub->fresh());

        // Simulate admin toggle uncount: build attendance + call the service
        $attendance = $session->meetingAttendances()->firstOrCreate(
            ['user_id' => $this->student->id, 'user_type' => 'student'],
            [
                'academy_id' => $this->academy->id,
                'session_type' => 'individual',
                'attendance_status' => 'attended',
                'counts_for_subscription' => true,
                'total_duration_minutes' => 30,
                'subscription_counted_at' => now(),
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

        // Toggle recount
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
        $session = $session->fresh();
        $session->updateSubscriptionUsage();
        $session = $session->fresh();
        expect($sub->fresh()->sessions_used)->toBe(1);

        // Trip 2: COMPLETED → SCHEDULED (observer should reverse)
        $session->update(['status' => SessionStatus::SCHEDULED]);
        expect($sub->fresh()->sessions_used)->toBe(0);
        assertCounterInvariant($sub->fresh());

        // Trip 3: SCHEDULED → COMPLETED again (observer should re-count)
        $session->update(['status' => SessionStatus::COMPLETED]);
        $session = $session->fresh();
        $session->updateSubscriptionUsage();
        $session = $session->fresh();
        expect($sub->fresh()->sessions_used)->toBe(1);
        assertCounterInvariant($sub->fresh());
    });

    // (removed: B8-3 manufactured the drift state directly via withoutEvents.
    //  It is not a real code-path test — kept the discussion of the prod drift
    //  in docs/subscription-bugs-found.md instead.)
});
