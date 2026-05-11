<?php

declare(strict_types=1);

/**
 * Pest architecture rules for the subscription subsystem.
 *
 * These are cheap, structural guardrails that complement the behavior tests.
 * They run as part of the regular test suite and fail fast if the
 * subscription invariants in docs/subscription-behavior-spec.md §2 start
 * to drift in code.
 */
arch('action visibility predicates always reference the status enum')
    ->expect('App\Filament\Shared\Traits\HasSubscriptionActions')
    ->toUse('App\Enums\SessionSubscriptionStatus');

arch('the resume-vs-extend gate references the PauseReason constant')
    ->expect('App\Filament\Shared\Traits\HasSubscriptionActions')
    ->toUse('App\Constants\PauseReason');

arch('the auto-pause cron stamps PauseReason::END_OF_PERIOD by constant')
    ->expect('App\Console\Commands\ExpireActiveSubscriptions')
    ->toUse('App\Constants\PauseReason');

arch('subscription services do not use string-based type checks')
    ->expect('App\Services\Subscription')
    ->not->toUse('str_contains');

arch('the PauseReason class is final')
    ->expect('App\Constants\PauseReason')
    ->toBeFinal();
