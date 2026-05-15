<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;

/**
 * Issue #1 regression — the cycle edit form pre-fills every field, so
 * `<input>` elements POST every value back regardless of whether the admin
 * touched them. The browser also drops seconds from datetime inputs
 * (`Y-m-d\TH:i`), so a no-change submit round-trips a value 0–59 seconds
 * before the stored timestamp and triggers a spurious
 * CYCLE-EDIT-STARTS-FORWARD / CYCLE-EDIT-ENDS-BACKWARD conflict.
 *
 * The controller now diffs the submitted patch vs. the stored cycle (at
 * minute precision for date fields) and short-circuits with a success flash
 * when the diff is empty.
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'cyc-nochange-'.uniqid()]);
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
            'sessions_used' => 0,
            'sessions_remaining' => 8,
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
        'sessions_used' => 0,
        'starts_at' => now()->subDays(5)->setTime(9, 30, 42),
        'ends_at' => now()->addDays(25)->setTime(20, 15, 17),
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
    ]);

    $this->sub->reconciling = true;
    $this->sub->current_cycle_id = $this->cycle->id;
    $this->sub->save();
    $this->sub->reconciling = false;
});

function editCycleNoChangeUrl(string $subdomain, int $subId, int $cycleId): string
{
    return route('manage.subscriptions.cycles.edit', [
        'subdomain' => $subdomain, 'type' => 'quran',
        'subscription' => $subId, 'cycle' => $cycleId,
    ]);
}

it('treats a no-change submit as success without firing coordinated-field validators', function () {
    $payload = [
        'starts_at' => $this->cycle->starts_at->format('Y-m-d\TH:i'),
        'ends_at' => $this->cycle->ends_at->format('Y-m-d\TH:i'),
        'total_sessions' => $this->cycle->total_sessions,
        'grace_period_ends_at' => $this->cycle->grace_period_ends_at?->format('Y-m-d\TH:i'),
        'archived_at' => $this->cycle->archived_at?->format('Y-m-d\TH:i'),
    ];

    $response = $this->actingAs($this->admin)->post(
        editCycleNoChangeUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id),
        $payload,
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $response->assertSessionMissing('cycle_edit_conflicts');
});

it('still validates when only grace_period_ends_at is changed', function () {
    $payload = [
        'starts_at' => $this->cycle->starts_at->format('Y-m-d\TH:i'),
        'ends_at' => $this->cycle->ends_at->format('Y-m-d\TH:i'),
        'total_sessions' => $this->cycle->total_sessions,
        'grace_period_ends_at' => now()->addDays(30)->startOfMinute()->format('Y-m-d\TH:i'),
    ];

    $response = $this->actingAs($this->admin)->post(
        editCycleNoChangeUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id),
        $payload,
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $response->assertSessionMissing('cycle_edit_conflicts');

    $fresh = $this->cycle->fresh();
    expect($fresh->grace_period_ends_at)->not->toBeNull();
});
