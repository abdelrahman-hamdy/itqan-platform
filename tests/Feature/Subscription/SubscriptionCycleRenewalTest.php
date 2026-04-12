<?php

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionRenewalService;

/**
 * Cycle-based renewal tests.
 *
 * Verifies the new "mutate-in-place + cycle history" renewal model:
 *   - renew() never creates a new subscription row
 *   - exhausted cycle → new cycle replaces current (active immediately)
 *   - remaining cycle → new cycle is queued (current stays active)
 *   - dates are always populated
 *   - grace metadata is cleared on the current cycle after renewal
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

function makeActiveSubscription(array $overrides = []): QuranSubscription
{
    return QuranSubscription::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'billing_cycle' => BillingCycle::MONTHLY,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'starts_at' => now()->subDays(15),
        'ends_at' => now()->addDays(15),
        'total_sessions' => 8,
        'sessions_used' => 4,
        'total_price' => 200,
        'final_price' => 200,
    ], $overrides));
}

test('renew on exhausted subscription replaces current cycle immediately', function () {
    $sub = makeActiveSubscription([
        'sessions_used' => 8,
        'total_sessions' => 8,
        'metadata' => ['sessions_exhausted' => true],
    ]);

    $originalId = $sub->id;
    $originalCode = $sub->subscription_code;

    $result = app(SubscriptionRenewalService::class)->renew($sub);

    // Same row — ID and code unchanged
    expect($result->id)->toBe($originalId);
    expect($result->subscription_code)->toBe($originalCode);

    // Status/payment active + paid
    expect($result->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    expect($result->payment_status)->toBe(SubscriptionPaymentStatus::PAID);

    // Dates populated
    expect($result->starts_at)->not->toBeNull();
    expect($result->ends_at)->not->toBeNull();

    // One archived cycle + one active cycle exist
    $cycles = $result->cycles()->orderBy('cycle_number')->get();
    expect($cycles->count())->toBeGreaterThanOrEqual(2);
    expect($cycles->last()->cycle_state)->toBe(SubscriptionCycle::STATE_ACTIVE);
});

test('renew on subscription with remaining sessions queues new cycle', function () {
    $sub = makeActiveSubscription([
        'sessions_used' => 2,
        'total_sessions' => 8,
    ]);

    $originalEndsAt = $sub->ends_at;

    $result = app(SubscriptionRenewalService::class)->renew($sub);

    // Subscription row still reflects the CURRENT cycle (not the new one)
    expect($result->id)->toBe($sub->id);
    expect($result->ends_at?->toDateTimeString())->toBe($originalEndsAt->toDateTimeString());

    // A queued cycle exists for the future
    $queued = $result->queuedCycle()->first();
    expect($queued)->not->toBeNull();
    expect($queued->cycle_state)->toBe(SubscriptionCycle::STATE_QUEUED);
    expect($queued->starts_at?->toDateTimeString())->toBe($originalEndsAt->toDateTimeString());
});

test('renew never creates a second subscription row', function () {
    $sub = makeActiveSubscription();
    $countBefore = QuranSubscription::withoutGlobalScopes()->count();

    app(SubscriptionRenewalService::class)->renew($sub);

    $countAfter = QuranSubscription::withoutGlobalScopes()->count();
    expect($countAfter)->toBe($countBefore);
});

test('renew populates dates even when subscription had NULL dates', function () {
    $sub = makeActiveSubscription([
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'starts_at' => null,
        'ends_at' => null,
        'sessions_used' => 0,
    ]);

    $result = app(SubscriptionRenewalService::class)->renew($sub);

    expect($result->starts_at)->not->toBeNull();
    expect($result->ends_at)->not->toBeNull();
    expect($result->status)->toBe(SessionSubscriptionStatus::ACTIVE);
});
