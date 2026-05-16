<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionViewState;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionPresentation;

/**
 * Unit tests for SubscriptionPresentation::viewStateFor() — the 8 canonical
 * cases from `docs/subscription-invariants.md §1`.
 *
 * Each test constructs a minimal subscription + cycle fixture and asserts
 * the derived view-state matches the expected case. Includes the edge
 * cases called out in Phase B:
 *   - NULL currentCycle → pending_first_payment (algorithm step 2).
 *   - cycle PENDING + cycle_state=QUEUED + payment_attempts=0 + never-paid →
 *     pending_first_payment (algorithm step 3).
 *   - grace window edge: now == grace_period_ends_at (must not be GRACE).
 *   - expired with no grace.
 *
 * Asserts INV-A7 (determinism) by running the algorithm twice in one test
 * and checking equality.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
    $this->presentation = app(SubscriptionPresentation::class);
});

/**
 * Helper: build a QuranSubscription + (optionally) a SubscriptionCycle in
 * the shape the test wants. Bypasses the v2 services on purpose — view
 * state derivation is read-only, so violating writer contracts here is
 * acceptable.
 *
 * Sub is saved with `reconciling = true` so the SubscriptionRowGuard
 * observer permits the derived-field write under test.
 */
function makeSub(array $subAttrs = [], ?array $cycleAttrs = null, $academy = null, $student = null, $teacher = null): QuranSubscription
{
    $academy = $academy ?? test()->academy;
    $student = $student ?? test()->student;
    $teacher = $teacher ?? test()->teacher;

    $sub = QuranSubscription::factory()->make(array_merge([
        'academy_id' => $academy->id,
        'student_id' => $student->id,
        'quran_teacher_id' => $teacher->id,
    ], $subAttrs));
    $sub->reconciling = true;
    $sub->save();
    $sub->reconciling = false;

    if ($cycleAttrs !== null) {
        $cycle = SubscriptionCycle::factory()->create(array_merge([
            'subscribable_type' => $sub->getMorphClass(),
            'subscribable_id' => $sub->id,
            'academy_id' => $academy->id,
        ], $cycleAttrs));

        $sub->reconciling = true;
        $sub->current_cycle_id = $cycle->id;
        $sub->save();
        $sub->reconciling = false;
        $sub->refresh();
    }

    return $sub;
}

test('case PENDING_FIRST_PAYMENT — no cycle exists', function () {
    $sub = makeSub([
        'status' => SessionSubscriptionStatus::PENDING,
        'last_payment_date' => null,
    ]);

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::PENDING_FIRST_PAYMENT);
});

test('case PENDING_FIRST_PAYMENT — cycle exists but never paid before (cycle_number=1, payment PENDING)', function () {
    $sub = makeSub(
        ['status' => SessionSubscriptionStatus::PENDING, 'last_payment_date' => null],
        [
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
            'cycle_number' => 1,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ],
    );

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::PENDING_FIRST_PAYMENT);
});

test('case ACTIVE_PAID — within window, paid, quota remaining', function () {
    $sub = makeSub(
        [
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'last_payment_date' => now()->subDay(),
        ],
        [
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->addDays(20),
            'total_sessions' => 8,
            'sessions_used' => 2,
        ],
    );

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::ACTIVE_PAID);
});

test('case PENDING_PAYMENT — within window but cycle payment PENDING (legacy lie-state)', function () {
    // Pre-Phase-3 this would have been ACTIVE_PAYMENT_DUE (in-window pending
    // with full access). Phase 3 routes the same shape to PENDING_PAYMENT so
    // scheduling/consumption refuse access until paid (or admin grace).
    $sub = makeSub(
        [
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID, // historical "lie"
            'last_payment_date' => now()->subMonth(), // sub has a prior paid history
        ],
        [
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
            'cycle_number' => 2,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->addDays(20),
            'total_sessions' => 8,
            'sessions_used' => 1,
        ],
    );

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::PENDING_PAYMENT);
});

test('case PAUSED_END_OF_PERIOD — within window, paid, quota exhausted', function () {
    $sub = makeSub(
        [
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'last_payment_date' => now()->subDay(),
        ],
        [
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'starts_at' => now()->subDays(15),
            'ends_at' => now()->addDays(15),
            'total_sessions' => 8,
            'sessions_used' => 8,
        ],
    );

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::PAUSED_END_OF_PERIOD);
});

test('case PAUSED_ADMIN — subscription.status == PAUSED', function () {
    $sub = makeSub(
        [
            'status' => SessionSubscriptionStatus::PAUSED,
            'last_payment_date' => now()->subDay(),
            'paused_at' => now()->subHours(2),
        ],
        [
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->addDays(20),
        ],
    );

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::PAUSED_ADMIN);
});

test('case GRACE_ADMIN — past ends_at, within grace window', function () {
    $sub = makeSub(
        [
            'status' => SessionSubscriptionStatus::ACTIVE,
            'last_payment_date' => now()->subMonth(),
        ],
        [
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDay(),
            'grace_period_ends_at' => now()->addDays(5),
        ],
    );

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::GRACE_ADMIN);
});

test('case EXPIRED — past ends_at, no grace', function () {
    $sub = makeSub(
        [
            'status' => SessionSubscriptionStatus::EXPIRED,
            'last_payment_date' => now()->subMonths(2),
        ],
        [
            'cycle_state' => SubscriptionCycle::STATE_ARCHIVED,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDays(10),
            'grace_period_ends_at' => null,
        ],
    );

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::EXPIRED);
});

test('case EXPIRED — past ends_at AND past grace', function () {
    $sub = makeSub(
        [
            'status' => SessionSubscriptionStatus::EXPIRED,
            'last_payment_date' => now()->subMonths(2),
        ],
        [
            'cycle_state' => SubscriptionCycle::STATE_ARCHIVED,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDays(20),
            'grace_period_ends_at' => now()->subDays(2),
        ],
    );

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::EXPIRED);
});

test('case CANCELLED — status CANCELLED wins over everything', function () {
    $sub = makeSub(
        [
            'status' => SessionSubscriptionStatus::CANCELLED,
            'cancelled_at' => now()->subDay(),
        ],
        [
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->addDays(20),
        ],
    );

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::CANCELLED);
});

test('edge case — NULL currentCycle resolves to PENDING_FIRST_PAYMENT', function () {
    $sub = makeSub([
        'status' => SessionSubscriptionStatus::PENDING,
        'last_payment_date' => null,
    ]);

    expect($sub->current_cycle_id)->toBeNull()
        ->and($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::PENDING_FIRST_PAYMENT);
});

test('edge case — first-payment branch (PENDING cycle, never paid, cycle_number=1) -> PENDING_FIRST_PAYMENT', function () {
    $sub = makeSub(
        [
            'status' => SessionSubscriptionStatus::PENDING,
            'last_payment_date' => null,
        ],
        [
            'cycle_state' => SubscriptionCycle::STATE_QUEUED,
            'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
            'cycle_number' => 1,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ],
    );

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::PENDING_FIRST_PAYMENT);
});

test('edge case — grace window edge (now == grace_period_ends_at) is NOT GRACE_ADMIN', function () {
    // The algorithm uses `$now->lt($graceEndsAt)`. When now equals grace_period_ends_at,
    // the predicate is false → falls through to EXPIRED.
    $sub = makeSub(
        [
            'status' => SessionSubscriptionStatus::ACTIVE,
            'last_payment_date' => now()->subMonth(),
        ],
        [
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDays(2),
            'grace_period_ends_at' => now(),
        ],
    );

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::EXPIRED);
});

test('edge case — expired with NO grace_period_ends_at -> EXPIRED', function () {
    $sub = makeSub(
        [
            'status' => SessionSubscriptionStatus::ACTIVE,
            'last_payment_date' => now()->subMonth(),
        ],
        [
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(),
            'grace_period_ends_at' => null,
        ],
    );

    expect($this->presentation->viewStateFor($sub))
        ->toBe(SubscriptionViewState::EXPIRED);
});

test('INV-A7 — viewStateFor is deterministic for identical input', function () {
    $sub = makeSub(
        [
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'last_payment_date' => now()->subDay(),
        ],
        [
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->addDays(20),
            'total_sessions' => 8,
            'sessions_used' => 2,
        ],
    );

    $a = $this->presentation->viewStateFor($sub);
    $b = $this->presentation->viewStateFor($sub);

    expect($a)->toBe($b);
});
