<?php

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Jobs\ExpireGracePeriodSubscriptions;
use App\Models\QuranSubscription;
use Carbon\Carbon;

beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

/**
 * Helper: create a QuranSubscription with given overrides.
 */
function createSubscription(array $overrides = []): QuranSubscription
{
    return QuranSubscription::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'billing_cycle' => BillingCycle::MONTHLY,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addDays(20),
    ], $overrides));
}

// ========================================
// isInGracePeriod() Tests
// ========================================

test('isInGracePeriod returns true when grace_period_ends_at is in future', function () {
    $subscription = createSubscription([
        'metadata' => [
            'grace_period_ends_at' => now()->addDays(3)->toIso8601String(),
            'grace_period_started_at' => now()->toIso8601String(),
        ],
    ]);

    expect($subscription->isInGracePeriod())->toBeTrue();
});

test('isInGracePeriod returns true with legacy grace_period_expires_at key', function () {
    $subscription = createSubscription([
        'metadata' => [
            'grace_period_expires_at' => now()->addDays(3)->toIso8601String(),
        ],
    ]);

    expect($subscription->isInGracePeriod())->toBeTrue();
});

test('isInGracePeriod returns false when grace period has expired', function () {
    $subscription = createSubscription([
        'metadata' => [
            'grace_period_ends_at' => now()->subDay()->toIso8601String(),
        ],
    ]);

    expect($subscription->isInGracePeriod())->toBeFalse();
});

test('isInGracePeriod returns false when no grace metadata exists', function () {
    $subscription = createSubscription(['metadata' => null]);

    expect($subscription->isInGracePeriod())->toBeFalse();
});

// ========================================
// canAccess() during grace period
// ========================================

test('canAccess returns true during active grace period despite FAILED payment', function () {
    $subscription = createSubscription([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::FAILED,
        'metadata' => [
            'grace_period_ends_at' => now()->addDays(3)->toIso8601String(),
            'grace_period_started_at' => now()->toIso8601String(),
        ],
    ]);

    expect($subscription->canAccess())->toBeTrue();
});

test('canAccess returns false after grace period expires with FAILED payment', function () {
    $subscription = createSubscription([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::FAILED,
        'metadata' => [
            'grace_period_ends_at' => now()->subDay()->toIso8601String(),
        ],
    ]);

    // Grace expired + payment_status=FAILED → allowsAccess()=false
    expect($subscription->canAccess())->toBeFalse();
});

// ========================================
// getGracePeriodEndsAt() Tests
// ========================================

test('getGracePeriodEndsAt returns correct date from standard key', function () {
    $graceEnd = now()->addDays(3);
    $subscription = createSubscription([
        'metadata' => [
            'grace_period_ends_at' => $graceEnd->toIso8601String(),
        ],
    ]);

    $result = $subscription->getGracePeriodEndsAt();

    expect($result)->toBeInstanceOf(Carbon::class);
    expect($result->toDateString())->toBe($graceEnd->toDateString());
});

test('getGracePeriodEndsAt returns correct date from legacy key', function () {
    $graceEnd = now()->addDays(2);
    $subscription = createSubscription([
        'metadata' => [
            'grace_period_expires_at' => $graceEnd->toIso8601String(),
        ],
    ]);

    $result = $subscription->getGracePeriodEndsAt();

    expect($result)->toBeInstanceOf(Carbon::class);
    expect($result->toDateString())->toBe($graceEnd->toDateString());
});

// ========================================
// Background Jobs
// ========================================

test('ExpireGracePeriodSubscriptions job cancels subscriptions with expired grace and FAILED payment', function () {
    // Create subscription in grace period with expired grace + FAILED payment
    $subscription = createSubscription([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::FAILED,
        'metadata' => [
            'grace_period_ends_at' => now()->subDay()->toIso8601String(),
            'grace_period_started_at' => now()->subDays(4)->toIso8601String(),
            'renewal_failed_count' => 3,
        ],
    ]);

    // Run the job synchronously
    (new ExpireGracePeriodSubscriptions)->handle();

    $subscription->refresh();

    expect($subscription->status)->toBe(SessionSubscriptionStatus::CANCELLED);
    expect($subscription->payment_status)->toBe(SubscriptionPaymentStatus::FAILED);
});

test('SuspendExpiredGraceSubscriptions command suspends subscriptions with expired admin-granted grace', function () {
    // Create subscription with admin-granted grace (payment_status=PAID) that has expired
    $subscription = createSubscription([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'metadata' => [
            'grace_period_ends_at' => now()->subDay()->toIso8601String(),
            'grace_period_started_at' => now()->subDays(4)->toIso8601String(),
        ],
    ]);

    $this->artisan('subscriptions:suspend-expired-grace')
        ->assertExitCode(0);

    $subscription->refresh();

    expect($subscription->status)->toBe(SessionSubscriptionStatus::SUSPENDED);
    // Grace metadata should be cleaned up
    expect($subscription->metadata['grace_period_ends_at'] ?? null)->toBeNull();
});
