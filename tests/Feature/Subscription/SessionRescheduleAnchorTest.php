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
 * G4 — when a Quran session is rescheduled across a cycle boundary the
 * session's `subscription_cycle_id` anchor MUST move with it. Without the
 * fix the consumption row mints against the old cycle's quota, leaking
 * quota into a no-longer-active window.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
    $this->package = QuranPackage::factory()->create([
        'academy_id' => $this->academy->id,
        'monthly_price' => 200,
        'session_duration_minutes' => 30,
    ]);

    $this->sub = QuranSubscription::factory()->make([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'package_id' => $this->package->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'total_sessions' => 16,
        'sessions_used' => 0,
        'sessions_remaining' => 16,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addDays(60),
        'last_payment_date' => now()->subDays(10),
    ]);
    $this->sub->reconciling = true;
    $this->sub->save();
    $this->sub->reconciling = false;

    // Two adjacent cycles: A (ends in 5 days), B (starts in 6 days, ends in 36 days).
    $this->cycleA = SubscriptionCycle::factory()->create([
        'subscribable_type' => $this->sub->getMorphClass(),
        'subscribable_id' => $this->sub->id,
        'academy_id' => $this->academy->id,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addDays(5),
        'cycle_number' => 1,
    ]);

    $this->cycleB = SubscriptionCycle::factory()->create([
        'subscribable_type' => $this->sub->getMorphClass(),
        'subscribable_id' => $this->sub->id,
        'academy_id' => $this->academy->id,
        'cycle_state' => SubscriptionCycle::STATE_QUEUED,
        'payment_status' => SubscriptionCycle::PAYMENT_PENDING,
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'starts_at' => now()->addDays(6),
        'ends_at' => now()->addDays(36),
        'cycle_number' => 2,
    ]);

    $this->sub->reconciling = true;
    $this->sub->current_cycle_id = $this->cycleA->id;
    $this->sub->save();
    $this->sub->reconciling = false;
});

it('moves a QuranSession across a cycle boundary and updates its subscription_cycle_id anchor', function () {
    // Original session sits inside cycle A.
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'quran_subscription_id' => $this->sub->id,
        'subscription_cycle_id' => $this->cycleA->id,
        'status' => SessionStatus::SCHEDULED,
        'scheduled_at' => now()->addDays(2),
        'duration_minutes' => 30,
    ]);

    expect((int) $session->subscription_cycle_id)->toBe($this->cycleA->id);

    // Reschedule to a date inside cycle B.
    $session->reschedule(now()->addDays(10), reason: 'student request');
    $session->refresh();

    expect((int) $session->subscription_cycle_id)->toBe($this->cycleB->id);
});

it('keeps the cycle anchor when rescheduling within the same cycle', function () {
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'quran_subscription_id' => $this->sub->id,
        'subscription_cycle_id' => $this->cycleA->id,
        'status' => SessionStatus::SCHEDULED,
        'scheduled_at' => now()->addDays(1),
        'duration_minutes' => 30,
    ]);

    $session->reschedule(now()->addDays(3), reason: 'minor shuffle');
    $session->refresh();

    expect((int) $session->subscription_cycle_id)->toBe($this->cycleA->id);
});

it('SubscriptionCycle::cycleForDate locates the right cycle for a given date', function () {
    $insideA = SubscriptionCycle::cycleForDate($this->sub, now()->addDay());
    $insideB = SubscriptionCycle::cycleForDate($this->sub, now()->addDays(20));
    $afterAll = SubscriptionCycle::cycleForDate($this->sub, now()->addDays(60));

    expect($insideA?->id)->toBe($this->cycleA->id)
        ->and($insideB?->id)->toBe($this->cycleB->id)
        ->and($afterAll)->toBeNull();
});
