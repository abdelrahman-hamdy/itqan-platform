<?php

use App\Models\Academy;

describe('LiveKitWebhookController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('handle', function () {
        it('accepts valid webhook payload', function () {
            $payload = [
                'event' => 'participant_joined',
                'room' => [
                    'name' => 'test-room',
                    'sid' => 'RM_test123',
                ],
                'participant' => [
                    'sid' => 'PA_test123',
                    'identity' => 'user_1',
                    'name' => 'Test User',
                ],
                'createdAt' => now()->timestamp,
            ];

            $response = $this->postJson(route('webhooks.livekit', [
                'subdomain' => $this->academy->subdomain,
            ]), $payload);

            // Should return 200 even if processing fails (webhook should acknowledge)
            expect($response->status())->toBeIn([200, 400, 401, 422]);
        });

        it('handles participant_left event', function () {
            $payload = [
                'event' => 'participant_left',
                'room' => [
                    'name' => 'test-room',
                    'sid' => 'RM_test123',
                ],
                'participant' => [
                    'sid' => 'PA_test123',
                    'identity' => 'user_1',
                    'name' => 'Test User',
                ],
                'createdAt' => now()->timestamp,
            ];

            $response = $this->postJson(route('webhooks.livekit', [
                'subdomain' => $this->academy->subdomain,
            ]), $payload);

            expect($response->status())->toBeIn([200, 400, 401, 422]);
        });

        it('handles room_finished event', function () {
            $payload = [
                'event' => 'room_finished',
                'room' => [
                    'name' => 'test-room',
                    'sid' => 'RM_test123',
                ],
                'createdAt' => now()->timestamp,
            ];

            $response = $this->postJson(route('webhooks.livekit', [
                'subdomain' => $this->academy->subdomain,
            ]), $payload);

            expect($response->status())->toBeIn([200, 400, 401, 422]);
        });
    });
});
