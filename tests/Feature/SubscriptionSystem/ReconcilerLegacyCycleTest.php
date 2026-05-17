<?php

declare(strict_types=1);

use App\Models\QuranSubscription;
use App\Services\Subscription\SubscriptionReconciler;
use Carbon\Carbon;

/**
 * E1 regression — the SubscriptionReconciler must NOT zero out
 * `cycle.sessions_used` for cycles created before the v2 flip cutoff that
 * still have `v2_consumption_complete = false`.
 *
 * Pre-v2 attendance writes only ever updated `cycle.sessions_used` directly,
 * never wrote `session_consumption` rows. If the reconciler's INV-B3
 * recount fires for those cycles it overwrites the legitimate aggregate
 * with 0 — corrupting the legacy population on the next webhook tick.
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'recon-legacy-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    // Pretend v2 was flipped on at this moment for the rest of the test.
    config(['subscriptions.v2_flip_cutoff' => Carbon::parse('2026-05-14 22:36:00')->toIso8601String()]);
});

afterEach(function () {
    config(['subscriptions.v2_flip_cutoff' => null]);
});

it('does NOT zero sessions_used on a pre-v2 cycle with no consumption rows', function () {
    $sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create([
            'sessions_used' => 0,
            'sessions_remaining' => 8,
            'total_sessions' => 8,
        ]);

    $cycle = $sub->ensureCurrentCycle();
    // Stamp the legacy shape: cycle predates the cutoff, has a non-zero
    // sessions_used, no consumption rows, and v2_consumption_complete=false.
    $cycle->forceFill([
        'created_at' => Carbon::parse('2026-05-10 09:00:00'),
        'sessions_used' => 2,
        'total_sessions' => 8,
        'v2_consumption_complete' => false,
    ])->save();

    app(SubscriptionReconciler::class)->syncWithoutInvariantCheck($sub->fresh());

    expect($cycle->fresh()->sessions_used)->toBe(2);
});

it('DOES recount sessions_used on a post-v2 cycle even with no consumption rows', function () {
    $sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create([
            'sessions_used' => 0,
            'sessions_remaining' => 8,
            'total_sessions' => 8,
        ]);

    $cycle = $sub->ensureCurrentCycle();
    $cycle->forceFill([
        'created_at' => Carbon::parse('2026-05-15 10:00:00'),
        'sessions_used' => 2,
        'total_sessions' => 8,
        'v2_consumption_complete' => false,
    ])->save();

    app(SubscriptionReconciler::class)->syncWithoutInvariantCheck($sub->fresh());

    // Post-cutoff cycle is treated as v2-canonical: the empty
    // session_consumption table is the source of truth, so 2 → 0.
    expect($cycle->fresh()->sessions_used)->toBe(0);
});

it('recounts sessions_used on a pre-v2 cycle once v2_consumption_complete is true', function () {
    $sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create([
            'sessions_used' => 0,
            'sessions_remaining' => 8,
            'total_sessions' => 8,
        ]);

    $cycle = $sub->ensureCurrentCycle();
    $cycle->forceFill([
        'created_at' => Carbon::parse('2026-05-10 09:00:00'),
        'sessions_used' => 5,
        'total_sessions' => 8,
        'v2_consumption_complete' => true, // backfill has flipped this true
    ])->save();

    app(SubscriptionReconciler::class)->syncWithoutInvariantCheck($sub->fresh());

    // Backfill is complete → consumption table is canonical even for a
    // pre-flip cycle. Empty consumption table → 0.
    expect($cycle->fresh()->sessions_used)->toBe(0);
});
