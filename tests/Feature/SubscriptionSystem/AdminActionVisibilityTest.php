<?php

declare(strict_types=1);

use App\Constants\PauseReason;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Filament\Resources\QuranSubscriptionResource;
use App\Models\QuranSubscription;
use Filament\Actions\Action;

/**
 * Smokes the Filament admin action visibility predicates without booting a
 * browser. Each test instantiates the relevant `Action` via reflection
 * (the factory methods on the trait are `protected static`) and asserts
 * that the visibility closure returns the expected value for a given
 * subscription state.
 *
 * Why this matters: the Phase 2 fix is *only* a UI-visibility change.
 * The model methods `pause()` and `resume()` are unchanged. So the
 * regression risk lives entirely in the predicate closures defined in
 * `app/Filament/Shared/Traits/HasSubscriptionActions.php`.
 *
 * See docs/subscription-behavior-spec.md §3.J.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

/**
 * Helper: invoke a `protected static` action factory on the trait via
 * reflection. Returns the configured `Filament\Actions\Action` instance.
 */
function getProtectedAction(string $methodName): Action
{
    $method = (new ReflectionClass(QuranSubscriptionResource::class))
        ->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invoke(null);
}

/**
 * Helper: bind a record onto a Filament Action and ask it whether it would
 * render. `record()` populates the action's record so that the visibility
 * closure (which usually has signature `fn (BaseSubscription $record) =>
 * ...`) receives it through Filament's evaluator-based DI.
 */
function isActionVisibleFor(Action $action, mixed $record): bool
{
    $action->record($record);

    return $action->isVisible();
}

describe('Resume action visibility (the Phase 2 gate)', function () {
    it('J2a — Resume is HIDDEN on auto-paused (END_OF_PERIOD) subscriptions', function () {
        $subscription = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->autoPaused()
            ->create();

        $action = getProtectedAction('getResumeAction');

        expect(isActionVisibleFor($action, $subscription))->toBeFalse();
    });

    it('J2b — Resume is VISIBLE on manually-paused subscriptions', function () {
        $subscription = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->manuallyPaused('الطالب مسافر')
            ->create();

        $action = getProtectedAction('getResumeAction');

        expect(isActionVisibleFor($action, $subscription))->toBeTrue();
    });

    it('J2c — Resume is HIDDEN on ACTIVE subscriptions (only PAUSED can resume)', function () {
        $subscription = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();

        $action = getProtectedAction('getResumeAction');

        expect(isActionVisibleFor($action, $subscription))->toBeFalse();
    });
});

describe('Reactivate action visibility', function () {
    it('J4 — Reactivate is visible only on CANCELLED subscriptions', function () {
        $cancelled = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->cancelled()
            ->create();
        $active = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();
        $autoPaused = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->autoPaused()
            ->create();

        $action = getProtectedAction('getReactivateAction');

        expect(isActionVisibleFor($action, $cancelled))->toBeTrue();
        expect(isActionVisibleFor($action, $active))->toBeFalse();
        expect(isActionVisibleFor($action, $autoPaused))->toBeFalse();
    });
});

describe('ConfirmPayment action visibility', function () {
    it('J6 — ConfirmPayment is hidden when payment_status === PAID', function () {
        $paid = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create(['payment_status' => SubscriptionPaymentStatus::PAID]);

        $pending = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->pending()
            ->create(['payment_status' => SubscriptionPaymentStatus::PENDING]);

        $failed = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->pending()
            ->create(['payment_status' => SubscriptionPaymentStatus::FAILED]);

        $action = getProtectedAction('getConfirmPaymentAction');

        expect(isActionVisibleFor($action, $paid))->toBeFalse();
        expect(isActionVisibleFor($action, $pending))->toBeTrue();
        expect(isActionVisibleFor($action, $failed))->toBeTrue();
    });
});

describe('Data invariant for the visibility predicate', function () {
    it('I10 — auto-paused subscriptions always have pause_reason = END_OF_PERIOD (the discriminator)', function () {
        $subscription = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->autoPaused()
            ->create();

        // Spec invariant I10: anything in PAUSED with END_OF_PERIOD MUST
        // have Resume hidden. Manual pauses keep Resume.
        expect($subscription->status)->toBe(SessionSubscriptionStatus::PAUSED);
        expect($subscription->pause_reason)->toBe(PauseReason::END_OF_PERIOD);
    });

    it('I10 — manually-paused subscriptions never use the END_OF_PERIOD reason', function () {
        $subscription = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->manuallyPaused('سفر')
            ->create();

        expect($subscription->status)->toBe(SessionSubscriptionStatus::PAUSED);
        expect($subscription->pause_reason)->not->toBe(PauseReason::END_OF_PERIOD);
    });
});
