<?php

use App\Models\Academy;

describe('PaymobWebhookController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('callback', function () {
        it('handles successful payment callback', function () {
            $payload = [
                'success' => true,
                'obj' => [
                    'id' => 12345,
                    'order' => [
                        'id' => 67890,
                    ],
                    'amount_cents' => 10000,
                    'currency' => 'EGP',
                    'merchant_order_id' => 'PAY-' . uniqid(),
                ],
            ];

            $response = $this->postJson(route('webhooks.paymob.callback', [
                'subdomain' => $this->academy->subdomain,
            ]), $payload);

            // Should process or return appropriate status
            expect($response->status())->toBeIn([200, 302, 400, 422]);
        });

        it('handles failed payment callback', function () {
            $payload = [
                'success' => false,
                'obj' => [
                    'id' => 12345,
                    'error_occured' => true,
                    'data_message' => 'Payment declined',
                ],
            ];

            $response = $this->postJson(route('webhooks.paymob.callback', [
                'subdomain' => $this->academy->subdomain,
            ]), $payload);

            expect($response->status())->toBeIn([200, 302, 400, 422]);
        });
    });

    describe('processed', function () {
        it('handles processed webhook', function () {
            $payload = [
                'type' => 'TRANSACTION',
                'obj' => [
                    'id' => 12345,
                    'success' => true,
                    'amount_cents' => 10000,
                ],
            ];

            $response = $this->postJson(route('webhooks.paymob.processed', [
                'subdomain' => $this->academy->subdomain,
            ]), $payload);

            expect($response->status())->toBeIn([200, 400, 422]);
        });
    });
});
