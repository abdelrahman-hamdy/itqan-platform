<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;

/**
 * Asserts the renewal surface — `POST /renew` and `POST /resubscribe`.
 *
 *   - renew(): validates billing_cycle ∈ {monthly,quarterly,yearly},
 *     payment_mode ∈ {paid,unpaid}; delegates to
 *     SubscriptionRenewalService::renew(); returns redirect to the *new*
 *     subscription's show page on success.
 *   - resubscribe(): only callable on cancelled/expired/paused; flips back to
 *     ACTIVE then calls renew() with replace-now semantics.
 *
 * See `SupervisorSubscriptionsController::renew()` (line 428-431),
 * `::resubscribe()` (line 436-439), `::performRenewalAction()` (line 444-485).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'renew-test-'.uniqid()]);
    $this->admin = createAdmin($this->academy);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

function renewUrl(string $subdomain, int $id): string
{
    return route('manage.subscriptions.renew', [
        'subdomain' => $subdomain,
        'type' => 'quran',
        'subscription' => $id,
    ]);
}

function resubscribeUrl(string $subdomain, int $id): string
{
    return route('manage.subscriptions.resubscribe', [
        'subdomain' => $subdomain,
        'type' => 'quran',
        'subscription' => $id,
    ]);
}

describe('POST /manage/subscriptions/{type}/{id}/renew', function () {
    it('R1 — renew on an exhausted subscription replaces immediately and redirects to the new sub', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'sessions_used' => 8,
                'sessions_remaining' => 0,
                'total_sessions' => 8,
                // Mark sub paid so the materialized current cycle is PAID —
                // otherwise the unpaid-current-cycle gate (2026-05-11) blocks
                // renew(). This test isn't covering that gate.
                'payment_status' => SubscriptionPaymentStatus::PAID,
            ]);

        $response = $this->actingAs($this->admin)->post(
            renewUrl($this->academy->subdomain, $sub->id),
            ['billing_cycle' => 'monthly', 'payment_mode' => 'paid']
        );

        $response->assertRedirect();
        // The redirect target is `manage.subscriptions.show` for the renewed
        // subscription. With replace-now, that's the *same* subscription id,
        // not a new sibling row.
        $expectedShow = route('manage.subscriptions.show', [
            'subdomain' => $this->academy->subdomain,
            'type' => 'quran',
            'subscription' => $sub->id,
        ]);
        $response->assertRedirect($expectedShow);
        $response->assertSessionHas('success');

        // After renewal: a new cycle row exists for this subscription.
        $cycles = $sub->fresh()->cycles()->count();
        expect($cycles)->toBeGreaterThanOrEqual(1);
    });

    it('R2 — renew validates billing_cycle is monthly|quarterly|yearly', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();

        $response = $this->actingAs($this->admin)->post(
            renewUrl($this->academy->subdomain, $sub->id),
            ['billing_cycle' => 'biweekly']
        );

        $response->assertRedirect();
        // The controller flashes 'error' (Validator::fails branch) rather than
        // setting a session validation error.
        $response->assertSessionHas('error');
    });

    it('R3 — renew on a still-active sub queues a new cycle (does not duplicate the row)', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'sessions_used' => 2,
                'sessions_remaining' => 6,
                'total_sessions' => 8,
                'payment_status' => SubscriptionPaymentStatus::PAID,
            ]);

        $this->actingAs($this->admin)->post(
            renewUrl($this->academy->subdomain, $sub->id),
            ['billing_cycle' => 'monthly']
        );

        $fresh = $sub->fresh();
        // The original subscription row is preserved; the queued cycle is
        // attached as a SubscriptionCycle row, not a sibling QuranSubscription.
        expect(QuranSubscription::query()
            ->where('student_id', $this->student->id)
            ->where('quran_teacher_id', $this->teacher->id)
            ->count())->toBe(1);
        expect($fresh->cycles()->count())->toBeGreaterThanOrEqual(1);
    });

    it('R4 — renew is rejected on a CANCELLED sub (use resubscribe instead)', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->cancelled()
            ->create();

        $response = $this->actingAs($this->admin)->post(
            renewUrl($this->academy->subdomain, $sub->id),
            ['billing_cycle' => 'monthly']
        );

        // Service throws "cannot renew" → controller catches and flashes error.
        $response->assertSessionHas('error');
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::CANCELLED);
    });
});

describe('POST /manage/subscriptions/{type}/{id}/resubscribe', function () {
    it('R5 — Academic resubscribe succeeds (Bug #3 fix: total_price excluded from academic update)', function () {
        // Before the fix: resubscribe → renew() → syncSubscriptionToCycle()
        // wrote `total_price` to academic_subscriptions, which has no such
        // column → SQLSTATE 42S22 crash. After the fix the column is only
        // written for QuranSubscription, so the academic resubscribe path
        // completes cleanly and the sub flips ACTIVE.
        $academicTeacher = createAcademicTeacher($this->academy);
        $sub = AcademicSubscription::factory()
            ->withStudent($this->student)
            ->withTeacher($academicTeacher)
            ->forAcademy($this->academy)
            ->cancelled()
            ->create([
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->subDays(15),
                'end_date' => now()->subDays(15),
                'sessions_used' => 5,
                'sessions_remaining' => 0,
                'total_sessions' => 8,
            ]);

        $response = $this->actingAs($this->admin)->post(
            route('manage.subscriptions.resubscribe', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'academic',
                'subscription' => $sub->id,
            ]),
            ['billing_cycle' => 'monthly']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    });

    it('R-Q1 — Quran resubscribe succeeds with an active teacher (Bug #2 fix: lookup uses user_id)', function () {
        // Before the fix: validateTeacherAvailability did
        // QuranTeacherProfile::find($quran_teacher_id) — but the column
        // stores users.id, not the profile id, so find() always returned
        // null and the resubscribe threw "teacher unavailable". After the
        // fix the lookup goes by user_id and the active teacher is found.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->cancelled()
            ->create();

        $response = $this->actingAs($this->admin)->post(
            resubscribeUrl($this->academy->subdomain, $sub->id),
            ['billing_cycle' => 'monthly']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    });

    it('R6 — resubscribe is rejected on an ACTIVE sub (use renew instead)', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();

        $response = $this->actingAs($this->admin)->post(
            resubscribeUrl($this->academy->subdomain, $sub->id),
            ['billing_cycle' => 'monthly']
        );

        $response->assertSessionHas('error');
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    });
});
