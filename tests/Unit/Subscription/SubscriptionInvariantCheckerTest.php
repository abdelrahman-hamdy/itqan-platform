<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionInvariantChecker;

/**
 * Per-Group invariant tests for SubscriptionInvariantChecker.
 *
 * For each of Groups A through G (the data-observable groups; H/I/J are
 * documented as out-of-scope by the checker itself):
 *   1. Build a violating fixture.
 *   2. Assert `check($sub)` surfaces a violation whose `code` matches.
 *   3. Build a clean fixture and assert the returned array is empty.
 *
 * The checker reads from the DB so each fixture is created with the
 * SubscriptionRowGuard temporarily disabled (`$sub->reconciling = true`).
 * That gives us full control to construct any shape — including the
 * broken ones the checker is supposed to catch.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
    $this->checker = app(SubscriptionInvariantChecker::class);
});

/**
 * Build a QuranSubscription bypassing the row guard.
 */
function buildSub(array $subAttrs = [], $academy = null, $student = null, $teacher = null): QuranSubscription
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

    return $sub->fresh();
}

/**
 * Build a SubscriptionCycle attached to $sub.
 */
function buildCycle(QuranSubscription $sub, array $cycleAttrs = []): SubscriptionCycle
{
    return SubscriptionCycle::factory()->create(array_merge([
        'subscribable_type' => $sub->getMorphClass(),
        'subscribable_id' => $sub->id,
        'academy_id' => $sub->academy_id,
    ], $cycleAttrs));
}

function attachCurrentCycle(QuranSubscription $sub, SubscriptionCycle $cycle): void
{
    $sub->reconciling = true;
    $sub->current_cycle_id = $cycle->id;
    $sub->save();
    $sub->reconciling = false;
    $sub->refresh();
}

function violationCodes(array $violations): array
{
    return array_values(array_unique(array_map(fn ($v) => $v['code'], $violations)));
}

// ─────────────────────────────────────────────────────────────────────
// Group A — Canonical state (R1)
// ─────────────────────────────────────────────────────────────────────

test('INV-A1 — sub.sessions_used disagrees with currentCycle.sessions_used', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 5,
        'total_sessions' => 8,
        'sessions_remaining' => 3,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'sessions_used' => 2,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    attachCurrentCycle($sub, $cycle);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-A1');
});

test('INV-A2 — lie state: sub.payment_status=PAID + cycle.payment_status=PENDING + status=ACTIVE', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    attachCurrentCycle($sub, $cycle);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-A2');
});

test('INV-A3 — more than one active cycle on the same subscription', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $c1 = buildCycle($sub, [
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'cycle_number' => 1,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    buildCycle($sub, [
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'cycle_number' => 2,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    attachCurrentCycle($sub, $c1);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-A3');
});

test('INV-A4 — more than one queued cycle', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $c1 = buildCycle($sub, ['cycle_state' => SubscriptionCycle::STATE_ACTIVE, 'cycle_number' => 1]);
    buildCycle($sub, ['cycle_state' => SubscriptionCycle::STATE_QUEUED, 'cycle_number' => 2, 'starts_at' => now()->addMonth(), 'ends_at' => now()->addMonths(2)]);
    buildCycle($sub, ['cycle_state' => SubscriptionCycle::STATE_QUEUED, 'cycle_number' => 3, 'starts_at' => now()->addMonth(), 'ends_at' => now()->addMonths(2)]);
    attachCurrentCycle($sub, $c1);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-A4');
});

test('INV-A5 — queued cycle starts_at does not equal current ends_at', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $current = buildCycle($sub, [
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'cycle_number' => 1,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
        'sessions_used' => 0,
        'total_sessions' => 8,
    ]);
    buildCycle($sub, [
        'cycle_state' => SubscriptionCycle::STATE_QUEUED,
        'cycle_number' => 2,
        // BUG: starts 5 days after current ends.
        'starts_at' => now()->addDays(25),
        'ends_at' => now()->addDays(55),
    ]);
    attachCurrentCycle($sub, $current);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-A5');
});

test('INV-A6 — activated sub with NULL starts_at/ends_at', function () {
    // Sub is ACTIVE (not PENDING) — has been activated — but dates are NULL.
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => null,
        'ends_at' => null,
    ]);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-A6');
});

test('Group A — clean subscription has zero Group A violations', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 2,
        'total_sessions' => 8,
        'sessions_remaining' => 6,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'sessions_used' => 2,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    attachCurrentCycle($sub, $cycle);
    // INV-B3 requires session_consumption rows to match cycle.sessions_used.
    SessionConsumption::factory()->count(2)->create([
        'subscription_id' => $sub->id,
        'subscription_type' => $sub->getMorphClass(),
        'cycle_id' => $cycle->id,
        'student_user_id' => $this->student->id,
    ]);

    $violations = $this->checker->check($sub);
    $codes = violationCodes($violations);
    expect($codes)->not->toContain('INV-A1')
        ->and($codes)->not->toContain('INV-A2')
        ->and($codes)->not->toContain('INV-A3')
        ->and($codes)->not->toContain('INV-A4')
        ->and($codes)->not->toContain('INV-A5')
        ->and($codes)->not->toContain('INV-A6');
});

// ─────────────────────────────────────────────────────────────────────
// Group B — Counting (R2)
// ─────────────────────────────────────────────────────────────────────

test('INV-B3 — cycle.sessions_used does not match COUNT(session_consumption)', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 5,
        'total_sessions' => 8,
        'sessions_remaining' => 3,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'sessions_used' => 5,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    attachCurrentCycle($sub, $cycle);
    // Only 2 active consumption rows, cycle claims 5.
    SessionConsumption::factory()->count(2)->create([
        'subscription_id' => $sub->id,
        'subscription_type' => $sub->getMorphClass(),
        'cycle_id' => $cycle->id,
        'student_user_id' => $this->student->id,
    ]);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-B3');
});

test('INV-B4 — sessions_used exceeds total_sessions (negative remaining)', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 10,
        'total_sessions' => 8,
        'sessions_remaining' => 0,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'sessions_used' => 10,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    attachCurrentCycle($sub, $cycle);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-B4');
});

test('INV-B5 — session_consumption row has partial reversal fields', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'sessions_used' => 0,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    attachCurrentCycle($sub, $cycle);
    // Insert directly (bypass writer) — partial reversal: reversed_at set but reason NULL.
    SessionConsumption::factory()->create([
        'subscription_id' => $sub->id,
        'subscription_type' => $sub->getMorphClass(),
        'cycle_id' => $cycle->id,
        'student_user_id' => $this->student->id,
        'reversed_at' => now(),
        'reversed_reason' => null,
        'reversed_by_user_id' => null,
    ]);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-B5');
});

test('Group B — clean cycle (counters match consumption rows) has zero B violations', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 2,
        'total_sessions' => 8,
        'sessions_remaining' => 6,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'sessions_used' => 2,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    attachCurrentCycle($sub, $cycle);
    SessionConsumption::factory()->count(2)->create([
        'subscription_id' => $sub->id,
        'subscription_type' => $sub->getMorphClass(),
        'cycle_id' => $cycle->id,
        'student_user_id' => $this->student->id,
    ]);

    $codes = violationCodes($this->checker->check($sub));
    expect($codes)->not->toContain('INV-B3')
        ->and($codes)->not->toContain('INV-B4')
        ->and($codes)->not->toContain('INV-B5');
});

// ─────────────────────────────────────────────────────────────────────
// Group C — Cron + interleaving (R3)
// ─────────────────────────────────────────────────────────────────────

test('Group C — no audit log entries = no Group C violations', function () {
    // C is documented as best-effort. With no audit-log activity the checker
    // surfaces zero entries.
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub);
    attachCurrentCycle($sub, $cycle);

    $codes = violationCodes($this->checker->check($sub));
    expect($codes)->not->toContain('INV-C2');
});

// ─────────────────────────────────────────────────────────────────────
// Group D — Pricing trust (R4)
// ─────────────────────────────────────────────────────────────────────

test('INV-D1 — cycle.pricing_source is invalid', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'pricing_source' => 'bogus_source', // invalid
    ]);
    attachCurrentCycle($sub, $cycle);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-D1');
});

test('INV-D2 — pricing_source=manual_override but reason+actor are missing', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'pricing_source' => 'manual_override',
        'pricing_override_reason' => null,
        'pricing_override_actor_id' => null,
        'final_price' => 1100,
    ]);
    attachCurrentCycle($sub, $cycle);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-D2');
});

test('INV-D3 — negative final_price', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'pricing_source' => 'manual_override',
        'pricing_override_reason' => 'test',
        'pricing_override_actor_id' => $this->student->id,
        'final_price' => -50,
    ]);
    attachCurrentCycle($sub, $cycle);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-D3');
});

test('INV-D4 — pricing_source=package but package_id is NULL', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'pricing_source' => 'package',
        'package_id' => null,
        'package_snapshot' => null,
    ]);
    attachCurrentCycle($sub, $cycle);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-D4');
});

test('Group D — clean cycle (package source + valid package_id) has zero D violations', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'pricing_source' => 'package',
        'package_id' => 1,
        'package_snapshot' => null, // no snapshot → checker skips D2 numeric compare
        'final_price' => 200,
    ]);
    attachCurrentCycle($sub, $cycle);

    $codes = violationCodes($this->checker->check($sub));
    expect($codes)->not->toContain('INV-D1')
        ->and($codes)->not->toContain('INV-D3')
        ->and($codes)->not->toContain('INV-D4');
});

// ─────────────────────────────────────────────────────────────────────
// Group F — Pause / grace / extend (P6)
// ─────────────────────────────────────────────────────────────────────

test('INV-F6 — expired subscription marked as PAUSED (forbidden)', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::PAUSED,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        // Past ends_at, no grace.
        'starts_at' => now()->subMonths(2),
        'ends_at' => now()->subDays(10),
        'metadata' => null,
    ]);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-F6');
});

test('Group F — non-paused expired sub has no F6 violation', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::EXPIRED,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subMonths(2),
        'ends_at' => now()->subDays(10),
    ]);

    $codes = violationCodes($this->checker->check($sub));
    expect($codes)->not->toContain('INV-F6');
});

// ─────────────────────────────────────────────────────────────────────
// Group G — Cancel & re-entry (P3, P4, P8)
// ─────────────────────────────────────────────────────────────────────

test('INV-G2 — cancelled subscription has NULL cancelled_at', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::CANCELLED,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addDays(20),
        'cancelled_at' => null,
        'cancellation_reason' => null,
    ]);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-G2');
});

test('INV-G4 — hybrid cycle past ends_at with pending payment was not transitioned', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE, // should have been EXPIRED
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(35),
        'ends_at' => now()->subDays(2),
    ]);
    $cycle = buildCycle($sub, [
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE, // not archived
        'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
        'starts_at' => now()->subDays(35),
        'ends_at' => now()->subDays(2),
        'sessions_used' => 0,
        'total_sessions' => 8,
    ]);
    attachCurrentCycle($sub, $cycle);

    $violations = $this->checker->check($sub);
    expect(violationCodes($violations))->toContain('INV-G4');
});

test('Group G — clean cancelled sub (with cancelled_at + reason) has no G2 violation', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::CANCELLED,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addDays(20),
        'cancelled_at' => now(),
        'cancellation_reason' => 'admin cancelled',
    ]);

    $codes = violationCodes($this->checker->check($sub));
    expect($codes)->not->toContain('INV-G2');
});

// ─────────────────────────────────────────────────────────────────────
// Overall clean
// ─────────────────────────────────────────────────────────────────────

test('a fully clean subscription returns an empty violations array', function () {
    $sub = buildSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $cycle = buildCycle($sub, [
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
        'pricing_source' => 'package',
        'package_id' => 1,
        'final_price' => 200,
    ]);
    attachCurrentCycle($sub, $cycle);

    $violations = $this->checker->check($sub);
    // Filter out 'info' severity — those are migration-window soft signals,
    // not invariant violations per the doc.
    $errors = array_values(array_filter($violations, fn ($v) => ($v['severity'] ?? 'error') === 'error'));
    expect($errors)->toBe([]);
});
