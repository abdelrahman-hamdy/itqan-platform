<?php

declare(strict_types=1);

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Exceptions\Subscription\InvalidPricingOverride;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\PricingResolver;
use App\Services\Subscription\SubscriptionPricing;

/**
 * Pricing trust tests for SubscriptionPricing.
 *
 * Asserts §7 of `docs/subscription-invariants.md`:
 *   - INV-D1: every priceCycle write stamps a known pricing_source.
 *   - INV-D2: package source pulls from PricingResolver; override sources
 *             require reason + actor.
 *   - INV-D3: refuses negative final_price.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
    $this->actor = createAdmin($this->academy);

    $this->package = QuranPackage::factory()->create([
        'academy_id' => $this->academy->id,
        'monthly_price' => 200,
        'quarterly_price' => 540,
        'yearly_price' => 2000,
        'session_duration_minutes' => 30,
    ]);

    $this->sub = QuranSubscription::factory()->make([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'total_sessions' => 8,
        'sessions_used' => 0,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $this->sub->reconciling = true;
    $this->sub->save();
    $this->sub->reconciling = false;

    $this->cycle = SubscriptionCycle::factory()->create([
        'subscribable_type' => $this->sub->getMorphClass(),
        'subscribable_id' => $this->sub->id,
        'academy_id' => $this->academy->id,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
        'billing_cycle' => 'monthly',
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
    ]);
    $this->sub->reconciling = true;
    $this->sub->current_cycle_id = $this->cycle->id;
    $this->sub->save();
    $this->sub->reconciling = false;
    $this->sub->refresh();

    $this->pricing = app(SubscriptionPricing::class);
});

test('priceCycle with package source computes from PricingResolver', function () {
    $expected = PricingResolver::resolvePriceFromPackage($this->package, BillingCycle::MONTHLY);

    $updated = $this->pricing->priceCycle(
        $this->cycle,
        $this->package,
        BillingCycle::MONTHLY,
        0.0,
        SubscriptionPricing::SOURCE_PACKAGE,
        $this->actor,
    );

    expect((float) $updated->final_price)->toBe($expected)
        ->and($updated->pricing_source)->toBe('package')
        ->and($updated->pricing_override_reason)->toBeNull()
        ->and($updated->pricing_override_actor_id)->toBeNull();
});

test('INV-D2 — manual_override requires non-empty reason', function () {
    expect(fn () => $this->pricing->priceCycle(
        $this->cycle,
        $this->package,
        BillingCycle::MONTHLY,
        0.0,
        SubscriptionPricing::SOURCE_MANUAL_OVERRIDE,
        $this->actor,
        reason: '', // empty
        manualPrice: 150.0,
    ))->toThrow(InvalidPricingOverride::class);
});

test('INV-D2 — manual_override requires an explicit manualPrice', function () {
    expect(fn () => $this->pricing->priceCycle(
        $this->cycle,
        $this->package,
        BillingCycle::MONTHLY,
        0.0,
        SubscriptionPricing::SOURCE_MANUAL_OVERRIDE,
        $this->actor,
        reason: 'goodwill discount',
        manualPrice: null,
    ))->toThrow(InvalidPricingOverride::class);
});

test('INV-D2 — successful manual_override populates override reason + actor', function () {
    $updated = $this->pricing->priceCycle(
        $this->cycle,
        $this->package,
        BillingCycle::MONTHLY,
        0.0,
        SubscriptionPricing::SOURCE_MANUAL_OVERRIDE,
        $this->actor,
        reason: 'goodwill discount',
        manualPrice: 150.0,
    );

    expect((float) $updated->final_price)->toBe(150.0)
        ->and($updated->pricing_source)->toBe('manual_override')
        ->and($updated->pricing_override_reason)->toBe('goodwill discount')
        ->and((int) $updated->pricing_override_actor_id)->toBe($this->actor->id);
});

test('INV-D3 — negative final_price (manual override) is rejected', function () {
    expect(fn () => $this->pricing->priceCycle(
        $this->cycle,
        $this->package,
        BillingCycle::MONTHLY,
        0.0,
        SubscriptionPricing::SOURCE_MANUAL_OVERRIDE,
        $this->actor,
        reason: 'should fail',
        manualPrice: -50.0,
    ))->toThrow(InvalidPricingOverride::class);
});

test('INV-D2 — cycle written with package source has final_price == PricingResolver expected', function () {
    $expected = PricingResolver::resolvePriceFromPackage($this->package, BillingCycle::MONTHLY) - 0.0;

    $updated = $this->pricing->priceCycle(
        $this->cycle,
        $this->package,
        BillingCycle::MONTHLY,
        0.0,
        SubscriptionPricing::SOURCE_PACKAGE,
        $this->actor,
    );

    expect((float) $updated->final_price)->toBe((float) $expected);
});

test('priceCycle with sale_price source persists override reason + actor', function () {
    $updated = $this->pricing->priceCycle(
        $this->cycle,
        $this->package,
        BillingCycle::MONTHLY,
        0.0,
        SubscriptionPricing::SOURCE_SALE_PRICE,
        $this->actor,
        reason: 'launch promo',
        manualPrice: 100.0,
    );

    expect($updated->pricing_source)->toBe('sale_price')
        ->and($updated->pricing_override_reason)->toBe('launch promo')
        ->and((int) $updated->pricing_override_actor_id)->toBe($this->actor->id);
});

test('recomputeNextCyclePrice returns package price minus discount, never negative', function () {
    $this->sub->discount_amount = 50;

    $expected = max(0.0, PricingResolver::resolvePriceFromPackage($this->package, BillingCycle::MONTHLY) - 50);
    $actual = $this->pricing->recomputeNextCyclePrice($this->sub, $this->package, BillingCycle::MONTHLY);

    expect($actual)->toBe($expected);
});
