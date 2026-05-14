<?php

declare(strict_types=1);

use App\Http\Controllers\EasyKashWebhookController;
use App\Services\Subscription\Concerns\DualExecutesPayment;

/**
 * Smoke test — confirms EasyKashWebhookController is wired to the
 * DualExecutesPayment trait. The full behaviour of the trait is exercised
 * in PaymobWebhookV2DualTest (the three controllers share the same wiring,
 * we only verify the contract once).
 */
it('EasyKashWebhookController uses the DualExecutesPayment trait', function () {
    expect(class_uses_recursive(EasyKashWebhookController::class))
        ->toHaveKey(DualExecutesPayment::class);
});
