<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Exceptions\Subscription\OverConsumptionAttempt;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionConsumption;

/**
 * Phase B counting tests for SubscriptionConsumption.
 *
 * Asserts the P5 precedence cascade + the INV-B3, INV-B4, INV-B5 contracts
 * from `docs/subscription-invariants.md §5`.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
    $this->actor = createAdmin($this->academy);

    // Sub + cycle in invariant-clean shape — every record() call below
    // operates against this fixture.
    $this->sub = QuranSubscription::factory()->make([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'total_sessions' => 4,
        'sessions_used' => 0,
        'sessions_remaining' => 4,
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
        'total_sessions' => 4,
        'sessions_used' => 0,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
        'package_id' => 1,
        'pricing_source' => 'package',
        'final_price' => 200,
    ]);
    $this->sub->reconciling = true;
    $this->sub->current_cycle_id = $this->cycle->id;
    $this->sub->save();
    $this->sub->reconciling = false;
    $this->sub->refresh();

    $this->consumption = app(SubscriptionConsumption::class);
});

/**
 * Build a QuranSession attached to the test's subscription + cycle so the
 * consumption writer can resolve the anchor cycle.
 */
function buildSession(): QuranSession
{
    $session = QuranSession::factory()->make([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'quran_subscription_id' => test()->sub->id,
        'subscription_cycle_id' => test()->cycle->id,
        'status' => SessionStatus::SCHEDULED->value,
        'scheduled_at' => now()->addHour(),
    ]);
    $session->save();

    return $session;
}

test('record() writes a new row when none exists', function () {
    $session = buildSession();

    $row = $this->consumption->record(
        $session,
        $this->student,
        $this->sub,
        SessionConsumption::SOURCE_TEACHER_REPORT,
        $this->teacher,
        SessionConsumption::TYPE_ATTENDED,
    );

    expect($row)->toBeInstanceOf(SessionConsumption::class)
        ->and($row->source)->toBe(SessionConsumption::SOURCE_TEACHER_REPORT)
        ->and($row->consumption_type)->toBe(SessionConsumption::TYPE_ATTENDED)
        ->and(SessionConsumption::query()
            ->where('session_id', $session->id)
            ->where('subscription_id', $this->sub->id)
            ->count())->toBe(1);
});

test('record() with same source updates in place (audit-promoted update)', function () {
    $session = buildSession();

    $r1 = $this->consumption->record(
        $session,
        $this->student,
        $this->sub,
        SessionConsumption::SOURCE_TEACHER_REPORT,
        $this->teacher,
        SessionConsumption::TYPE_ATTENDED,
    );

    $r2 = $this->consumption->record(
        $session,
        $this->student,
        $this->sub,
        SessionConsumption::SOURCE_TEACHER_REPORT,
        $this->teacher,
        SessionConsumption::TYPE_LATE,
    );

    expect($r1->id)->toBe($r2->id)
        ->and($r2->consumption_type)->toBe(SessionConsumption::TYPE_LATE)
        ->and(SessionConsumption::query()
            ->where('session_id', $session->id)
            ->count())->toBe(1);
});

test('record() with lower precedence drops the write and returns null', function () {
    $session = buildSession();

    // Higher precedence first: admin_manual.
    $existing = $this->consumption->record(
        $session,
        $this->student,
        $this->sub,
        SessionConsumption::SOURCE_ADMIN_MANUAL,
        $this->actor,
        SessionConsumption::TYPE_ATTENDED,
    );

    // Now attempt a lower precedence: auto_attendance.
    $attempt = $this->consumption->record(
        $session,
        $this->student,
        $this->sub,
        SessionConsumption::SOURCE_AUTO_ATTENDANCE,
        null,
        SessionConsumption::TYPE_LATE,
    );

    expect($attempt)->toBeNull();

    // Existing row remained untouched.
    $existing->refresh();
    expect($existing->source)->toBe(SessionConsumption::SOURCE_ADMIN_MANUAL)
        ->and($existing->consumption_type)->toBe(SessionConsumption::TYPE_ATTENDED);
});

test('record() with higher precedence promotes an existing lower-precedence row', function () {
    $session = buildSession();

    // Lower first: auto_attendance.
    $auto = $this->consumption->record(
        $session,
        $this->student,
        $this->sub,
        SessionConsumption::SOURCE_AUTO_ATTENDANCE,
        null,
        SessionConsumption::TYPE_ATTENDED,
    );

    // Higher: admin_manual.
    $promoted = $this->consumption->record(
        $session,
        $this->student,
        $this->sub,
        SessionConsumption::SOURCE_ADMIN_MANUAL,
        $this->actor,
        SessionConsumption::TYPE_LATE,
    );

    expect($promoted)->not->toBeNull()
        ->and($promoted->id)->toBe($auto->id) // same row, promoted in place
        ->and($promoted->source)->toBe(SessionConsumption::SOURCE_ADMIN_MANUAL)
        ->and($promoted->consumption_type)->toBe(SessionConsumption::TYPE_LATE)
        ->and(SessionConsumption::query()->count())->toBe(1);
});

test('INV-B5 — reverse() atomically populates all three reversal fields', function () {
    $session = buildSession();
    $row = $this->consumption->record(
        $session,
        $this->student,
        $this->sub,
        SessionConsumption::SOURCE_TEACHER_REPORT,
        $this->teacher,
        SessionConsumption::TYPE_ATTENDED,
    );

    $reversed = $this->consumption->reverse(
        $row,
        'student requested refund',
        $this->actor,
    );

    expect($reversed->reversed_at)->not->toBeNull()
        ->and($reversed->reversed_reason)->toBe('student requested refund')
        ->and($reversed->reversed_by_user_id)->toBe($this->actor->id);
});

test('INV-B4 — record() that would push remaining < 0 raises OverConsumptionAttempt', function () {
    // Cycle has 4 total. Fill it.
    for ($i = 0; $i < 4; $i++) {
        $session = buildSession();
        $this->consumption->record(
            $session,
            $this->student,
            $this->sub,
            SessionConsumption::SOURCE_TEACHER_REPORT,
            $this->teacher,
            SessionConsumption::TYPE_ATTENDED,
        );
    }

    $this->cycle->refresh();
    expect((int) $this->cycle->sessions_used)->toBe(4);

    // 5th record() must throw OverConsumptionAttempt.
    $extra = buildSession();
    expect(fn () => $this->consumption->record(
        $extra,
        $this->student,
        $this->sub,
        SessionConsumption::SOURCE_TEACHER_REPORT,
        $this->teacher,
        SessionConsumption::TYPE_ATTENDED,
    ))->toThrow(OverConsumptionAttempt::class);
});

test('INV-B3 — after N successful records, cycle.sessions_used equals N (via reconciler)', function () {
    $count = 3;
    for ($i = 0; $i < $count; $i++) {
        $session = buildSession();
        $this->consumption->record(
            $session,
            $this->student,
            $this->sub,
            SessionConsumption::SOURCE_TEACHER_REPORT,
            $this->teacher,
            SessionConsumption::TYPE_ATTENDED,
        );
    }

    $this->cycle->refresh();
    $activeRows = SessionConsumption::query()
        ->where('cycle_id', $this->cycle->id)
        ->whereNull('reversed_at')
        ->count();

    expect((int) $this->cycle->sessions_used)->toBe($count)
        ->and($activeRows)->toBe($count);
});

test('reverse() is idempotent on an already-reversed row', function () {
    $session = buildSession();
    $row = $this->consumption->record(
        $session,
        $this->student,
        $this->sub,
        SessionConsumption::SOURCE_TEACHER_REPORT,
        $this->teacher,
        SessionConsumption::TYPE_ATTENDED,
    );

    $first = $this->consumption->reverse($row, 'first reason', $this->actor);
    $firstAt = $first->reversed_at;
    $second = $this->consumption->reverse($row, 'a different reason', $this->actor);

    // Idempotent: no-op when already reversed.
    expect($second->reversed_at?->equalTo($firstAt))->toBeTrue()
        ->and($second->reversed_reason)->toBe('first reason');
});
