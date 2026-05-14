<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Exceptions\Subscription\SubscriptionLockTimeout;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionReconciler;
use App\Support\Subscriptions\SubscriptionLock;
use Illuminate\Support\Facades\Cache;

/**
 * Phase B tests for SubscriptionLock.
 *
 * Asserts INV-C1 / INV-C3:
 *   - for() blocks until the lock is released, then runs the work.
 *   - for() raises SubscriptionLockTimeout when the wait expires.
 *   - tryFor() returns false on timeout (the cron-skip path).
 *   - The lock key shape is keyed on (morphClass, id) so two different
 *     subscription classes never collide.
 *
 * Lock state cleanup: the cache lock backend is array-driven under the test
 * config; clearing the cache between assertions keeps each test isolated.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    Cache::clear();

    $this->sub = QuranSubscription::factory()->make([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'sessions_used' => 0,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(20),
    ]);
    $this->sub->reconciling = true;
    $this->sub->save();
    $this->sub->reconciling = false;
});

test('SubscriptionLock::keyFor produces a (morphClass, id)-keyed string', function () {
    $key = SubscriptionLock::keyFor($this->sub);

    expect($key)->toContain('subscription:lock:')
        ->and($key)->toContain($this->sub->getMorphClass())
        ->and($key)->toContain((string) $this->sub->id);
});

test('SubscriptionLock::for runs the work and returns the result when uncontested', function () {
    $result = SubscriptionLock::for($this->sub, fn () => 'hello-locked');

    expect($result)->toBe('hello-locked');
});

test('SubscriptionLock::for releases the lock after the work completes', function () {
    SubscriptionLock::for($this->sub, fn () => 'first');
    // If the lock were not released, the second call would block to timeout.
    $result = SubscriptionLock::for($this->sub, fn () => 'second', waitTimeoutSeconds: 1);

    expect($result)->toBe('second');
});

test('SubscriptionLock::for raises SubscriptionLockTimeout when wait expires', function () {
    // Manually acquire the underlying lock and HOLD it. The next for() call
    // should block for the timeout and then raise.
    $heldLock = Cache::lock(SubscriptionLock::keyFor($this->sub), 30);
    expect($heldLock->get())->toBeTrue();

    try {
        expect(fn () => SubscriptionLock::for(
            $this->sub,
            fn () => 'never reaches',
            waitTimeoutSeconds: 1,
        ))->toThrow(SubscriptionLockTimeout::class);
    } finally {
        $heldLock->release();
    }
});

test('SubscriptionLock::tryFor returns false when the lock is held', function () {
    $heldLock = Cache::lock(SubscriptionLock::keyFor($this->sub), 30);
    expect($heldLock->get())->toBeTrue();

    try {
        $result = SubscriptionLock::tryFor(
            $this->sub,
            fn () => 'never reaches',
            maxWaitSeconds: 1,
        );
        expect($result)->toBeFalse();
    } finally {
        $heldLock->release();
    }
});

test('SubscriptionLock::tryFor runs the work and returns its result when free', function () {
    $result = SubscriptionLock::tryFor($this->sub, fn () => 'cron-tick');

    expect($result)->toBe('cron-tick');
});

test('Reconciler runs inside the lock — observer sees reconciling=true during save', function () {
    // Cycle deliberately diverges from the sub's row so the reconciler MUST
    // write — without a real diff Eloquent's isDirty() check (the no-op
    // suppression in mirrorFromCycle) would skip the save and the observer
    // assertion would have nothing to observe.
    $cycle = SubscriptionCycle::factory()->create([
        'subscribable_type' => $this->sub->getMorphClass(),
        'subscribable_id' => $this->sub->id,
        'academy_id' => $this->academy->id,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'sessions_used' => 3,
        'total_sessions' => 8,
        'starts_at' => now()->subDays(7),
        'ends_at' => now()->addDays(14),
        'package_id' => 1,
        'pricing_source' => 'package',
        'final_price' => 200,
    ]);
    $this->sub->reconciling = true;
    $this->sub->current_cycle_id = $cycle->id;
    $this->sub->save();
    $this->sub->reconciling = false;
    $this->sub->refresh();

    $observedDuringSave = false;

    // Tap into the model `saving` event for this single instance so we can
    // assert reconciling=true at observer-save time.
    QuranSubscription::saving(function (QuranSubscription $model) use (&$observedDuringSave) {
        if ($model->reconciling === true && $model->id === test()->sub->id) {
            $observedDuringSave = true;
        }
    });

    $reconciler = app(SubscriptionReconciler::class);
    SubscriptionLock::for($this->sub, function () use ($reconciler) {
        $reconciler->sync($this->sub);
    });

    expect($observedDuringSave)->toBeTrue();
});
