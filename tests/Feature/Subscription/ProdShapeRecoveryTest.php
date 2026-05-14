<?php

use App\Services\Subscription\SubscriptionInvariantChecker;
use App\Services\Subscription\SubscriptionReconciler;
use Tests\Concerns\LoadsSubscriptionFixtures;

uses(LoadsSubscriptionFixtures::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Asserts that every prod-shape fixture under tests/Fixtures/Subscription/
 * surfaces the documented invariant violations through SubscriptionInvariantChecker.
 *
 * For shapes where the v2 contract publishes a deterministic recovery
 * (reconciler::sync after the underlying cycle is repaired), the test also
 * confirms the post-recovery checker returns an empty violation list.
 *
 * Shapes flagged `recovery_action: null` in their fixture are NOT auto-recoverable;
 * we assert the violations stay surfaced and the operator triages them by hand
 * via the remediation runbook.
 */
dataset('prodShapeFixtures', [
    'lie_state' => 'lie_state_subscription',
    'hybrid_unpaid' => 'hybrid_unpaid_cycle',
    'paused_extended' => 'paused_extended_bug',
    'package_change_old_duration' => 'package_change_old_duration',
    'missing_dates' => 'missing_dates',
    'sharouq_pricing' => 'sharouq_pricing_mismatch',
]);

it('surfaces expected invariant violations for each prod-shape fixture', function (string $fixtureName) {
    $loaded = $this->loadSubscriptionFixture($fixtureName);
    $sub = $loaded['subscription'];

    $violations = app(SubscriptionInvariantChecker::class)->check($sub);
    $surfacedCodes = collect($violations)->pluck('code')->unique()->values()->all();

    foreach ($loaded['expected_violations'] as $expected) {
        // Pest's toContain() is variadic — a second argument is treated as
        // another required value, not a message. Use a plain PHPUnit-style
        // assertion so the fixture name shows up if the violation is missing.
        \PHPUnit\Framework\Assert::assertContains(
            $expected,
            $surfacedCodes,
            "Fixture {$fixtureName} expected violation {$expected} but checker returned: ".json_encode($surfacedCodes),
        );
    }
})->with('prodShapeFixtures');

it('lie-state subscription self-heals once the cycle payment is corrected', function () {
    $loaded = $this->loadSubscriptionFixture('lie_state_subscription');
    $sub = $loaded['subscription'];
    $cycle = $loaded['cycle'];

    expect(app(SubscriptionInvariantChecker::class)->check($sub))
        ->not->toBeEmpty();

    // Operator-driven repair: webhook arrives or supervisor confirms cash →
    // the cycle's payment_status flips PAID → reconciler mirrors to the sub.
    $cycle->payment_status = 'paid';
    $cycle->save();
    app(SubscriptionReconciler::class)->sync($sub->fresh());

    $violations = app(SubscriptionInvariantChecker::class)->check($sub->fresh());
    $errorViolations = collect($violations)
        ->filter(fn ($v) => ($v['severity'] ?? 'error') === 'error')
        ->values();

    expect($errorViolations)->toBeEmpty(
        'Lie-state sub should be invariant-clean after the cycle is repaired + reconciler runs');
});

it('missing-dates subscription self-heals once cycle dates are populated and reconciler runs', function () {
    $loaded = $this->loadSubscriptionFixture('missing_dates');
    $sub = $loaded['subscription'];
    $cycle = $loaded['cycle'];

    $beforeViolations = app(SubscriptionInvariantChecker::class)->check($sub);
    expect(collect($beforeViolations)->pluck('code'))->toContain('INV-A6');

    $cycle->starts_at = now()->subDays(3);
    $cycle->ends_at = now()->addDays(27);
    $cycle->save();
    app(SubscriptionReconciler::class)->sync($sub->fresh());

    $afterViolations = collect(app(SubscriptionInvariantChecker::class)->check($sub->fresh()))
        ->filter(fn ($v) => ($v['severity'] ?? 'error') === 'error')
        ->pluck('code');

    expect($afterViolations)->not->toContain('INV-A6');
});

it('sharouq-shape pricing mismatch is NOT auto-recoverable and stays surfaced', function () {
    $loaded = $this->loadSubscriptionFixture('sharouq_pricing_mismatch');
    $sub = $loaded['subscription'];

    // Without operator classification (sale_price vs manual_override + reason + actor)
    // the cycle stays invariant-violating — that's the point. Phase D remediation
    // runbook directs the operator to run SubscriptionPricing::applyOverride() with
    // an explicit reason; only then does INV-D2 clear.
    $codes = collect(app(SubscriptionInvariantChecker::class)->check($sub))->pluck('code');
    expect($codes)->toContain('INV-D2');

    // Reconciler alone does NOT clear it — pricing is cycle-owned, not derived.
    // Per the contract sync() throws on ANY post-mirror violation; the cycle's
    // pricing violation is outside the reconciler's purview but still surfaces.
    expect(fn () => app(SubscriptionReconciler::class)->sync($sub->fresh()))
        ->toThrow(\App\Exceptions\Subscription\SubscriptionInvariantViolation::class);

    // Confirm the violation is still visible on a fresh read (checker is read-only,
    // so the rolled-back sync attempt didn't change the cycle's state).
    $codesAfter = collect(app(SubscriptionInvariantChecker::class)->check($sub->fresh()))->pluck('code');
    expect($codesAfter)->toContain('INV-D2');
});
