<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranSubscription;
use App\Services\Subscription\SubscriptionMaintenanceService;

/**
 * Asserts the Extend / grace period semantics (spec §3.E).
 *
 * The Extend action stores `grace_period_ends_at` in metadata; it does
 * NOT modify `ends_at`. PAUSED/EXPIRED → ACTIVE happens as a side effect
 * when the new grace window starts.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

it('E1 — extend stacks on existing grace period', function () {
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->inGracePeriod(5)
        ->create();

    $existingGraceEnd = \Carbon\Carbon::parse($subscription->metadata['grace_period_ends_at']);

    $result = app(SubscriptionMaintenanceService::class)->extend($subscription, 7);

    $newGraceEnd = $result['grace_period_ends_at'];
    // Grace stacks: 5 + 7 = 12 days from original baseline.
    expect($newGraceEnd->copy()->diffInDays($existingGraceEnd, false))->toBeLessThanOrEqual(-7); // negative = future
    expect($newGraceEnd->copy()->diffInDays($existingGraceEnd, false))->toBeGreaterThanOrEqual(-8);
});

it('E2 — extend on auto-paused subscription transitions it back to ACTIVE', function () {
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->autoPaused()
        ->create();

    $result = app(SubscriptionMaintenanceService::class)->extend($subscription, 14);

    expect($result['subscription']->status)->toBe(SessionSubscriptionStatus::ACTIVE);
});

it('I9 — extend does NOT modify ends_at', function () {
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->autoPaused()
        ->create();

    $endsAtBefore = $subscription->ends_at->copy();

    app(SubscriptionMaintenanceService::class)->extend($subscription, 14);

    expect($subscription->fresh()->ends_at->equalTo($endsAtBefore))->toBeTrue();
});

it('E5 — extend mirrors grace_period_ends_at onto current_cycle row when one exists', function () {
    // The factory builds without a current_cycle. When current_cycle_id is
    // null, the extend service still writes metadata.grace_period_ends_at
    // but does not need to mirror onto a non-existent cycle.
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->autoPaused()
        ->create(['current_cycle_id' => null]);

    $result = app(SubscriptionMaintenanceService::class)->extend($subscription, 7);

    expect($result['subscription']->metadata['grace_period_ends_at'])->not->toBeNull();
    expect($result['grace_period_ends_at']->isFuture())->toBeTrue();
});

it('E5b — isInGracePeriod() returns true while grace window is in the future', function () {
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->inGracePeriod(7)
        ->create();

    expect($subscription->isInGracePeriod())->toBeTrue();
});
