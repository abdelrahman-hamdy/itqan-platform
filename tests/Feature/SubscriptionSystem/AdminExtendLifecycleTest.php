<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Services\Subscription\SubscriptionMaintenanceService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Scenario 3 — Admin extend → grace window → expiry sweep.
 *
 * Pins:
 *   - extend() writes grace_period_ends_at into subscription metadata AND the
 *     current cycle row, leaving ends_at honest.
 *   - stacked extensions cumulate from the prior grace, not from ends_at.
 *   - PAUSED → extend transitions back to ACTIVE.
 *   - grace does NOT create a new cycle row.
 *   - once grace elapses, the cron auto-pauses and suspends in-window sessions.
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'admin-ext-'.uniqid()]);
    $this->admin = createAdmin($this->academy);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    setTenantContext($this->academy);
    $this->actingAs($this->admin);

    $this->service = app(SubscriptionMaintenanceService::class);
});

function activeSub(array $overrides = []): QuranSubscription
{
    $sub = QuranSubscription::factory()
        ->forStudent(test()->student)
        ->forTeacher(test()->teacher)
        ->active()
        ->create(array_merge([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(5),
            'total_sessions' => 8,
            'sessions_used' => 0,
            'sessions_remaining' => 8,
        ], $overrides));
    $sub->ensureCurrentCycle();

    return $sub->fresh();
}

describe('AE — admin extend grace lifecycle', function () {
    it('AE1 — extend(7) sets grace_period_ends_at on metadata + cycle, leaves ends_at intact', function () {
        $sub = activeSub();
        $originalEndsAt = $sub->ends_at->copy();

        $result = $this->service->extend($sub, 7);

        $fresh = $result['subscription']->fresh()->load('currentCycle');
        expect($fresh->status)->toBe(SessionSubscriptionStatus::ACTIVE);
        expect($fresh->ends_at->toDateTimeString())->toBe(
            $originalEndsAt->toDateTimeString(),
            'extend must NOT shift ends_at; it sets a separate grace window'
        );
        expect($fresh->metadata['grace_period_ends_at'] ?? null)->not->toBeNull(
            'extend must record grace_period_ends_at in metadata for legacy readers'
        );
        expect($fresh->currentCycle->grace_period_ends_at)->not->toBeNull(
            'extend must mirror the grace window onto the current cycle row'
        );

        $expectedGrace = $originalEndsAt->copy()->addDays(7);
        expect($fresh->currentCycle->grace_period_ends_at->toDateTimeString())->toBe(
            $expectedGrace->toDateTimeString()
        );
    });

    it('AE2 — multiple extensions stack from the prior grace, not from ends_at', function () {
        $sub = activeSub();
        $originalEndsAt = $sub->ends_at->copy();

        $this->service->extend($sub, 7);
        $afterFirst = $sub->fresh();

        $this->service->extend($afterFirst, 3);
        $afterSecond = $afterFirst->fresh()->load('currentCycle');

        $expected = $originalEndsAt->copy()->addDays(10);
        expect($afterSecond->currentCycle->grace_period_ends_at->toDateTimeString())->toBe(
            $expected->toDateTimeString(),
            'second extend must stack on top of the first grace, not re-anchor at ends_at'
        );
    });

    it('AE3 — extending a PAUSED sub re-activates it AND grants grace', function () {
        $sub = activeSub();
        $sub->update([
            'status' => SessionSubscriptionStatus::PAUSED,
            'paused_at' => now(),
        ]);

        $result = $this->service->extend($sub->fresh(), 7);

        $fresh = $result['subscription']->fresh();
        expect($fresh->status)->toBe(SessionSubscriptionStatus::ACTIVE);
        expect($fresh->metadata['grace_period_ends_at'] ?? null)->not->toBeNull();
    });

    it('AE5 — once grace elapses, ExpireActiveSubscriptions transitions sub PAUSED + suspends in-window sessions', function () {
        $sub = activeSub([
            'ends_at' => now()->subDays(2), // already past
        ]);
        // Stretch the current cycle to mirror the subscription's window.
        $sub->currentCycle->update(['ends_at' => $sub->ends_at]);

        // Insert a FUTURE SCHEDULED session via raw DB to bypass scheduling
        // validators — ExpireActiveSubscriptions.suspendFutureSessions() only
        // touches `scheduled_at > now()`. Past sessions are left alone (no-op).
        $sessionId = DB::table('quran_sessions')->insertGetId([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $sub->current_cycle_id,
            'status' => SessionStatus::SCHEDULED->value,
            'scheduled_at' => now()->addDays(2),
            'session_type' => 'individual',
            'duration_minutes' => 30,
            'session_code' => 'EXP-'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $session = QuranSession::find($sessionId);

        // No grace was extended (or it has elapsed): the cron must pause + suspend.
        Artisan::call('subscriptions:expire-active', ['--force' => true]);

        $fresh = $sub->fresh();
        expect($fresh->status)->toBe(SessionSubscriptionStatus::PAUSED);
        expect($session->fresh()->status)->toBe(SessionStatus::SUSPENDED);
    });

    it('AE7 — extension does NOT create a new cycle row', function () {
        $sub = activeSub();
        $cycleCountBefore = $sub->cycle_count;
        $allCyclesBefore = $sub->cycles()->count();

        $this->service->extend($sub, 7);

        $fresh = $sub->fresh();
        expect($fresh->cycle_count)->toBe(
            $cycleCountBefore,
            'extend must not bump cycle_count — it grants grace within the existing cycle'
        );
        expect($fresh->cycles()->count())->toBe(
            $allCyclesBefore,
            'no new SubscriptionCycle rows should be created by extend'
        );
    });

    it('AE8 — when the grace window itself elapses, sub flips out of schedulable state', function () {
        $sub = activeSub([
            'ends_at' => now()->subDays(5),
        ]);
        $sub->currentCycle->update(['ends_at' => $sub->ends_at]);

        // Grant a short grace that ALREADY elapsed.
        $this->service->extend($sub->fresh(), 1);
        $sub = $sub->fresh();
        // Hand-rewind the grace to clearly in the past so the cron predicate fires.
        $metadata = $sub->metadata ?? [];
        $metadata['grace_period_ends_at'] = now()->subDay()->toDateTimeString();
        $sub->update(['metadata' => $metadata]);
        $sub->currentCycle->update(['grace_period_ends_at' => now()->subDay()]);

        Artisan::call('subscriptions:expire-active', ['--force' => true]);

        $fresh = $sub->fresh();
        expect($fresh->status)->toBe(SessionSubscriptionStatus::PAUSED);
        expect($fresh->isSchedulable())->toBeFalse(
            'a PAUSED sub past its grace must not be schedulable'
        );
    });
});
