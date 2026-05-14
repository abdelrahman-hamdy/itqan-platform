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
use App\Services\Subscription\SubscriptionConsumption;
use App\Support\Subscriptions\CycleEditValidator;

/**
 * F2.4 — CycleEditValidator pure-logic tests.
 *
 * Each test seeds a sub + cycle + the minimum dependent rows, runs the
 * validator with a candidate patch, and asserts the returned conflict
 * codes. The validator never writes — these assertions are read-only.
 */
beforeEach(function () {
    $this->validator = app(CycleEditValidator::class);

    $this->academy = createAcademy(['subdomain' => 'cyc-val-'.uniqid()]);
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

/**
 * Anchor a session to the current cycle.
 */
function anchoredSession(?\Carbon\Carbon $scheduledAt = null, $status = null): QuranSession
{
    return QuranSession::factory()->create([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'quran_subscription_id' => test()->sub->id,
        'subscription_cycle_id' => test()->cycle->id,
        'scheduled_at' => $scheduledAt ?? now()->addDays(3),
        'status' => $status ?? SessionStatus::SCHEDULED,
    ]);
}

it('V1 — empty patch produces no conflicts', function () {
    $conflicts = $this->validator->validate($this->sub, $this->cycle, []);

    expect($conflicts)->toBe([]);
});

it('V2 — editing only grace_period_ends_at is always safe', function () {
    $conflicts = $this->validator->validate($this->sub, $this->cycle, [
        'grace_period_ends_at' => now()->addDays(7),
    ]);

    expect($conflicts)->toBe([]);
});

it('V3 — total_sessions below active consumption count is blocked', function () {
    // Seed 3 active consumption rows.
    for ($i = 1; $i <= 3; $i++) {
        $session = anchoredSession(now()->subDays($i));
        SessionConsumption::create([
            'session_id' => $session->id,
            'session_type' => $session->getMorphClass(),
            'subscription_id' => $this->sub->id,
            'subscription_type' => $this->sub->getMorphClass(),
            'cycle_id' => $this->cycle->id,
            'student_user_id' => $this->student->id,
            'consumption_type' => SessionConsumption::TYPE_ATTENDED,
            'source' => SessionConsumption::SOURCE_AUTO_ATTENDANCE,
            'consumed_at' => now()->subDays($i),
        ]);
    }

    $conflicts = $this->validator->validate($this->sub, $this->cycle, [
        'total_sessions' => 2,
    ]);

    expect(collect($conflicts)->pluck('code'))->toContain('CYCLE-EDIT-TOTAL-LT-USED');
});

it('V4 — moving ends_at backward when future sessions exist strands them', function () {
    anchoredSession(now()->addDays(10));
    anchoredSession(now()->addDays(15));
    anchoredSession(now()->addDays(20));

    $conflicts = $this->validator->validate($this->sub, $this->cycle, [
        'ends_at' => now()->addDays(5), // backward shift
    ]);

    $byCode = collect($conflicts)->pluck('code')->all();
    expect($byCode)->toContain('CYCLE-EDIT-ENDS-BACKWARD');

    $endsConflict = collect($conflicts)->firstWhere('code', 'CYCLE-EDIT-ENDS-BACKWARD');
    expect($endsConflict['context']['stranded_sessions'])->toBe(3);
});

it('V5 — moving starts_at forward when past sessions exist orphans them', function () {
    anchoredSession(now()->subDays(3));
    anchoredSession(now()->subDay());

    $conflicts = $this->validator->validate($this->sub, $this->cycle, [
        'starts_at' => now(), // forward shift
    ]);

    $codes = collect($conflicts)->pluck('code')->all();
    expect($codes)->toContain('CYCLE-EDIT-STARTS-FORWARD');
});

it('V6 — ends_at into the past while a queued cycle exists blocks premature auto-promote', function () {
    // Add a queued sibling cycle.
    SubscriptionCycle::factory()->create([
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

    $conflicts = $this->validator->validate($this->sub, $this->cycle, [
        'ends_at' => now()->subDay(),
    ]);

    expect(collect($conflicts)->pluck('code'))->toContain('CYCLE-EDIT-ENDS-PREMATURE-PROMOTE');
});
