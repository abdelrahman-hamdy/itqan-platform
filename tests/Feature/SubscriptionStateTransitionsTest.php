<?php

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranSubscription;

beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

function makeSubscription(array $overrides = []): QuranSubscription
{
    return QuranSubscription::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'billing_cycle' => BillingCycle::MONTHLY,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
    ], $overrides));
}

// ========================================
// activate()
// ========================================

test('activate sets status to ACTIVE, payment_status to PAID, and calculates end date', function () {
    $subscription = makeSubscription([
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'starts_at' => now(),
        'ends_at' => null,
    ]);

    $subscription->activate();
    $subscription->refresh();

    expect($subscription->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    expect($subscription->payment_status)->toBe(SubscriptionPaymentStatus::PAID);
    expect($subscription->ends_at)->not->toBeNull();
    expect($subscription->last_payment_date)->not->toBeNull();
});

// ========================================
// cancel()
// ========================================

test('cancel from ACTIVE sets CANCELLED with timestamp and reason', function () {
    $subscription = makeSubscription([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    $subscription->cancel('Test cancellation reason');
    $subscription->refresh();

    expect($subscription->status)->toBe(SessionSubscriptionStatus::CANCELLED);
    expect($subscription->cancelled_at)->not->toBeNull();
    expect($subscription->cancellation_reason)->toBe('Test cancellation reason');
    expect($subscription->auto_renew)->toBeFalse();
});

test('cancel from CANCELLED throws exception', function () {
    $subscription = makeSubscription([
        'status' => SessionSubscriptionStatus::CANCELLED,
    ]);

    $subscription->cancel();
})->throws(Exception::class, 'Cannot cancel subscription in current state');

// ========================================
// pause()
// ========================================

test('pause from ACTIVE sets PAUSED with timestamp', function () {
    $subscription = makeSubscription([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    $subscription->pause('Student requested pause');
    $subscription->refresh();

    expect($subscription->status)->toBe(SessionSubscriptionStatus::PAUSED);
    expect($subscription->paused_at)->not->toBeNull();
    expect($subscription->pause_reason)->toBe('Student requested pause');
});

test('pause from PAUSED throws exception', function () {
    $subscription = makeSubscription([
        'status' => SessionSubscriptionStatus::PAUSED,
    ]);

    $subscription->pause();
})->throws(Exception::class, 'Cannot pause subscription in current state');

// ========================================
// resume()
// ========================================

test('resume from PAUSED sets ACTIVE and clears pause data', function () {
    $subscription = makeSubscription([
        'status' => SessionSubscriptionStatus::PAUSED,
        'paused_at' => now()->subDays(3),
        'pause_reason' => 'Student requested pause',
    ]);

    $subscription->resume();
    $subscription->refresh();

    expect($subscription->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    expect($subscription->paused_at)->toBeNull();
    expect($subscription->pause_reason)->toBeNull();
});

test('resume from ACTIVE throws exception', function () {
    $subscription = makeSubscription([
        'status' => SessionSubscriptionStatus::ACTIVE,
    ]);

    $subscription->resume();
})->throws(Exception::class, 'Cannot resume subscription in current state');

// ========================================
// enableAutoRenewal()
// ========================================

test('enableAutoRenewal throws for LIFETIME billing cycle', function () {
    // Create with valid billing cycle first (DB may not support 'lifetime' enum value)
    $subscription = makeSubscription([
        'billing_cycle' => BillingCycle::MONTHLY,
        'auto_renew' => false,
    ]);

    // Set billing_cycle to LIFETIME in memory to test the guard
    $subscription->billing_cycle = BillingCycle::LIFETIME;

    $subscription->enableAutoRenewal();
})->throws(Exception::class, 'This billing cycle does not support auto-renewal');

// ========================================
// needsRenewal()
// ========================================

test('needsRenewal returns true when ends_at is past and status canRenew', function () {
    // Use different teachers to avoid "duplicate active subscription" validation
    $teacher2 = createQuranTeacher($this->academy);
    $teacher3 = createQuranTeacher($this->academy);
    $teacher4 = createQuranTeacher($this->academy);
    $teacher5 = createQuranTeacher($this->academy);

    // ACTIVE with past ends_at
    $active = makeSubscription([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'ends_at' => now()->subDay(),
    ]);
    expect($active->needsRenewal())->toBeTrue();

    // PAUSED with past ends_at
    $paused = makeSubscription([
        'status' => SessionSubscriptionStatus::PAUSED,
        'ends_at' => now()->subDay(),
        'quran_teacher_id' => $teacher2->id,
    ]);
    expect($paused->needsRenewal())->toBeTrue();

    // SUSPENDED with past ends_at
    $suspended = makeSubscription([
        'status' => SessionSubscriptionStatus::SUSPENDED,
        'ends_at' => now()->subDay(),
        'quran_teacher_id' => $teacher3->id,
    ]);
    expect($suspended->needsRenewal())->toBeTrue();

    // CANCELLED should NOT need renewal
    $cancelled = makeSubscription([
        'status' => SessionSubscriptionStatus::CANCELLED,
        'ends_at' => now()->subDay(),
        'quran_teacher_id' => $teacher4->id,
    ]);
    expect($cancelled->needsRenewal())->toBeFalse();

    // ACTIVE with future ends_at should NOT need renewal
    $future = makeSubscription([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'ends_at' => now()->addDays(10),
        'quran_teacher_id' => $teacher5->id,
    ]);
    expect($future->needsRenewal())->toBeFalse();
});
