<?php

declare(strict_types=1);

use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranSubscription;
use App\Services\Subscription\SubscriptionRenewalService;

/**
 * Cycle-carryover invariant for in-subscription renewals.
 *
 * Carryover is a per-subscription, cycle-to-cycle policy: when the current
 * cycle ELAPSED with leftover sessions, the next cycle inherits them in its
 * `total_sessions` and records the count on `carryover_sessions`. The
 * policy explicitly does NOT cross subscription rows — `previous_subscription_id`
 * is a renewal-chain bookkeeping field, not a carryover bridge.
 *
 * See `SubscriptionRenewalService::shouldCarryOverLeftovers` (lines 430-443)
 * for the predicate.
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'renewal-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

describe('cycle-carryover invariant', function () {
    it('renew() on an ACTIVE sub with elapsed cycle + leftovers carries them into the new cycle', function () {
        // Build a sub whose current cycle has elapsed (ends_at past) with 1
        // session still unused. The renewal must mint a new cycle whose
        // total_sessions = packageQuota + leftover, and stamp carryover on it.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'total_sessions' => 8,
                'sessions_used' => 7,
                'sessions_remaining' => 1,
                'starts_at' => now()->subMonth(),
                'ends_at' => now()->subDay(),
                'payment_status' => SubscriptionPaymentStatus::PAID,
            ]);
        $sub->ensureCurrentCycle();
        $sub->currentCycle()->update([
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
            'total_sessions' => 8,
            'sessions_used' => 7,
        ]);
        $sub = $sub->fresh();
        $renewed = app(SubscriptionRenewalService::class)->renew($sub, [
            'payment_mode' => 'paid',
            'sessions_per_month' => 8,
        ]);

        $newCycle = $renewed->currentCycle;
        expect($newCycle->carryover_sessions)->toBe(1, sprintf(
            'expected carryover_sessions=1 from prior cycle, got %d',
            $newCycle->carryover_sessions
        ));
        expect($newCycle->total_sessions)->toBe(9, sprintf(
            'expected total_sessions=9 (8 package + 1 carryover), got %d',
            $newCycle->total_sessions
        ));
    });
});
