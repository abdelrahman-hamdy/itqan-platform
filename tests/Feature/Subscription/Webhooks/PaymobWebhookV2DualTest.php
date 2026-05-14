<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Payment;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\SubscriptionAuditLog;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\Concerns\DualExecutesPayment;

/**
 * Tier 2 / G1: gated dual-execution wiring tests.
 *
 * These tests exercise the DualExecutesPayment trait directly because the
 * three webhook controllers all share the same wiring — we only need to
 * verify the trait once and assert each controller `use`s it (covered in
 * EasyKashWebhookV2DualTest + TapWebhookV2DualTest).
 *
 * Hard rules under test:
 *   1. Legacy path always runs (we never break payments).
 *   2. When dual flag OFF, no audit row.
 *   3. When dual flag ON, an audit row of action='payment.v2_shadow' lands
 *      with both view_state_before and view_state_after populated.
 *   4. If the v2 path throws, legacy result is still returned AND the
 *      exception is captured in the audit row's has_violations + payload.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    $this->package = QuranPackage::factory()->create([
        'academy_id' => $this->academy->id,
        'monthly_price' => 200,
        'session_duration_minutes' => 30,
    ]);

    // Build a freshly PENDING sub + cycle so the v2 path takes the
    // SubscriptionLifecycle::activate branch.
    $this->sub = QuranSubscription::factory()->make([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'package_id' => $this->package->id,
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'total_sessions' => 8,
        'sessions_used' => 0,
        'sessions_remaining' => 8,
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
        'last_payment_date' => null,
    ]);
    $this->sub->reconciling = true;
    $this->sub->save();
    $this->sub->reconciling = false;

    $this->cycle = SubscriptionCycle::factory()->create([
        'subscribable_type' => $this->sub->getMorphClass(),
        'subscribable_id' => $this->sub->id,
        'academy_id' => $this->academy->id,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);

    $this->sub->reconciling = true;
    $this->sub->current_cycle_id = $this->cycle->id;
    $this->sub->save();
    $this->sub->reconciling = false;
    $this->sub->refresh();

    $this->payment = Payment::factory()->create([
        'subscription_id' => $this->sub->id,
        'payable_type' => $this->sub->getMorphClass(),
        'payable_id' => $this->sub->id,
        'user_id' => $this->student->id,
        'academy_id' => $this->academy->id,
        'amount' => 200,
        'currency' => 'SAR',
        'payment_method' => 'paymob',
        'status' => 'completed',
        'payment_status' => 'paid',
        'payment_type' => 'subscription',
    ]);
});

/**
 * Anonymous controller exposing the trait so we can test it without
 * spinning up an HTTP request. Mirrors how the three webhook controllers
 * use it.
 */
function makeDualExecutor(): object
{
    return new class
    {
        use DualExecutesPayment {
            runWithDualExecution as public;
            defaultV2Callable as public;
            resolvePayableSubscription as public;
        }
    };
}

it('runs the legacy callable exactly once and returns its result', function () {
    config(['subscriptions.v2_payment_dual' => false]);

    $legacyCallCount = 0;
    $result = makeDualExecutor()->runWithDualExecution(
        $this->payment,
        function () use (&$legacyCallCount) {
            $legacyCallCount++;

            return 'legacy-ran';
        },
        fn () => 'v2-would-also-run',
    );

    expect($result)->toBe('legacy-ran')
        ->and($legacyCallCount)->toBe(1);

    // No audit row when dual flag is off.
    expect(SubscriptionAuditLog::query()->where('action', 'payment.v2_shadow')->count())->toBe(0);
});

it('writes a payment.v2_shadow audit row when dual flag is on', function () {
    config(['subscriptions.v2_payment_dual' => true]);

    $legacyCallCount = 0;
    $result = makeDualExecutor()->runWithDualExecution(
        $this->payment,
        function () use (&$legacyCallCount) {
            $legacyCallCount++;

            return 'legacy-ran';
        },
        fn () => ['routed' => 'noop'],
    );

    expect($result)->toBe('legacy-ran')
        ->and($legacyCallCount)->toBe(1);

    $audit = SubscriptionAuditLog::query()
        ->where('action', 'payment.v2_shadow')
        ->where('subscription_id', $this->sub->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->source)->toBe('webhook')
        ->and($audit->view_state_before)->not->toBeNull()
        ->and($audit->view_state_after)->not->toBeNull()
        ->and($audit->has_violations)->toBeFalse();
});

it('captures a v2 exception in the audit row without breaking legacy', function () {
    config(['subscriptions.v2_payment_dual' => true]);

    $legacyResult = makeDualExecutor()->runWithDualExecution(
        $this->payment,
        fn () => 'legacy-still-ran',
        fn () => throw new \RuntimeException('boom from v2'),
    );

    expect($legacyResult)->toBe('legacy-still-ran');

    $audit = SubscriptionAuditLog::query()
        ->where('action', 'payment.v2_shadow')
        ->where('subscription_id', $this->sub->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->has_violations)->toBeTrue()
        ->and($audit->after_state['payload']['v2_exception']['message'] ?? null)->toBe('boom from v2')
        ->and($audit->after_state['payload']['v2_exception']['class'] ?? null)->toBe(\RuntimeException::class);
});

it('routes a PENDING subscription through SubscriptionLifecycle::activate', function () {
    config(['subscriptions.v2_payment_dual' => true]);

    $callable = makeDualExecutor()->defaultV2Callable($this->payment);

    $result = $callable();
    expect($result)->toBe(['routed' => 'activate']);

    // After activate runs, the cycle should be PAID and the sub's
    // last_payment_date should be stamped (see SubscriptionLifecycle::activate).
    $this->cycle->refresh();
    $this->sub->refresh();
    expect($this->cycle->payment_status)->toBe(SubscriptionCycle::PAYMENT_PAID)
        ->and($this->sub->last_payment_date)->not->toBeNull();
});

it('routes an ACTIVE subscription with a pending current cycle through markCyclePaid', function () {
    // Promote the sub to ACTIVE+last_payment_date set, but leave the
    // current cycle PENDING — emulating a subsequent renewal payment.
    $this->sub->reconciling = true;
    $this->sub->status = SessionSubscriptionStatus::ACTIVE;
    $this->sub->payment_status = SubscriptionPaymentStatus::PAID;
    $this->sub->last_payment_date = now()->subDay();
    $this->sub->save();
    $this->sub->reconciling = false;

    config(['subscriptions.v2_payment_dual' => true]);

    $callable = makeDualExecutor()->defaultV2Callable($this->payment);
    $result = $callable();

    expect($result['routed'] ?? null)->toBe('mark_cycle_paid')
        ->and($result['cycle_id'] ?? null)->toBe($this->cycle->id);
});
