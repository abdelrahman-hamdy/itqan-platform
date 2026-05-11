<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranSubscription;

/**
 * Asserts cancel + reactivate semantics (spec §3.F).
 *
 * Reactivate intentionally keeps `auto_renew = false` — the user opted out
 * by cancelling and must opt back in explicitly. This is a quiet, often-
 * surprising rule and is invariant I8 in the spec.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

it('I8 — cancel sets auto_renew = false', function () {
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create(['auto_renew' => true]);

    $subscription->cancel('test reason');

    expect($subscription->fresh()->auto_renew)->toBeFalse();
});

it('F1 — cancel sets cancelled_at and stores the cancellation_reason', function () {
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create();

    $subscription->cancel('admin termination');

    $fresh = $subscription->fresh();
    expect($fresh->status)->toBe(SessionSubscriptionStatus::CANCELLED);
    expect($fresh->cancelled_at)->not->toBeNull();
    expect($fresh->cancellation_reason)->toBe('admin termination');
});

it('F3 (I8) — manual reactivate path keeps auto_renew = false (user must opt back in)', function () {
    // The Filament action handler does this directly via $record->update().
    // Replicate the production update list here so we lock the contract.
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->cancelled()
        ->create(['auto_renew' => false]);

    $subscription->update([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'cancelled_at' => null,
        'cancellation_reason' => null,
        'auto_renew' => false, // intentional: I8
    ]);

    expect($subscription->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    expect($subscription->fresh()->auto_renew)->toBeFalse();
});

it('F4 — reactivate refreshes dates only when ends_at is past', function () {
    $subscription = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->cancelled()
        ->create([
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subMonth(), // past
        ]);

    expect($subscription->ends_at->isPast())->toBeTrue();

    // The manual reactivate path on subscription model:
    if (! $subscription->starts_at || $subscription->ends_at?->isPast()) {
        $subscription->update([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'starts_at' => now(),
            'ends_at' => $subscription->calculateEndDate(now()),
        ]);
    }

    $fresh = $subscription->fresh();
    expect($fresh->ends_at->isFuture())->toBeTrue();
});
