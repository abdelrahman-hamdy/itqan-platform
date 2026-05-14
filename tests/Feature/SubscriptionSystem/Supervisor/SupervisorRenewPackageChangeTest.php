<?php

declare(strict_types=1);

use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;

/**
 * Feature 1 — supervisor renew accepts an optional `package_id` to switch
 * the package on the new (queued or replacing) cycle. INV-H2: admin path
 * bypasses the "previous package must be currently active" student gate,
 * but the picked package itself must be an active package on the same
 * academy (no cross-tenant data bleed, no retired-package writes).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'renew-pkg-'.uniqid()]);
    $this->admin = createAdmin($this->academy);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    // Original package (subscription was created with this one).
    $this->originalPackage = QuranPackage::factory()->create([
        'academy_id' => $this->academy->id,
        'name' => 'Original 30-min',
        'sessions_per_month' => 8,
        'session_duration_minutes' => 30,
        'monthly_price' => 200,
    ]);

    // Different active package available for switching.
    $this->newPackage = QuranPackage::factory()->create([
        'academy_id' => $this->academy->id,
        'name' => 'New 45-min',
        'sessions_per_month' => 12,
        'session_duration_minutes' => 45,
        'monthly_price' => 400,
    ]);
});

function renewWithPackageUrl(string $subdomain, int $id): string
{
    return route('manage.subscriptions.renew', [
        'subdomain' => $subdomain,
        'type' => 'quran',
        'subscription' => $id,
    ]);
}

function buildExhaustedSubOnPackage(QuranPackage $package): QuranSubscription
{
    $sub = QuranSubscription::factory()
        ->forStudent(test()->student)
        ->forTeacher(test()->teacher)
        ->active()
        ->create([
            'academy_id' => test()->academy->id,
            'package_id' => $package->id,
            'sessions_used' => 8,
            'sessions_remaining' => 0,
            'total_sessions' => 8,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'starts_at' => now()->subDays(30),
            'ends_at' => now()->subDay(),
        ]);

    // Pre-materialize the current cycle and link it back via current_cycle_id
    // so renew()'s ensureCurrentCycle() is a no-op. Without the linkage, the
    // renewal path materializes a fresh cycle_number=1 row alongside our
    // factory-created one and the unique (thread, cycle_number) constraint fires.
    $cycle = SubscriptionCycle::factory()->create([
        'subscribable_type' => $sub->getMorphClass(),
        'subscribable_id' => $sub->id,
        'academy_id' => test()->academy->id,
        'cycle_number' => 1,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'sessions_used' => 8,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(30),
        'ends_at' => now()->subDay(),
        'package_id' => $package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
    ]);

    $sub->reconciling = true;
    $sub->current_cycle_id = $cycle->id;
    $sub->save();
    $sub->reconciling = false;

    return $sub->fresh();
}

describe('Supervisor renew + optional package_id', function () {
    it('A — renew without package_id keeps the old package on the new cycle', function () {
        $sub = buildExhaustedSubOnPackage($this->originalPackage);

        $response = $this->actingAs($this->admin)->post(
            renewWithPackageUrl($this->academy->subdomain, $sub->id),
            ['billing_cycle' => 'monthly', 'payment_mode' => 'paid']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $sub->fresh();
        // The new cycle (current cycle after replace-now) keeps the original package.
        expect($fresh->package_id)->toBe($this->originalPackage->id);

        $newCycle = $fresh->currentCycle;
        expect($newCycle)->not->toBeNull()
            ->and($newCycle->package_id)->toBe($this->originalPackage->id);
    });

    it('B — renew with package_id = same package behaves like no change', function () {
        $sub = buildExhaustedSubOnPackage($this->originalPackage);

        $response = $this->actingAs($this->admin)->post(
            renewWithPackageUrl($this->academy->subdomain, $sub->id),
            [
                'billing_cycle' => 'monthly',
                'payment_mode' => 'paid',
                'package_id' => $this->originalPackage->id,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $sub->fresh();
        expect($fresh->package_id)->toBe($this->originalPackage->id)
            ->and($fresh->currentCycle->package_id)->toBe($this->originalPackage->id);
    });

    it('C — renew with package_id = different package switches the new cycle to the new package', function () {
        $sub = buildExhaustedSubOnPackage($this->originalPackage);

        $response = $this->actingAs($this->admin)->post(
            renewWithPackageUrl($this->academy->subdomain, $sub->id),
            [
                'billing_cycle' => 'monthly',
                'payment_mode' => 'paid',
                'package_id' => $this->newPackage->id,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $sub->fresh();
        // Subscription columns mirror the new cycle (INV-A1).
        expect($fresh->package_id)->toBe($this->newPackage->id);

        $newCycle = $fresh->currentCycle;
        expect($newCycle)->not->toBeNull()
            ->and($newCycle->package_id)->toBe($this->newPackage->id)
            // INV-D2/D4: pricing_source must stay 'package' and the snapshot
            // reflects the picked package's monthly price.
            ->and($newCycle->pricing_source)->toBe('package')
            ->and((float) $newCycle->final_price)->toBe(400.0);
    });

    it('D — renew with a package from another academy is rejected (tenant isolation)', function () {
        // Build another academy with its own package — picking THIS package
        // on the original sub must not be allowed.
        $otherAcademy = createAcademy(['subdomain' => 'other-pkg-'.uniqid()]);
        $foreignPackage = QuranPackage::factory()->create([
            'academy_id' => $otherAcademy->id,
            'monthly_price' => 999,
        ]);

        $sub = buildExhaustedSubOnPackage($this->originalPackage);

        $response = $this->actingAs($this->admin)->post(
            renewWithPackageUrl($this->academy->subdomain, $sub->id),
            [
                'billing_cycle' => 'monthly',
                'payment_mode' => 'paid',
                'package_id' => $foreignPackage->id,
            ]
        );

        // Controller flashes 'error' (invalid_package) and does not call renew().
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $fresh = $sub->fresh();
        // No package switch happened.
        expect($fresh->package_id)->toBe($this->originalPackage->id);
    });
});
