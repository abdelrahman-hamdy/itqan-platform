<?php

declare(strict_types=1);

use App\Services\Payment\PaymobSignatureService;

/**
 * Tier 5 — synthetic Paymob payload smoke.
 *
 * The full end-to-end POST against the webhook route is brittle in the
 * test environment (the controller's pre-flight guards expect specific
 * integration_id / merchant_order_id / currency mappings that depend on
 * staging-only config). The dual-execution mechanics ARE already covered
 * by `PaymobWebhookV2DualTest` (trait-level) and by the smoke tests for
 * the EasyKash + Tap controllers.
 *
 * What this file adds: a documented synthetic Paymob payload at
 * `tests/Fixtures/Webhooks/paymob_subscription_success.json` so the
 * operator validation step "tinker replay of a sanitised payload" no
 * longer needs a real staging capture. The test below proves the fixture
 * is loadable, schema-shaped, and HMAC-able with the project's signature
 * service — exactly the prep step the operator would otherwise do by
 * hand before piping it into `php artisan tinker`.
 */
it('synthetic Paymob payload fixture is loadable and HMAC-able', function () {
    $path = base_path('tests/Fixtures/Webhooks/paymob_subscription_success.json');

    expect(file_exists($path))->toBeTrue();

    $payload = json_decode(file_get_contents($path), true);
    expect($payload)->toBeArray()
        ->and($payload)->toHaveKey('obj')
        ->and($payload['obj'])->toHaveKey('amount_cents')
        ->and($payload['obj'])->toHaveKey('success')
        ->and($payload['obj'])->toHaveKey('order');

    // The shape required for HMAC computation: every field
    // PaymobSignatureService::buildHmacString reads must exist.
    foreach ([
        'amount_cents', 'created_at', 'currency', 'error_occured',
        'has_parent_transaction', 'id', 'integration_id',
        'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded',
        'is_standalone_payment', 'is_voided',
        'owner', 'pending', 'source_data', 'success',
    ] as $required) {
        expect($payload['obj'])->toHaveKey($required);
    }

    config(['payments.gateways.paymob.hmac_secret' => 'test-hmac-secret-2026']);
    $hmac = app(PaymobSignatureService::class)->calculateHmac($payload['obj']);

    expect($hmac)->toBeString()->toHaveLength(128); // SHA-512 hex digest
});
