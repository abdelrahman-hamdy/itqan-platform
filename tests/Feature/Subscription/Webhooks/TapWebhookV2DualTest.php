<?php

declare(strict_types=1);

use App\Http\Controllers\TapWebhookController;
use App\Services\Subscription\Concerns\DualExecutesPayment;

/**
 * Smoke test — confirms TapWebhookController is wired to the
 * DualExecutesPayment trait. Full behaviour exercised in PaymobWebhookV2DualTest.
 */
it('TapWebhookController uses the DualExecutesPayment trait', function () {
    expect(class_uses_recursive(TapWebhookController::class))
        ->toHaveKey(DualExecutesPayment::class);
});
