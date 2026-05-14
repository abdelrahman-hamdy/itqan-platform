<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranPackage;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;

/**
 * F2.2 + F2.4 — end-to-end cycle editor route.
 *
 *   - Safe-field edits commit cleanly (grace_period_ends_at, archived_at, metadata).
 *   - Coordinated-field edits with conflicts block and flash an error payload.
 *   - Forbidden-field POST payloads are silently stripped by the FormRequest.
 *   - Queued sibling's starts_at is re-anchored when active.ends_at moves (INV-A5).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'cyc-edit-'.uniqid()]);
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

function editCycleUrl(string $subdomain, int $subId, int $cycleId): string
{
    return route('manage.subscriptions.cycles.edit', [
        'subdomain' => $subdomain, 'type' => 'quran',
        'subscription' => $subId, 'cycle' => $cycleId,
    ]);
}

it('E1 — admin can edit grace_period_ends_at (safe field, no conflicts)', function () {
    $newGrace = now()->addDays(30)->startOfMinute();

    $response = $this->actingAs($this->admin)->post(
        editCycleUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id),
        ['grace_period_ends_at' => $newGrace->format('Y-m-d\TH:i')]
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $fresh = $this->cycle->fresh();
    expect($fresh->grace_period_ends_at)->not->toBeNull();
});

it('E2 — block-on-conflict: dropping total_sessions below committed work returns to inspector with banner', function () {
    // Anchor 5 future scheduled sessions to this cycle, then try to drop total to 2.
    for ($i = 1; $i <= 5; $i++) {
        QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $this->sub->id,
            'subscription_cycle_id' => $this->cycle->id,
            'scheduled_at' => now()->addDays($i + 2),
            'status' => SessionStatus::SCHEDULED,
        ]);
    }

    $response = $this->actingAs($this->admin)->post(
        editCycleUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id),
        ['total_sessions' => 2]
    );

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $response->assertSessionHas('cycle_edit_conflicts');

    // No write happened.
    expect((int) $this->cycle->fresh()->total_sessions)->toBe(8);
});

it('E3 — forbidden fields (sessions_used) are silently stripped by the form request', function () {
    // Try to set sessions_used directly. The FormRequest's rules() doesn't
    // list it, so validated() returns []. The validator + lifecycle never
    // see it.
    $response = $this->actingAs($this->admin)->post(
        editCycleUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id),
        [
            'sessions_used' => 99,    // forbidden — dropped
            'grace_period_ends_at' => now()->addDays(5)->format('Y-m-d\TH:i'),
        ]
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect((int) $this->cycle->fresh()->sessions_used)->toBe(0);
});

it('E4 — moving ends_at re-anchors the queued sibling.starts_at (INV-A5)', function () {
    // Add a queued sibling pegged to current ends_at.
    $queued = SubscriptionCycle::factory()->create([
        'subscribable_type' => $this->sub->getMorphClass(),
        'subscribable_id' => $this->sub->id,
        'academy_id' => $this->academy->id,
        'cycle_number' => 2,
        'cycle_state' => SubscriptionCycle::STATE_QUEUED,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'starts_at' => $this->cycle->ends_at,
        'ends_at' => $this->cycle->ends_at?->copy()->addDays(30),
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
    ]);

    $newEndsAt = $this->cycle->ends_at?->copy()->addDays(5);

    $response = $this->actingAs($this->admin)->post(
        editCycleUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id),
        ['ends_at' => $newEndsAt->format('Y-m-d\TH:i')]
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $freshQueued = $queued->fresh();
    expect($freshQueued->starts_at->equalTo($this->cycle->fresh()->ends_at))->toBeTrue();
});
