<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranPackage;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;

/**
 * F2.3 — per-row consumption ops (reverse / promote / record).
 *
 * Every route delegates to {@see \App\Services\Subscription\SubscriptionConsumption}
 * which already wraps in lock + audit + reconciler. The tests assert the
 * controller wiring (auth + ownership guards + payload validation +
 * post-reconcile state).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'cycle-cons-'.uniqid()]);
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
            'sessions_used' => 1,
            'sessions_remaining' => 7,
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
        'sessions_used' => 1,
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

/**
 * Build a session anchored to (sub, cycle) + a matching consumption row.
 */
function buildSessionWithConsumption(array $consumptionAttrs = []): array
{
    $session = QuranSession::factory()->create([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'quran_subscription_id' => test()->sub->id,
        'subscription_cycle_id' => test()->cycle->id,
        'scheduled_at' => now()->subDay(),
        'status' => SessionStatus::COMPLETED,
    ]);

    $row = SessionConsumption::create(array_merge([
        'session_id' => $session->id,
        'session_type' => $session->getMorphClass(),
        'subscription_id' => test()->sub->id,
        'subscription_type' => test()->sub->getMorphClass(),
        'cycle_id' => test()->cycle->id,
        'student_user_id' => test()->student->id,
        'consumption_type' => SessionConsumption::TYPE_ATTENDED,
        'source' => SessionConsumption::SOURCE_AUTO_ATTENDANCE,
        'consumed_at' => now()->subDay(),
    ], $consumptionAttrs));

    return ['session' => $session, 'row' => $row];
}

function reverseConsumptionUrl(string $subdomain, int $subId, int $cycleId, int $consId): string
{
    return route('manage.subscriptions.cycles.consumption.reverse', [
        'subdomain' => $subdomain, 'type' => 'quran',
        'subscription' => $subId, 'cycle' => $cycleId, 'consumption' => $consId,
    ]);
}

function promoteConsumptionUrl(string $subdomain, int $subId, int $cycleId, int $consId): string
{
    return route('manage.subscriptions.cycles.consumption.promote', [
        'subdomain' => $subdomain, 'type' => 'quran',
        'subscription' => $subId, 'cycle' => $cycleId, 'consumption' => $consId,
    ]);
}

it('C1 — admin can reverse an active consumption row; sessions_used drops by 1 after reconcile', function () {
    ['row' => $row] = buildSessionWithConsumption();

    // Sanity: row is active.
    expect($row->reversed_at)->toBeNull();

    $response = $this->actingAs($this->admin)->post(
        reverseConsumptionUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id, $row->id)
    );
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $row->refresh();
    expect($row->reversed_at)->not->toBeNull()
        ->and($row->reversed_reason)->toBe('admin_data_fix')
        ->and($row->reversed_by_user_id)->toBe($this->admin->id);

    // INV-B3 / reconciler effect: cycle.sessions_used is recomputed from the
    // active-row count, which dropped to 0 after this reversal.
    expect((int) $this->cycle->fresh()->sessions_used)->toBe(0);
});

it('C2 — promoting an auto_attendance row flips source to admin_manual (P5)', function () {
    ['row' => $row] = buildSessionWithConsumption();

    $response = $this->actingAs($this->admin)->post(
        promoteConsumptionUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id, $row->id)
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $row->refresh();
    expect($row->source)->toBe(SessionConsumption::SOURCE_ADMIN_MANUAL)
        ->and($row->source_user_id)->toBe($this->admin->id);
});

it('C3 — consumption ops require canManageSubscriptions (non-supervisor user blocked)', function () {
    ['row' => $row] = buildSessionWithConsumption();

    // Build a student account in the same academy — they don't pass canManageSubscriptions.
    $randomStudent = createStudent($this->academy);

    $response = $this->actingAs($randomStudent)->post(
        reverseConsumptionUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id, $row->id)
    );

    // Either 403 from the manage middleware OR a redirect away — never executes the mutation.
    expect($response->status())->not->toBe(200);
    $row->refresh();
    expect($row->reversed_at)->toBeNull();
});

it('C4 — consumption from a foreign cycle returns 404 (id-rewrite defense)', function () {
    // Build a second subscription + cycle, with its own consumption row.
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

    $foreignRow = SessionConsumption::create([
        'session_id' => 999_999, // non-existent session id is fine — we only need the row.
        'session_type' => $this->sub->getMorphClass(),
        'subscription_id' => $otherSub->id,
        'subscription_type' => $otherSub->getMorphClass(),
        'cycle_id' => $otherCycle->id,
        'student_user_id' => $this->student->id,
        'consumption_type' => SessionConsumption::TYPE_ATTENDED,
        'source' => SessionConsumption::SOURCE_AUTO_ATTENDANCE,
        'consumed_at' => now(),
    ]);

    // Try to reverse $foreignRow via THIS subscription's route — must 404.
    $response = $this->actingAs($this->admin)->post(
        reverseConsumptionUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id, $foreignRow->id)
    );

    $response->assertNotFound();
});
