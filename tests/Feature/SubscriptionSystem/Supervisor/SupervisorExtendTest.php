<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranSubscription;

/**
 * Asserts the grace-period (extend) surface — the *correct* path that
 * delegates to `SubscriptionMaintenanceService::extend()` /
 * `cancelExtension()`. No bug here; the controller does the right thing.
 *
 *   - extend on ACTIVE: stamps grace_period_ends_at on metadata + cycle.
 *   - extend on PAUSED/EXPIRED: re-activates and sets grace.
 *   - extend stacks on existing grace.
 *   - validation: extend_days 1..365.
 *   - cancel-extension: clears grace; pauses if ends_at is past.
 *
 * See `SupervisorSubscriptionsController::extend()` (line 292-320) and
 * `::cancelExtension()` (line 322-339).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'extend-test-'.uniqid()]);
    $this->admin = createAdmin($this->academy);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

function extendUrl(string $subdomain, int $id): string
{
    return route('manage.subscriptions.extend', [
        'subdomain' => $subdomain,
        'type' => 'quran',
        'subscription' => $id,
    ]);
}

describe('POST /manage/subscriptions/{type}/{id}/extend', function () {
    it('E1 — extend on an ACTIVE subscription stores grace_period_ends_at on metadata', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();
        $endsAtBefore = $sub->ends_at->copy();

        $response = $this->actingAs($this->admin)
            ->post(extendUrl($this->academy->subdomain, $sub->id), ['extend_days' => 7]);

        $response->assertRedirect();
        $fresh = $sub->fresh();
        // Grace stamped at ends_at + 7 days (extend_days from base date = ends_at).
        expect($fresh->metadata['grace_period_ends_at'] ?? null)->not->toBeNull();
        // ends_at MUST NOT shift — extend grants grace, not paid time.
        expect($fresh->ends_at->equalTo($endsAtBefore))->toBeTrue();
        // Status stays ACTIVE.
        expect($fresh->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    });

    it('E2 — extend on a PAUSED subscription reactivates it for the grace duration', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->autoPaused() // ends_at is in the past (sub days)
            ->create();

        $response = $this->actingAs($this->admin)
            ->post(extendUrl($this->academy->subdomain, $sub->id), ['extend_days' => 5]);

        $response->assertRedirect();
        $fresh = $sub->fresh();
        // PAUSED → ACTIVE because of the grace.
        expect($fresh->status)->toBe(SessionSubscriptionStatus::ACTIVE);
        expect($fresh->metadata['grace_period_ends_at'] ?? null)->not->toBeNull();
    });

    it('E3 — extend stacks on an existing grace period rather than replacing it', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->inGracePeriod(7) // grace_period_ends_at = now + 7 days
            ->create();
        $graceBefore = \Carbon\Carbon::parse($sub->metadata['grace_period_ends_at']);

        $this->actingAs($this->admin)
            ->post(extendUrl($this->academy->subdomain, $sub->id), ['extend_days' => 5]);

        $fresh = $sub->fresh();
        $graceAfter = \Carbon\Carbon::parse($fresh->metadata['grace_period_ends_at']);
        // Grace stretches: +5 days on top of the existing +7.
        $shiftSeconds = $graceBefore->diffInSeconds($graceAfter, true);
        expect($shiftSeconds)->toBeGreaterThanOrEqual(5 * 86400 - 60);
        expect($shiftSeconds)->toBeLessThanOrEqual(5 * 86400 + 60);
    });

    it('E4 — validation rejects extend_days < 1 or > 365', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();

        // Below the floor.
        $belowResp = $this->actingAs($this->admin)
            ->post(extendUrl($this->academy->subdomain, $sub->id), ['extend_days' => 0]);
        $belowResp->assertRedirect();
        $belowResp->assertSessionHasErrors('extend_days');

        // Above the ceiling.
        $aboveResp = $this->actingAs($this->admin)
            ->post(extendUrl($this->academy->subdomain, $sub->id), ['extend_days' => 366]);
        $aboveResp->assertRedirect();
        $aboveResp->assertSessionHasErrors('extend_days');

        // Subscription unchanged.
        expect($sub->fresh()->metadata['grace_period_ends_at'] ?? null)->toBeNull();
    });
});

describe('POST /manage/subscriptions/{type}/{id}/cancel-extension', function () {
    it('E5 — cancel-extension clears grace metadata and flashes success on a sub in grace', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->inGracePeriod(7)
            ->create();

        $response = $this->actingAs($this->admin)->post(
            route('manage.subscriptions.cancel-extension', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
                'subscription' => $sub->id,
            ])
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $fresh = $sub->fresh();
        expect($fresh->metadata['grace_period_ends_at'] ?? null)->toBeNull();
        // Because ends_at is in the past, cancellation transitions to PAUSED.
        expect($fresh->status)->toBe(SessionSubscriptionStatus::PAUSED);
    });
});
