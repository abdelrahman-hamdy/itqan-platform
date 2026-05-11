<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Exceptions\SubscriptionException;
use App\Models\QuranSession;
use App\Models\QuranSubscription;

/**
 * End-to-end coverage of `BaseSubscription::isSchedulable()` →
 * `BaseSessionObserver::creating()`. Any session created without
 * `Model::withoutEvents()` should be blocked when the linked subscription is
 * not schedulable.
 *
 *   - ACTIVE + PAID → schedulable (happy path).
 *   - PAUSED → not schedulable (auto-pause OR manual pause).
 *   - PAUSED + grace period → still NOT schedulable (only ACTIVE + grace works).
 *   - ACTIVE + payment_status=PENDING + grace → schedulable.
 *   - CANCELLED → not schedulable.
 *   - withoutEvents() bypasses the guard (used by cycle bootstrap).
 *
 * See `app/Observers/BaseSessionObserver.php::creating()` (line 42-60) and
 * `app/Models/BaseSubscription.php::isSchedulable()` (line 591-602).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'sched-int-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

/** Build a session row attached to the given subscription via the observer-firing path. */
function tryCreateSession(QuranSubscription $sub, \App\Models\Academy $academy, \App\Models\User $student, \App\Models\User $teacher): QuranSession
{
    // Factory generates session_code; calling create() fires the observer.
    return QuranSession::factory()->create([
        'academy_id' => $academy->id,
        'student_id' => $student->id,
        'quran_teacher_id' => $teacher->id,
        'quran_subscription_id' => $sub->id,
        'scheduled_at' => now()->addDay(),
        'duration_minutes' => 30,
        'status' => SessionStatus::SCHEDULED,
    ]);
}

describe('BaseSessionObserver::creating', function () {
    it('SS1 — ACTIVE + PAID: session creation succeeds and stamps subscription_cycle_id', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create(['payment_status' => SubscriptionPaymentStatus::PAID]);
        $cycle = $sub->ensureCurrentCycle();

        $session = tryCreateSession($sub->fresh(), $this->academy, $this->student, $this->teacher);

        expect($session->id)->not->toBeNull();
        // Observer stamped the active cycle id on the session.
        expect($session->subscription_cycle_id)->toBe($cycle->id);
    });

    it('SS2 — PAUSED: session creation throws SubscriptionException::notSchedulable', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->autoPaused()
            ->create();

        expect(fn () => tryCreateSession($sub, $this->academy, $this->student, $this->teacher))
            ->toThrow(SubscriptionException::class);
    });

    it('SS3 — PAUSED with grace period is still NOT schedulable (grace only works on ACTIVE)', function () {
        // The grace gate runs after the ACTIVE check, so a PAUSED + grace
        // sub is still rejected. This pins the spec-defined edge case.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->manuallyPaused()
            ->create([
                'metadata' => [
                    'grace_period_ends_at' => now()->addDays(5)->toDateTimeString(),
                ],
            ]);

        expect(fn () => tryCreateSession($sub, $this->academy, $this->student, $this->teacher))
            ->toThrow(SubscriptionException::class);
    });

    it('SS4 — ACTIVE + PENDING payment + grace period: schedulable', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->inGracePeriod(7)
            ->create(['payment_status' => SubscriptionPaymentStatus::PENDING]);
        $sub->ensureCurrentCycle();

        $session = tryCreateSession($sub->fresh(), $this->academy, $this->student, $this->teacher);
        expect($session->id)->not->toBeNull();
    });

    it('SS5 — CANCELLED: session creation throws SubscriptionException', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->cancelled()
            ->create();

        expect(fn () => tryCreateSession($sub, $this->academy, $this->student, $this->teacher))
            ->toThrow(SubscriptionException::class);
    });

    it('SS6 — Model::withoutEvents() bypass: cycle-bootstrap path can create rows on a non-schedulable sub', function () {
        // The cycle bootstrap (e.g., academic batch pre-allocation in
        // AcademicSubscription::activateFromPayment()) creates UNSCHEDULED
        // sessions before the subscription becomes ACTIVE. The observer
        // creating() guard is bypassed via Model::withoutEvents().
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->pending() // not schedulable
            ->create();

        $session = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'scheduled_at' => null,
            'duration_minutes' => 30,
            'status' => SessionStatus::UNSCHEDULED,
        ]));

        expect($session->id)->not->toBeNull();
    });
});
