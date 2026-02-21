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

function sessionSubscription(array $overrides = []): QuranSubscription
{
    return QuranSubscription::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'billing_cycle' => BillingCycle::MONTHLY,
        'total_sessions' => 8,
        'sessions_used' => 2,
        'sessions_remaining' => 6,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addDays(20),
    ], $overrides));
}

// ========================================
// useSession()
// ========================================

test('useSession decrements sessions_remaining and increments sessions_used', function () {
    $subscription = sessionSubscription([
        'sessions_used' => 2,
        'sessions_remaining' => 6,
    ]);

    $subscription->useSession();
    $subscription->refresh();

    expect($subscription->sessions_used)->toBe(3);
    expect($subscription->sessions_remaining)->toBe(5);
    expect($subscription->last_session_at)->not->toBeNull();
});

test('useSession auto-pauses subscription when sessions reach zero', function () {
    $subscription = sessionSubscription([
        'sessions_used' => 7,
        'sessions_remaining' => 1,
    ]);

    $subscription->useSession();
    $subscription->refresh();

    expect($subscription->sessions_remaining)->toBe(0);
    expect($subscription->sessions_used)->toBe(8);
    expect($subscription->status)->toBe(SessionSubscriptionStatus::PAUSED);
});

test('useSession throws when no sessions remaining', function () {
    $subscription = sessionSubscription([
        'sessions_used' => 8,
        'sessions_remaining' => 0,
    ]);

    $subscription->useSession();
})->throws(Exception::class);

// ========================================
// returnSession()
// ========================================

test('returnSession increments sessions_remaining and decrements sessions_used', function () {
    $subscription = sessionSubscription([
        'sessions_used' => 4,
        'sessions_remaining' => 4,
    ]);

    $subscription->returnSession();
    $subscription->refresh();

    expect($subscription->sessions_used)->toBe(3);
    expect($subscription->sessions_remaining)->toBe(5);
});

test('returnSession reactivates subscription paused due to exhaustion', function () {
    $subscription = sessionSubscription([
        'status' => SessionSubscriptionStatus::PAUSED,
        'sessions_used' => 8,
        'sessions_remaining' => 0,
        'paused_at' => now(),
        'pause_reason' => 'انتهت الجلسات المتاحة - في انتظار التجديد',
    ]);

    $subscription->returnSession();
    $subscription->refresh();

    expect($subscription->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    expect($subscription->sessions_remaining)->toBe(1);
    expect($subscription->paused_at)->toBeNull();
});

test('returnSession does NOT reactivate subscription paused for other reasons', function () {
    $subscription = sessionSubscription([
        'status' => SessionSubscriptionStatus::PAUSED,
        'sessions_used' => 4,
        'sessions_remaining' => 0,
        'paused_at' => now(),
        'pause_reason' => 'Student requested pause',
    ]);

    $subscription->returnSession();
    $subscription->refresh();

    expect($subscription->status)->toBe(SessionSubscriptionStatus::PAUSED);
    expect($subscription->sessions_remaining)->toBe(1);
});

// ========================================
// needsRenewal() with future ends_at
// ========================================

test('needsRenewal returns false for active subscription with future ends_at', function () {
    $subscription = sessionSubscription([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'ends_at' => now()->addDays(20),
    ]);

    expect($subscription->needsRenewal())->toBeFalse();
});
