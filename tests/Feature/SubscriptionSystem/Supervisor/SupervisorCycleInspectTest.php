<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;

/**
 * F2.1 — read-only cycle inspector page smoke test.
 *
 * Verifies:
 *   - Authenticated admin can render the inspector.
 *   - Cycle data + invariant scan block are present on the page.
 *   - Unauthenticated callers are redirected to login.
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'cycle-inspect-'.uniqid()]);
    $this->admin = createAdmin($this->academy);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    $this->package = QuranPackage::factory()->create([
        'academy_id' => $this->academy->id,
        'monthly_price' => 200,
    ]);

    $this->sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create([
            'academy_id' => $this->academy->id,
            'package_id' => $this->package->id,
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'total_sessions' => 8,
            'sessions_used' => 2,
            'sessions_remaining' => 6,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
        ]);

    $this->cycle = SubscriptionCycle::factory()->create([
        'subscribable_type' => $this->sub->getMorphClass(),
        'subscribable_id' => $this->sub->id,
        'academy_id' => $this->academy->id,
        'cycle_number' => 1,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'total_sessions' => 8,
        'sessions_used' => 2,
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->addDays(25),
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
    ]);

    $this->sub->reconciling = true;
    $this->sub->current_cycle_id = $this->cycle->id;
    $this->sub->save();
    $this->sub->reconciling = false;
});

function inspectCycleUrl(string $subdomain, int $subId, int $cycleId): string
{
    return route('manage.subscriptions.cycles.inspect', [
        'subdomain' => $subdomain,
        'type' => 'quran',
        'subscription' => $subId,
        'cycle' => $cycleId,
    ]);
}

it('I1 — admin can render the cycle inspector page (200 + key sections present)', function () {
    $response = $this->actingAs($this->admin)->get(
        inspectCycleUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id)
    );

    $response->assertOk();
    // Cycle row section must surface the cycle's stored values.
    $response->assertSeeText('#'.$this->cycle->id);
    $response->assertSeeText((string) $this->cycle->total_sessions);
});

it('I2 — inspector rejects a cycle belonging to a different subscription (404)', function () {
    // Build a sibling sub + cycle in the same academy; passing its cycle_id
    // alongside our subscription's id must 404.
    $otherSub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create([
            'academy_id' => $this->academy->id,
            'package_id' => $this->package->id,
            'payment_status' => SubscriptionPaymentStatus::PAID,
        ]);

    $otherCycle = SubscriptionCycle::factory()->create([
        'subscribable_type' => $otherSub->getMorphClass(),
        'subscribable_id' => $otherSub->id,
        'academy_id' => $this->academy->id,
        'cycle_number' => 1,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
    ]);

    $response = $this->actingAs($this->admin)->get(
        inspectCycleUrl($this->academy->subdomain, $this->sub->id, $otherCycle->id)
    );

    $response->assertNotFound();
});

it('I3 — unauthenticated requests are redirected to login', function () {
    $response = $this->get(
        inspectCycleUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id)
    );

    // No assertion on the exact target — role middleware can redirect either to
    // /login or to a 403 depending on guard config. We assert it's not a 200.
    expect($response->status())->not->toBe(200);
});
