<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Enums\SessionStatus;

/**
 * Service for verifying Paymob webhook signatures.
 *
 * Implements HMAC SHA512 verification according to Paymob's specifications.
 */
class PaymobSignatureService
{
    private string $hmacSecret;

    public function __construct(?string $hmacSecret = null)
    {
        $this->hmacSecret = $hmacSecret ?? config('payments.gateways.paymob.hmac_secret') ?? '';
    }

    /**
     * Verify the HMAC signature from a Paymob webhook request.
     */
    public function verify(Request $request): bool
    {
        if (empty($this->hmacSecret)) {
            Log::channel('payments')->warning('Paymob HMAC secret not configured');
            return false;
        }

        // Get HMAC from query string or header
        $receivedHmac = $request->query('hmac') ?? $request->header('Hmac');

        if (empty($receivedHmac)) {
            Log::channel('payments')->warning('No HMAC received in Paymob webhook');
            return false;
        }

        // Get the transaction object
        $data = $request->all();
        $obj = $data['obj'] ?? $data;

        // Calculate expected HMAC
        $calculatedHmac = $this->calculateHmac($obj);

        // Timing-safe comparison
        $isValid = hash_equals($calculatedHmac, $receivedHmac);

        if (! $isValid) {
            Log::channel('payments')->warning('Paymob HMAC verification failed', [
                'received_hmac' => substr($receivedHmac, 0, 16) . '...',
                'calculated_hmac' => substr($calculatedHmac, 0, 16) . '...',
                'transaction_id' => $obj['id'] ?? 'unknown',
            ]);
        }

        return $isValid;
    }

    /**
     * Calculate HMAC for the transaction object.
     *
     * Paymob requires specific field ordering for HMAC calculation.
     */
    public function calculateHmac(array $obj): string
    {
        $hmacString = $this->buildHmacString($obj);

        return hash_hmac('sha512', $hmacString, $this->hmacSecret);
    }

    /**
     * Build the HMAC string in Paymob's required field order.
     *
     * Order is critical - must match Paymob's documentation exactly.
     */
    private function buildHmacString(array $obj): string
    {
        // Extract nested values safely
        $orderId = $obj['order']['id'] ?? $obj['order_id'] ?? '';
        $sourceDataPan = $obj['source_data']['pan'] ?? '';
        $sourceDataSubType = $obj['source_data']['sub_type'] ?? '';
        $sourceDataType = $obj['source_data']['type'] ?? '';

        // Build string in Paymob's specific order (alphabetical by key)
        $values = [
            $this->stringify($obj['amount_cents'] ?? ''),
            $this->stringify($obj['created_at'] ?? ''),
            $this->stringify($obj['currency'] ?? ''),
            $this->stringify($obj['error_occured'] ?? $obj['error_occurred'] ?? false),
            $this->stringify($obj['has_parent_transaction'] ?? false),
            $this->stringify($obj['id'] ?? ''),
            $this->stringify($obj['integration_id'] ?? ''),
            $this->stringify($obj['is_3d_secure'] ?? false),
            $this->stringify($obj['is_auth'] ?? false),
            $this->stringify($obj['is_capture'] ?? false),
            $this->stringify($obj['is_refunded'] ?? false),
            $this->stringify($obj['is_standalone_payment'] ?? true),
            $this->stringify($obj['is_voided'] ?? false),
            $this->stringify($orderId),
            $this->stringify($obj['owner'] ?? ''),
            $this->stringify($obj['pending'] ?? false),
            $this->stringify($sourceDataPan),
            $this->stringify($sourceDataSubType),
            $this->stringify($sourceDataType),
            $this->stringify($obj['success'] ?? false),
        ];

        return implode('', $values);
    }

    /**
     * Convert value to string for HMAC calculation.
     *
     * Booleans need special handling: true -> "true", false -> "false"
     */
    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * Verify amount matches expected value.
     *
     * Prevents amount tampering attacks.
     */
    public function verifyAmount(array $webhookData, int $expectedAmountCents): bool
    {
        $obj = $webhookData['obj'] ?? $webhookData;
        $receivedAmount = (int) ($obj['amount_cents'] ?? 0);

        if ($receivedAmount !== $expectedAmountCents) {
            Log::channel('payments')->error('Paymob amount mismatch', [
                'expected' => $expectedAmountCents,
                'received' => $receivedAmount,
                'transaction_id' => $obj['id'] ?? 'unknown',
            ]);
            return false;
        }

        return true;
    }

    /**
     * Extract payment ID from merchant order ID.
     *
     * Format: ACADEMY_ID-PAYMENT_ID-TIMESTAMP
     */
    public function extractPaymentId(array $webhookData): ?int
    {
        $obj = $webhookData['obj'] ?? $webhookData;
        $merchantOrderId = $obj['merchant_order_id']
            ?? $obj['order']['merchant_order_id']
            ?? null;

        if (! $merchantOrderId || ! str_contains($merchantOrderId, '-')) {
            return null;
        }

        $parts = explode('-', $merchantOrderId);

        return isset($parts[1]) ? (int) $parts[1] : null;
    }

    /**
     * Extract academy ID from merchant order ID.
     */
    public function extractAcademyId(array $webhookData): ?int
    {
        $obj = $webhookData['obj'] ?? $webhookData;
        $merchantOrderId = $obj['merchant_order_id']
            ?? $obj['order']['merchant_order_id']
            ?? null;

        if (! $merchantOrderId || ! str_contains($merchantOrderId, '-')) {
            return null;
        }

        $parts = explode('-', $merchantOrderId);

        return isset($parts[0]) ? (int) $parts[0] : null;
    }
}
