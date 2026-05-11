<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Models\Payment;
use App\Models\QuranSubscription;

/**
 * Bug #9 — payment-gateway retry creates duplicate subscription rows.
 *
 * Asserts the CORRECT expected behavior: a student should never end up with
 * two ACTIVE subscriptions for the same teacher + package after a payment
 * retry. The supported flows are:
 *   - retry succeeds → previous pending row reused OR cancelled-then-replaced
 *   - retry fails → previous row stays pending, no extra row
 *
 * Failure mode in prod (لبنى, student 83):
 *   sub 685 created → Paymob expired → 685 status=cancelled
 *   sub 686 created (Tap retry) → activated, but starts_at offset to 685's
 *     end-date instead of NOW → ghost subscription appears 1 month later
 *
 * `findDuplicatePending` at lines 180-203 of PreventsDuplicatePendingSubscriptions
 * only matches pending+pending. Once 685 was cancelled, the duplicate check
 * missed it. Then 686 was inserted as a fresh row.
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'gw-retry-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

describe('Bug #9 — gateway retry duplicate prevention', function () {
    it('B9-1 — findDuplicatePending() detects an existing pending sub for the same student/teacher', function () {
        // Sanity: the existing guard catches the trivial case of two
        // concurrent pending subs.
        $first = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->pending()
            ->create([
                'package_sessions_per_week' => 2,
            ]);

        $second = new QuranSubscription;
        $second->academy_id = $this->academy->id;
        $second->student_id = $this->student->id;
        $second->quran_teacher_id = $this->teacher->id;
        $second->package_sessions_per_week = 2;
        $second->status = SessionSubscriptionStatus::PENDING;
        $second->payment_status = \App\Enums\SubscriptionPaymentStatus::PENDING;

        $duplicate = $second->findDuplicatePending();
        expect($duplicate?->id)->toBe($first->id);
    });

    it('B9-2 — after first sub is CANCELLED, retry through the service un-cancels the original (no new row)', function () {
        // Simulates Paymob expired → 685 cancelled → user retries via Tap.
        // The real retry flow goes through SubscriptionCreationService,
        // which (Bug #9 fix) looks back for a recently-cancelled sibling
        // and reuses it instead of minting a sibling row.
        $first = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->pending()
            ->create();
        $first->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
            'cancelled_at' => now()->subMinute(),
            'cancellation_reason' => 'payment_expired',
        ]);

        $service = app(\App\Services\Subscription\SubscriptionCreationService::class);
        $reused = $service->createWithDuplicateHandling(
            \App\Services\Subscription\SubscriptionCreationService::TYPE_QURAN,
            [
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'subscription_type' => $first->subscription_type,
                'subscription_code' => 'QSP-RETRY-'.uniqid(),
                'package_id' => $first->package_id,
                'billing_cycle' => $first->billing_cycle,
                'total_sessions' => $first->total_sessions,
                'sessions_per_month' => $first->sessions_per_month,
                'monthly_amount' => $first->monthly_amount,
                'monthly_price' => $first->monthly_price,
                'total_price' => $first->total_price,
                'final_price' => $first->final_price,
                'currency' => $first->currency,
            ],
            duplicateKeyValues: [
                'quran_teacher_id' => $this->teacher->id,
                'package_id' => $first->package_id,
            ],
        );

        // CORRECT: the reused row IS the original (685), not a sibling (686).
        expect($reused->id)->toBe($first->id, 'service must reuse the recent-cancelled row');
        expect($reused->status)->toBe(SessionSubscriptionStatus::PENDING);
        expect($reused->cancelled_at)->toBeNull();

        $liveCount = QuranSubscription::query()
            ->where('student_id', $this->student->id)
            ->where('quran_teacher_id', $this->teacher->id)
            ->whereIn('status', [
                SessionSubscriptionStatus::PENDING,
                SessionSubscriptionStatus::ACTIVE,
                SessionSubscriptionStatus::CANCELLED,
            ])
            ->where('created_at', '>=', now()->subDay())
            ->count();

        expect($liveCount)->toBeLessThanOrEqual(1, sprintf(
            'after gateway retry, expected ≤1 sub in the 24h window for the same student/teacher, got %d (Bug #9 — ghost sub)',
            $liveCount
        ));
    });

    it('B9-3 — new sub created after retry must use NOW as starts_at, not the cancelled sub\'s end-date', function () {
        // Prod sub 685: starts 4/6, ends 5/6. Then 686 was created with
        // starts_at=5/6 — a future offset. This caused the May 6 surprise.
        // Correct: new sub starts NOW.
        $first = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->pending()
            ->create([
                'starts_at' => now()->subMinutes(2),
                'ends_at' => now()->subMinutes(2)->addMonth(),
            ]);
        $first->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
            'cancelled_at' => now()->subMinute(),
        ]);

        $second = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->pending()
            ->create();

        // Activate the new sub (simulating successful Tap payment).
        $second->activate();
        $second->refresh();

        // CORRECT: second's starts_at should be ~now(), not offset to first->ends_at
        expect($second->starts_at->diffInMinutes(now()))->toBeLessThan(5, sprintf(
            'second sub starts_at=%s but should be ~now (Bug #9 — ghost offset)',
            $second->starts_at->toDateTimeString()
        ));
    });
});
