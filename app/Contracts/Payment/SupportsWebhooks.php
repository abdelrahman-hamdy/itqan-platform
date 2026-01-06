<?php

namespace App\Contracts\Payment;

use App\Services\Payment\DTOs\WebhookPayload;
use Illuminate\Http\Request;

/**
 * Interface for payment gateways that support webhook notifications.
 *
 * Gateways implementing this interface can receive and process
 * asynchronous payment status updates from the provider.
 */
interface SupportsWebhooks
{
    /**
     * Verify the webhook signature/authenticity.
     *
     * @param  Request  $request  The incoming webhook request
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Parse the webhook request into a standardized payload.
     *
     * @param  Request  $request  The incoming webhook request
     */
    public function parseWebhookPayload(Request $request): WebhookPayload;

    /**
     * Get the webhook secret/key for signature verification.
     */
    public function getWebhookSecret(): string;

    /**
     * Get the expected webhook event types this gateway supports.
     *
     * @return array<string> e.g., ['TRANSACTION', 'REFUND', 'VOID']
     */
    public function getSupportedWebhookEvents(): array;
}
