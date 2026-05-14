<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranPackage;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Notifications\SessionCancelledBySubscriptionPause;
use App\Services\Subscription\SubscriptionLifecycle;
use Illuminate\Support\Facades\Notification;

/**
 * G7.a — when a sub is paused or cancelled, every future SCHEDULED/READY
 * session inside the affected window must be suspended/cancelled AND a
 * SessionCancelledBySubscriptionPause notification must be dispatched to
 * the student. ONGOING sessions are NOT touched (safety: don't kick
 * students mid-meeting).
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
    $this->admin = createAdmin($this->academy);

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
        'total_sessions' => 12,
        'sessions_used' => 0,
        'sessions_remaining' => 12,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(27),
        'last_payment_date' => now()->subDays(3),
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
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
        'sessions_used' => 0,
        'total_sessions' => 12,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(27),
    ]);

    $this->sub->reconciling = true;
    $this->sub->current_cycle_id = $this->cycle->id;
    $this->sub->save();
    $this->sub->reconciling = false;
    $this->sub->refresh();
});

it('pause() suspends future scheduled sessions inside the pause window and notifies the student', function () {
    // 2 future scheduled sessions + 1 in the past — the past one must be left alone.
    $future1 = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'quran_subscription_id' => $this->sub->id,
        'subscription_cycle_id' => $this->cycle->id,
        'status' => SessionStatus::SCHEDULED,
        'scheduled_at' => now()->addDays(3),
        'duration_minutes' => 30,
    ]);
    $future2 = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'quran_subscription_id' => $this->sub->id,
        'subscription_cycle_id' => $this->cycle->id,
        'status' => SessionStatus::SCHEDULED,
        'scheduled_at' => now()->addDays(7),
        'duration_minutes' => 30,
    ]);
    $past = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'quran_subscription_id' => $this->sub->id,
        'subscription_cycle_id' => $this->cycle->id,
        'status' => SessionStatus::COMPLETED,
        'scheduled_at' => now()->subDays(1),
        'duration_minutes' => 30,
    ]);

    Notification::fake();

    app(SubscriptionLifecycle::class)->pause(
        $this->sub,
        $this->admin,
        reason: 'admin_pause_test',
    );

    $future1->refresh();
    $future2->refresh();
    $past->refresh();

    expect($future1->status)->toBe(SessionStatus::SUSPENDED)
        ->and($future2->status)->toBe(SessionStatus::SUSPENDED)
        ->and($past->status)->toBe(SessionStatus::COMPLETED);

    Notification::assertSentTo(
        $this->student,
        SessionCancelledBySubscriptionPause::class,
        fn (SessionCancelledBySubscriptionPause $n) => $n->cause === 'subscription_paused'
    );

    // Exactly 2 notifications (one per future session).
    Notification::assertSentToTimes($this->student, SessionCancelledBySubscriptionPause::class, 2);
});

it('cancel() cancels every future scheduled session and notifies the student', function () {
    QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'quran_subscription_id' => $this->sub->id,
        'subscription_cycle_id' => $this->cycle->id,
        'status' => SessionStatus::SCHEDULED,
        'scheduled_at' => now()->addDays(5),
        'duration_minutes' => 30,
    ]);

    Notification::fake();

    app(SubscriptionLifecycle::class)->cancel(
        $this->sub,
        $this->admin,
        reason: 'admin_cancel_test',
    );

    Notification::assertSentTo(
        $this->student,
        SessionCancelledBySubscriptionPause::class,
        fn (SessionCancelledBySubscriptionPause $n) => $n->cause === 'subscription_cancelled'
    );
});
