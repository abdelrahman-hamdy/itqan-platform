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

function dupSubscription(array $overrides = []): QuranSubscription
{
    return QuranSubscription::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'billing_cycle' => BillingCycle::MONTHLY,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addDays(20),
    ], $overrides));
}

// ========================================
// findDuplicatePending()
// ========================================

test('findDuplicatePending finds matching pending subscription by key fields', function () {
    // Create an existing pending subscription
    $existing = dupSubscription([
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
    ]);

    // Create a new (unsaved) subscription with same key fields to search from
    $new = new QuranSubscription([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
    ]);

    $duplicate = $new->findDuplicatePending();

    expect($duplicate)->not->toBeNull();
    expect($duplicate->id)->toBe($existing->id);
});

test('findDuplicatePending excludes current subscription when it has an ID', function () {
    // Create a pending subscription
    $subscription = dupSubscription([
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
    ]);

    // Search from itself — should exclude itself
    $duplicate = $subscription->findDuplicatePending();

    expect($duplicate)->toBeNull();
});

test('findDuplicatePending returns null when no duplicate exists', function () {
    // Create an ACTIVE subscription (not PENDING)
    dupSubscription([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    // Search for pending duplicates — should find none
    $new = new QuranSubscription([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
    ]);

    expect($new->findDuplicatePending())->toBeNull();
});

// ========================================
// findDuplicateActive()
// ========================================

test('findDuplicateActive finds matching active subscription', function () {
    // Create an active subscription
    $active = dupSubscription([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    // Search from a new unsaved instance
    $new = new QuranSubscription([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
    ]);

    $duplicate = $new->findDuplicateActive();

    expect($duplicate)->not->toBeNull();
    expect($duplicate->id)->toBe($active->id);
});

// ========================================
// isPendingAndExpired()
// ========================================

test('isPendingAndExpired returns true for old pending subscriptions', function () {
    // Create a pending subscription created 3 days ago (> 48h default)
    $subscription = dupSubscription([
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
    ]);

    // Manually set created_at to 3 days ago (use DB query since Eloquent protects timestamps)
    \Illuminate\Support\Facades\DB::table('quran_subscriptions')
        ->where('id', $subscription->id)
        ->update(['created_at' => now()->subDays(3)]);
    $subscription->refresh();

    expect($subscription->isPendingAndExpired())->toBeTrue();

    // A freshly created pending subscription should NOT be expired
    $fresh = dupSubscription([
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'quran_teacher_id' => createQuranTeacher(test()->academy)->id,
    ]);

    expect($fresh->isPendingAndExpired())->toBeFalse();
});
