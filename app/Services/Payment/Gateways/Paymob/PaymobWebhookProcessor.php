<?php

namespace App\Services\Payment\Gateways\Paymob;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\DTOs\WebhookPayload;

/**
 * Handles webhook signature verification and payload parsing for Paymob.
 */
class PaymobWebhookProcessor
{
    public function __construct(protected array $config) {}

    /**
     * Verify webhook signature using HMAC.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $hmacSecret = $this->getWebhookSecret();
        if (empty($hmacSecret)) {
            Log::channel('payments')->warning('Paymob HMAC secret not configured');

            return false;
        }

        $receivedHmac = $request->query('hmac') ?? $request->header('Hmac');
        if (empty($receivedHmac)) {
            Log::channel('payments')->warning('No HMAC received in webhook');

            return false;
        }

        $data = $request->all();
        $obj = $data['obj'] ?? $data;

        $hmacString = $this->buildHmacString($obj);
        $calculatedHmac = hash_hmac('sha512', $hmacString, $hmacSecret);

        $isValid = hash_equals($calculatedHmac, $receivedHmac);

        if (! $isValid) {
            Log::channel('payments')->warning('Paymob HMAC verification failed', [
                'received_prefix' => substr($receivedHmac, 0, 16).'...',
                'calculated_prefix' => substr($calculatedHmac, 0, 16).'...',
            ]);
        }

        return $isValid;
    }

    /**
     * Parse webhook payload into standardized format.
     */
    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        return WebhookPayload::fromPaymob($request->all());
    }

    /**
     * Get webhook secret for signature verification.
     */
    public function getWebhookSecret(): string
    {
        return $this->config['hmac_secret'] ?? '';
    }

    /**
     * Get supported webhook event types.
     */
    public function getSupportedWebhookEvents(): array
    {
        return ['TRANSACTION', 'REFUND', 'VOIDED', 'TOKEN'];
    }

    /**
     * Build HMAC verification string in Paymob's required order.
     */
    private function buildHmacString(array $obj): string
    {
        $fields = [
            'amount_cents' => $obj['amount_cents'] ?? '',
            'created_at' => $obj['created_at'] ?? '',
            'currency' => $obj['currency'] ?? '',
            'error_occured' => $obj['error_occured'] ?? $obj['error_occurred'] ?? 'false',
            'has_parent_transaction' => $obj['has_parent_transaction'] ?? 'false',
            'id' => $obj['id'] ?? '',
            'integration_id' => $obj['integration_id'] ?? '',
            'is_3d_secure' => $obj['is_3d_secure'] ?? 'false',
            'is_auth' => $obj['is_auth'] ?? 'false',
            'is_capture' => $obj['is_capture'] ?? 'false',
            'is_refunded' => $obj['is_refunded'] ?? 'false',
            'is_standalone_payment' => $obj['is_standalone_payment'] ?? 'true',
            'is_voided' => $obj['is_voided'] ?? 'false',
            'order_id' => $obj['order']['id'] ?? $obj['order_id'] ?? '',
            'owner' => $obj['owner'] ?? '',
            'pending' => $obj['pending'] ?? 'false',
            'source_data_pan' => $obj['source_data']['pan'] ?? '',
            'source_data_sub_type' => $obj['source_data']['sub_type'] ?? '',
            'source_data_type' => $obj['source_data']['type'] ?? '',
            'success' => $obj['success'] ?? 'false',
        ];

        $values = array_map(function ($value) {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            return (string) $value;
        }, $fields);

        return implode('', $values);
    }
}
