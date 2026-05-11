<?php

declare(strict_types=1);

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\AdminSubscriptionWizardService;
use Illuminate\Support\Facades\Notification;

/**
 * Cycle materialization reset + admin preset defense — guarding the gap that
 * caused sub #781 (Ammar Yasser) to show "subscription exhausted" after only
 * 8 of 12 sessions in cycle 2.
 *
 * The defect: `SubscriptionCycle::materializeFromSubscription` used to copy
 * `source->sessions_used` straight onto every materialized cycle row,
 * regardless of cycle_number. After an admin preset (consumed_sessions > 0)
 * had been written to the subscription row at creation, any later cycle the
 * `ensureCurrentCycle` lazy backfill path materialized would silently inherit
 * the preset (+ subsequent platform usage), inflating the new cycle's
 * sessions_used and exhausting the subscription early.
 *
 * Invariants pinned here:
 *   CMR1 — Cycle 1 still inherits source.sessions_used (admin preset path).
 *   CMR2 — Cycle ≥ 2 always materializes with sessions_used = 0, regardless
 *          of what the parent subscription's sessions_used currently is.
 *   CMR3 — Cycle ≥ 2 also zeros sessions_completed / sessions_missed.
 *   CMR4 — total_sessions (the package quota) is still carried over verbatim
 *          for both cycle 1 and cycle N+1 — only consumption counters reset.
 *   AWG1 — setInitialProgress refuses to apply when cycle_count > 1, even if
 *          a stray caller invokes the wizard against an already-renewed sub.
 *   AWG2 — setInitialProgress still applies normally on a fresh sub
 *          (cycle_count = 1 from ensureCurrentCycle).
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'cycmat-'.uniqid()]);
    $this->admin = createAdmin($this->academy);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    setTenantContext($this->academy);
    $this->actingAs($this->admin);
});

describe('CMR — materializeFromSubscription resets counters for cycle ≥ 2', function () {
    it('CMR1 — cycle 1 still inherits source.sessions_used (admin preset semantics)', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'total_sessions' => 12,
                // Simulate an admin preset already written to the row.
                'sessions_used' => 4,
                'sessions_remaining' => 8,
                'total_sessions_completed' => 4,
            ]);

        $cycle = SubscriptionCycle::materializeFromSubscription(
            $sub,
            $sub,
            SubscriptionCycle::STATE_ACTIVE,
            ['cycle_number' => 1],
        );

        expect($cycle->cycle_number)->toBe(1);
        expect((int) $cycle->sessions_used)->toBe(4, 'cycle 1 must inherit the admin preset');
        expect((int) $cycle->sessions_completed)->toBe(4);
        expect((int) $cycle->total_sessions)->toBe(12);
    });

    it('CMR2 — cycle 2 materializes with sessions_used = 0 even if sub.sessions_used is non-zero', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'total_sessions' => 12,
                // Mid-cycle state: parent row reflects accumulated consumption
                // from the prior cycle. If we don't reset, this leaks into
                // cycle 2 and exhausts it on day one.
                'sessions_used' => 8,
                'sessions_remaining' => 4,
                'total_sessions_completed' => 8,
            ]);

        $cycle = SubscriptionCycle::materializeFromSubscription(
            $sub,
            $sub,
            SubscriptionCycle::STATE_ACTIVE,
            ['cycle_number' => 2],
        );

        expect($cycle->cycle_number)->toBe(2);
        expect((int) $cycle->sessions_used)
            ->toBe(0, 'cycle ≥ 2 must start fresh — the renewal counter is what protects from preset leakage');
    });

    it('CMR3 — cycle ≥ 2 zeros sessions_completed and sessions_missed too', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'total_sessions' => 12,
                'sessions_used' => 7,
                'sessions_remaining' => 5,
                'total_sessions_completed' => 6,
                'total_sessions_missed' => 1,
            ]);

        $cycle = SubscriptionCycle::materializeFromSubscription(
            $sub,
            $sub,
            SubscriptionCycle::STATE_QUEUED,
            ['cycle_number' => 3],
        );

        expect((int) $cycle->sessions_used)->toBe(0);
        expect((int) $cycle->sessions_completed)->toBe(0);
        expect((int) $cycle->sessions_missed)->toBe(0);
    });

    it('CMR4 — total_sessions (quota) is preserved on cycle ≥ 2, only consumption resets', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'total_sessions' => 16,
                'sessions_used' => 5,
                'sessions_remaining' => 11,
            ]);

        $cycle = SubscriptionCycle::materializeFromSubscription(
            $sub,
            $sub,
            SubscriptionCycle::STATE_ACTIVE,
            ['cycle_number' => 2],
        );

        expect((int) $cycle->total_sessions)->toBe(16, 'package quota carries over verbatim');
        expect((int) $cycle->sessions_used)->toBe(0);
        // Convenience accessor — confirms the math wires together end-to-end.
        expect($cycle->sessions_remaining)->toBe(16);
    });

    it('CMR5 — cycle_number = 1 default path (no explicit override) also inherits — guards against silent regression', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'total_sessions' => 8,
                'sessions_used' => 2,
                'sessions_remaining' => 6,
                'total_sessions_completed' => 2,
            ]);

        // No `cycle_number` override → materialize must default to 1 because
        // no prior cycles exist on this subscription.
        $cycle = SubscriptionCycle::materializeFromSubscription(
            $sub,
            $sub,
            SubscriptionCycle::STATE_ACTIVE,
        );

        expect($cycle->cycle_number)->toBe(1);
        expect((int) $cycle->sessions_used)->toBe(2, 'first auto-numbered cycle still inherits admin preset');
    });
});

describe('AWG — AdminSubscriptionWizard.setInitialProgress defensive guards', function () {
    beforeEach(function () {
        $this->package = QuranPackage::create([
            'academy_id' => $this->academy->id,
            'name' => 'AWG Test Package',
            'sessions_per_month' => 8,
            'monthly_price' => 200,
            'quarterly_price' => 540,
            'yearly_price' => 1920,
            'session_duration_minutes' => 60,
            'currency' => 'SAR',
            'is_active' => true,
        ]);

        $this->service = app(AdminSubscriptionWizardService::class);

        $this->payload = [
            'type' => 'quran_individual',
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'package_id' => $this->package->id,
            'billing_cycle' => BillingCycle::MONTHLY->value,
            'amount' => 200,
            'payment_method' => 'cash',
            'payment_date' => now(),
            'consumed_sessions' => 0,
        ];
    });

    it('AWG1 — fresh subscription with cycle_count = 1 still applies the preset', function () {
        $sub = $this->service->createFullSubscription(array_merge($this->payload, [
            'consumed_sessions' => 3,
        ]));

        $fresh = $sub->fresh();
        // Guard does not trip on cycle_count = 1 (single auto-materialized cycle).
        expect((int) $fresh->sessions_used)->toBe(3);
        expect((int) $fresh->sessions_remaining)->toBe(5);
    });

    it('AWG2 — guard refuses to apply preset when sub already has cycle_count > 1', function () {
        // Build a sub that already has two cycles — simulate a stray reuse of
        // the wizard against an already-renewed subscription.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'total_sessions' => 12,
                'sessions_used' => 0,
                'sessions_remaining' => 12,
                'cycle_count' => 2,
            ]);

        // Invoke the private guard directly via reflection — calling
        // createFullSubscription would build a new sub, not exercise the guard.
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('setInitialProgress');
        $method->setAccessible(true);
        $method->invoke($this->service, $sub, ['consumed_sessions' => 4]);

        $fresh = $sub->fresh();
        expect((int) $fresh->sessions_used)
            ->toBe(0, 'guard must refuse: preset would have leaked into the live cycle');
        expect((int) $fresh->sessions_remaining)->toBe(12);
    });

    it('AWG3 — guard refuses when consumed >= total_sessions (sanity boundary)', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'total_sessions' => 8,
                'sessions_used' => 0,
                'sessions_remaining' => 8,
                'cycle_count' => 1,
            ]);

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('setInitialProgress');
        $method->setAccessible(true);
        $method->invoke($this->service, $sub, ['consumed_sessions' => 8]);

        $fresh = $sub->fresh();
        expect((int) $fresh->sessions_used)->toBe(0, 'consumed == total must not exhaust the sub at creation');
        expect((int) $fresh->sessions_remaining)->toBe(8);
    });
});

describe('CMR/AMR — Ammar regression: ensureCurrentCycle on cycle ≥ 2 does not bake in the preset', function () {
    it('AMR1 — backfill on a mid-life sub creates fresh cycle ≥ 2, parent counters survive', function () {
        // Reproduce Ammar's shape: subscription mid-cycle with a non-zero
        // sessions_used. Materialize cycle 2 directly (mirrors what the
        // legacy ensureCurrentCycle backfill path would do if it were ever
        // asked to build a fresh cycle 2 from the parent row alone).
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'total_sessions' => 12,
                'sessions_used' => 8,           // Ammar's actual cycle-2 count
                'sessions_remaining' => 4,
                'total_sessions_completed' => 8,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'status' => SessionSubscriptionStatus::ACTIVE,
            ]);

        // Pre-seed cycle 1 so cycle_number auto-advances to 2 on the next call.
        SubscriptionCycle::materializeFromSubscription(
            $sub,
            $sub,
            SubscriptionCycle::STATE_ARCHIVED,
            ['cycle_number' => 1],
        );

        $cycle2 = SubscriptionCycle::materializeFromSubscription(
            $sub,
            $sub,
            SubscriptionCycle::STATE_ACTIVE,
        );

        expect($cycle2->cycle_number)->toBe(2);
        expect((int) $cycle2->sessions_used)
            ->toBe(0, 'pre-fix Ammar bug would have stored 8 here, exhausting the cycle on day one');
        expect((int) $cycle2->total_sessions)->toBe(12);
    });
});
