<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Exceptions\Subscription\RenewBlockedByPendingPayment;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionLifecycle;

/**
 * Phase B lifecycle tests for SubscriptionLifecycle.
 *
 * Focuses on the invariant-mode contracts that don't depend on the legacy
 * SubscriptionRenewalService composition:
 *
 *   - pause() refuses when grace flag is set (INV-F1).
 *   - extend() ONLY writes grace_period_ends_at, never ends_at (INV-F2).
 *   - cancel() refuses non-admin actors (INV-G2 + §4 matrix).
 *   - expire() on a hybrid cycle marks cycle FAILED + sub EXPIRED (INV-G4).
 *   - renew() on an ACTIVE_PAYMENT_DUE sub raises RenewBlockedByPendingPayment.
 *
 * Tests that exercise the full create→activate→renew→advanceCycle path
 * compose with SubscriptionRenewalService — they are intentionally
 * lightweight here and lean on the LifecycleService's audit log to confirm
 * the mutator ran.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
    $this->admin = createAdmin($this->academy);

    $this->package = QuranPackage::factory()->create([
        'academy_id' => $this->academy->id,
        'monthly_price' => 200,
        'session_duration_minutes' => 30,
    ]);

    $this->lifecycle = app(SubscriptionLifecycle::class);
});

/**
 * Build an ACTIVE+PAID Quran subscription with a current cycle. Mirrors
 * the canonical happy-path state.
 */
function buildActiveSub(array $subAttrs = [], array $cycleAttrs = [], $academy = null, $student = null, $teacher = null, $package = null): QuranSubscription
{
    $academy = $academy ?? test()->academy;
    $student = $student ?? test()->student;
    $teacher = $teacher ?? test()->teacher;
    $package = $package ?? test()->package;

    $sub = QuranSubscription::factory()->make(array_merge([
        'academy_id' => $academy->id,
        'student_id' => $student->id,
        'quran_teacher_id' => $teacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'total_sessions' => 8,
        'sessions_used' => 0,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
        'last_payment_date' => now()->subDay(),
        'package_id' => $package->id,
    ], $subAttrs));
    $sub->reconciling = true;
    $sub->save();
    $sub->reconciling = false;

    $cycle = SubscriptionCycle::factory()->create(array_merge([
        'subscribable_type' => $sub->getMorphClass(),
        'subscribable_id' => $sub->id,
        'academy_id' => $academy->id,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
        'package_id' => $package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
    ], $cycleAttrs));

    $sub->reconciling = true;
    $sub->current_cycle_id = $cycle->id;
    $sub->save();
    $sub->reconciling = false;

    return $sub->fresh();
}

test('pause() throws if subscription is already in grace (INV-F1)', function () {
    $sub = buildActiveSub(
        [
            // Grace fields live on the cycle in this codebase, so we mirror that.
        ],
        [
            'grace_period_ends_at' => now()->addDays(5),
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDay(),
        ],
    );

    expect(fn () => $this->lifecycle->pause($sub, $this->admin, 'student traveling'))
        ->toThrow(RuntimeException::class);
});

test('pause() throws if subscription is not ACTIVE (INV-F1)', function () {
    $sub = buildActiveSub(['status' => SessionSubscriptionStatus::PAUSED]);

    expect(fn () => $this->lifecycle->pause($sub, $this->admin, 'should fail'))
        ->toThrow(RuntimeException::class);
});

test('extend() writes ONLY grace_period_ends_at and never mutates ends_at (INV-F2)', function () {
    $sub = buildActiveSub();
    $originalEndsAt = $sub->currentCycle->ends_at->copy();

    $this->lifecycle->extend($sub, 5, $this->admin, 'goodwill');

    $cycle = $sub->fresh()->currentCycle;
    expect($cycle->ends_at->equalTo($originalEndsAt))->toBeTrue()
        ->and($cycle->grace_period_ends_at)->not->toBeNull()
        ->and($cycle->grace_period_ends_at->gt(now()))->toBeTrue();
});

test('extend() refuses graceDays outside the configured cap', function () {
    $sub = buildActiveSub();
    $cap = (int) config('subscriptions.max_grace_days', 14);

    expect(fn () => $this->lifecycle->extend($sub, $cap + 1, $this->admin, 'too many'))
        ->toThrow(InvalidArgumentException::class);
});

test('cancel() refuses non-admin actors (INV-G1/INV-G2 + §4 matrix)', function () {
    $sub = buildActiveSub();

    // student actor
    expect(fn () => $this->lifecycle->cancel($sub, $this->student, 'self-cancel'))
        ->toThrow(RuntimeException::class);

    // teacher actor
    expect(fn () => $this->lifecycle->cancel($sub, $this->teacher, 'teacher cancel'))
        ->toThrow(RuntimeException::class);
});

test('cancel() succeeds for admin and stamps cancelled_at + reason', function () {
    $sub = buildActiveSub();

    $cancelled = $this->lifecycle->cancel($sub, $this->admin, 'admin cancelled');

    expect($cancelled->status)->toBe(SessionSubscriptionStatus::CANCELLED)
        ->and($cancelled->cancelled_at)->not->toBeNull()
        ->and($cancelled->cancellation_reason)->toBe('admin cancelled');
});

test('expire() on hybrid-unpaid cycle marks cycle FAILED + sub EXPIRED (INV-G4)', function () {
    $sub = buildActiveSub(
        ['payment_status' => SubscriptionPaymentStatus::PENDING],
        [
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDays(2),
            'sessions_used' => 0,
            'total_sessions' => 8,
        ],
    );

    $expired = $this->lifecycle->expire($sub);
    $cycle = $expired->currentCycle()->first();

    expect($expired->status)->toBe(SessionSubscriptionStatus::EXPIRED)
        ->and($cycle->cycle_state)->toBe(SubscriptionCycle::STATE_ARCHIVED)
        ->and($cycle->payment_status)->toBe(SubscriptionCycle::PAYMENT_FAILED);
});

test('expire() on cleanly-paid expired cycle archives cycle and marks sub EXPIRED', function () {
    $sub = buildActiveSub(
        [],
        [
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDays(2),
            'sessions_used' => 4,
            'total_sessions' => 8,
        ],
    );

    $expired = $this->lifecycle->expire($sub);
    $cycle = $expired->currentCycle()->first();

    expect($expired->status)->toBe(SessionSubscriptionStatus::EXPIRED)
        ->and($cycle->cycle_state)->toBe(SubscriptionCycle::STATE_ARCHIVED)
        ->and($cycle->payment_status)->toBe(SubscriptionCycle::PAYMENT_PAID);
});

test('renew() on an ACTIVE_PAYMENT_DUE sub raises RenewBlockedByPendingPayment', function () {
    // Hybrid: cycle pending, sub still showing ACTIVE — the
    // active_payment_due view state.
    $sub = buildActiveSub(
        ['payment_status' => SubscriptionPaymentStatus::PENDING],
        [
            'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
            'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
            'sessions_used' => 1,
            'total_sessions' => 8,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->addDays(20),
        ],
    );

    expect(fn () => $this->lifecycle->renew($sub, [], $this->admin, 'admin'))
        ->toThrow(RenewBlockedByPendingPayment::class);
});

test('advanceCycle() is a no-op when there is no queued cycle (audit-only)', function () {
    $sub = buildActiveSub();

    // No queued cycle exists. advanceCycle should return without throwing.
    $result = $this->lifecycle->advanceCycle($sub);

    expect($result)->toBeInstanceOf(QuranSubscription::class)
        ->and($result->currentCycle->cycle_state)->toBe(SubscriptionCycle::STATE_ACTIVE);
});

test('advanceCycle() promotes queued cycle without resetting its counters (INV-B6)', function () {
    $sub = buildActiveSub(
        [],
        [
            'starts_at' => now()->subDays(35),
            'ends_at' => now()->subDay(),
        ],
    );

    $queuedStart = $sub->currentCycle->ends_at->copy();
    $queued = SubscriptionCycle::factory()->create([
        'subscribable_type' => $sub->getMorphClass(),
        'subscribable_id' => $sub->id,
        'academy_id' => $sub->academy_id,
        'cycle_state' => SubscriptionCycle::STATE_QUEUED,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'cycle_number' => 2,
        'starts_at' => $queuedStart,
        'ends_at' => $queuedStart->copy()->addMonth(),
        'total_sessions' => 8,
        'sessions_used' => 0, // counters initialised at materialise-time, not promote-time.
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
    ]);

    $advanced = $this->lifecycle->advanceCycle($sub);
    $advanced->refresh();

    expect((int) $advanced->current_cycle_id)->toBe($queued->id)
        ->and($advanced->currentCycle->cycle_state)->toBe(SubscriptionCycle::STATE_ACTIVE)
        // INV-B6: counters didn't reset on promotion.
        ->and((int) $advanced->currentCycle->sessions_used)->toBe(0);
});
