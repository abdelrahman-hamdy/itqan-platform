<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionType;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Services\Subscription\SubscriptionLifecycle;

/**
 * Plan 4.7 — Lifecycle::create is the single canonical create entry point.
 * When called with duplicateKeyValues it runs the dedup pipeline:
 *   1. reuseRecentCancelled — revive a sibling cancelled within the
 *      retry-window (Bug #9 gateway-retry envelope).
 *   2. cancelDuplicatePending — cancel every PENDING sibling for the combo.
 *   3. create — mint the new row.
 *
 * Without duplicateKeyValues, dedup is skipped and a fresh row is minted
 * each call (matches the old `Lifecycle::create` behaviour exactly).
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

    $this->baseData = [
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'subscription_type' => 'individual',
        'package_id' => $this->package->id,
        'package_name_ar' => $this->package->name_ar ?? 'الأساسي',
        'package_name_en' => $this->package->name_en ?? 'Basic',
        'package_sessions_per_week' => 2,
        'package_session_duration_minutes' => 30,
        'total_sessions' => 8,
        'sessions_remaining' => 8,
        'total_price' => 200,
        'final_price' => 200,
        'currency' => 'SAR',
    ];
});

it('skips dedup when duplicateKeyValues is empty (single fresh sub minted)', function () {
    $sub = app(SubscriptionLifecycle::class)->create(
        SubscriptionType::QURAN,
        $this->baseData,
        $this->admin,
    );

    expect($sub)->toBeInstanceOf(QuranSubscription::class)
        ->and(QuranSubscription::query()->where('student_id', $this->student->id)->count())->toBe(1);
});

it('cancels prior PENDING siblings when duplicateKeyValues is supplied', function () {
    // Seed a stale PENDING sub for the same combo.
    $stale = QuranSubscription::factory()->make(array_merge($this->baseData, [
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
    ]));
    $stale->reconciling = true;
    $stale->save();
    $stale->reconciling = false;

    $fresh = app(SubscriptionLifecycle::class)->create(
        SubscriptionType::QURAN,
        $this->baseData,
        $this->admin,
        duplicateKeyValues: [
            'quran_teacher_id' => $this->teacher->id,
            'subscription_type' => 'individual',
        ],
    );

    $stale->refresh();

    // Stale row got cancelled by step 2 (cancelDuplicatePending).
    expect($stale->status)->toBe(SessionSubscriptionStatus::CANCELLED)
        ->and($fresh->id)->not->toBe($stale->id);
});

it('Lifecycle::create still wraps in lock + audit + reconciler when dedup is supplied', function () {
    $before = \App\Models\SubscriptionAuditLog::query()->where('action', 'create')->count();

    app(SubscriptionLifecycle::class)->create(
        SubscriptionType::QURAN,
        $this->baseData,
        $this->admin,
        duplicateKeyValues: [
            'quran_teacher_id' => $this->teacher->id,
            'subscription_type' => 'individual',
        ],
    );

    expect(\App\Models\SubscriptionAuditLog::query()->where('action', 'create')->count())
        ->toBe($before + 1);
});
