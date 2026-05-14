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
 * F2.5 — supervisor session delete + cancel actions.
 *
 * Hard delete is allowed only when the session is CLEAN (no attendance,
 * reports, consumption, or earnings). Otherwise the route returns to the
 * inspector with an explicit error. Cancel never deletes — it flips the
 * row to CANCELLED.
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'sess-del-'.uniqid()]);
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
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(28),
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
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->addDays(28),
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
    ]);

    $this->sub->reconciling = true;
    $this->sub->current_cycle_id = $this->cycle->id;
    $this->sub->save();
    $this->sub->reconciling = false;
});

function buildCleanFutureSession(): QuranSession
{
    return QuranSession::factory()->create([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'quran_subscription_id' => test()->sub->id,
        'subscription_cycle_id' => test()->cycle->id,
        'scheduled_at' => now()->addDays(5),
        'status' => SessionStatus::SCHEDULED,
    ]);
}

function deleteSessionUrl(string $subdomain, int $subId, int $cycleId, int $sessionId): string
{
    return route('manage.subscriptions.cycles.sessions.destroy', [
        'subdomain' => $subdomain, 'type' => 'quran',
        'subscription' => $subId, 'cycle' => $cycleId, 'session' => $sessionId,
    ]);
}

function cancelSessionUrl(string $subdomain, int $subId, int $cycleId, int $sessionId): string
{
    return route('manage.subscriptions.cycles.sessions.cancel', [
        'subdomain' => $subdomain, 'type' => 'quran',
        'subscription' => $subId, 'cycle' => $cycleId, 'session' => $sessionId,
    ]);
}

it('D1 — admin can hard-delete a clean future session', function () {
    $session = buildCleanFutureSession();

    $response = $this->actingAs($this->admin)->delete(
        deleteSessionUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id, $session->id)
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(QuranSession::query()->whereKey($session->id)->exists())->toBeFalse();
});

it('D2 — delete blocked when the session has a consumption row', function () {
    $session = buildCleanFutureSession();

    // Add a consumption row to make the session "dirty".
    SessionConsumption::create([
        'session_id' => $session->id,
        'session_type' => $session->getMorphClass(),
        'subscription_id' => $this->sub->id,
        'subscription_type' => $this->sub->getMorphClass(),
        'cycle_id' => $this->cycle->id,
        'student_user_id' => $this->student->id,
        'consumption_type' => SessionConsumption::TYPE_ATTENDED,
        'source' => SessionConsumption::SOURCE_AUTO_ATTENDANCE,
        'consumed_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->delete(
        deleteSessionUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id, $session->id)
    );

    $response->assertRedirect();
    $response->assertSessionHas('error');

    // Session row still exists.
    expect(QuranSession::query()->whereKey($session->id)->exists())->toBeTrue();
});

it('D3 — cancel always succeeds on a scheduled session (no clean-state guard)', function () {
    $session = buildCleanFutureSession();

    $response = $this->actingAs($this->admin)->post(
        cancelSessionUrl($this->academy->subdomain, $this->sub->id, $this->cycle->id, $session->id)
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($session->fresh()->status)->toBe(SessionStatus::CANCELLED);
});

it('D4 — delete returns 404 when session belongs to a different cycle', function () {
    $session = buildCleanFutureSession();

    // Build a second cycle on the same sub; pass its id in the route — must 404.
    $otherCycle = SubscriptionCycle::factory()->create([
        'subscribable_type' => $this->sub->getMorphClass(),
        'subscribable_id' => $this->sub->id,
        'academy_id' => $this->academy->id,
        'cycle_number' => 2,
        'cycle_state' => SubscriptionCycle::STATE_QUEUED,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
        'starts_at' => now()->addDays(28),
        'ends_at' => now()->addDays(56),
    ]);

    $response = $this->actingAs($this->admin)->delete(
        deleteSessionUrl($this->academy->subdomain, $this->sub->id, $otherCycle->id, $session->id)
    );

    $response->assertNotFound();
});
