<?php

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranSubscription;
use App\Services\Subscription\SubscriptionMaintenanceService;

/**
 * Tests for BaseSubscription::isSchedulable() — the single contract every
 * scheduling gate (validators, strategies, session observer) must honor.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

function makeSub(array $overrides = []): QuranSubscription
{
    return QuranSubscription::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'billing_cycle' => BillingCycle::MONTHLY,
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->addDays(25),
    ], $overrides));
}

test('active + paid subscription is schedulable', function () {
    $sub = makeSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    expect($sub->isSchedulable())->toBeTrue();
});

test('pending payment with active grace period is schedulable', function () {
    $sub = makeSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'metadata' => [
            'grace_period_ends_at' => now()->addDays(7)->toDateTimeString(),
        ],
    ]);

    expect($sub->isSchedulable())->toBeTrue();
});

test('pending payment WITHOUT grace is NOT schedulable', function () {
    $sub = makeSub([
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'metadata' => null,
    ]);

    expect($sub->isSchedulable())->toBeFalse();
});

test('pending payment with EXPIRED grace is NOT schedulable', function () {
    $sub = makeSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'metadata' => [
            'grace_period_ends_at' => now()->subDay()->toDateTimeString(),
        ],
    ]);

    expect($sub->isSchedulable())->toBeFalse();
});

test('paused subscription is NOT schedulable', function () {
    $sub = makeSub([
        'status' => SessionSubscriptionStatus::PAUSED,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    expect($sub->isSchedulable())->toBeFalse();
});

test('cancelled subscription is NOT schedulable', function () {
    $sub = makeSub([
        'status' => SessionSubscriptionStatus::CANCELLED,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    expect($sub->isSchedulable())->toBeFalse();
});

test('extend on PAUSED subscription flips to ACTIVE and makes schedulable for grace duration', function () {
    $sub = makeSub([
        'status' => SessionSubscriptionStatus::PAUSED,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
    ]);

    app(SubscriptionMaintenanceService::class)->extend($sub, 14);

    $sub->refresh();

    expect($sub->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    expect($sub->isSchedulable())->toBeTrue();
    expect($sub->isInGracePeriod())->toBeTrue();
});

test('cancelExtension clears grace and pauses if original window passed', function () {
    $sub = makeSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'ends_at' => now()->subDays(2),
        'metadata' => [
            'grace_period_ends_at' => now()->addDays(5)->toDateTimeString(),
        ],
    ]);

    app(SubscriptionMaintenanceService::class)->cancelExtension($sub);

    $sub->refresh();

    expect($sub->isInGracePeriod())->toBeFalse();
    expect($sub->status)->toBe(SessionSubscriptionStatus::PAUSED);
});
