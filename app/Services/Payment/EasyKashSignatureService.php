<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Service for verifying EasyKash webhook signatures.
 *
 * EasyKash uses HMAC-SHA512 for webhook authentication.
 * The signature is calculated using specific fields in a defined order.
 */
class EasyKashSignatureService
{
    public function __construct(
        private string $secretKey
    ) {}

    /**
     * Verify the webhook signature from EasyKash.
     */
    public function verify(Request $request): bool
    {
        if (empty($this->secretKey)) {
            Log::channel('payments')->warning('EasyKash secret key not configured');

            return false;
        }

        $payload = $request->all();
        $receivedSignature = $payload['signatureHash'] ?? '';

        if (empty($receivedSignature)) {
            Log::channel('payments')->warning('No signature hash received in EasyKash webhook');

            return false;
        }

        $calculatedSignature = $this->calculateSignature($payload);
        $isValid = hash_equals($calculatedSignature, $receivedSignature);

        if (! $isValid) {
            Log::channel('payments')->warning('EasyKash HMAC verification failed', [
                'received_prefix' => substr($receivedSignature, 0, 20),
                'calculated_prefix' => substr($calculatedSignature, 0, 20),
            ]);
        }

        return $isValid;
    }

    /**
     * Calculate the HMAC-SHA512 signature for the payload.
     *
     * EasyKash field order: ProductCode, Amount, ProductType, PaymentMethod, status, easykashRef, customerReference
     */
    public function calculateSignature(array $payload): string
    {
        $dataString = $this->buildDataString($payload);

        return hash_hmac('sha512', $dataString, $this->secretKey);
    }

    /**
     * Build the data string for HMAC calculation.
     *
     * Fields are concatenated in a specific order without separators.
     */
    private function buildDataString(array $payload): string
    {
        $fields = [
            $payload['ProductCode'] ?? '',
            $payload['Amount'] ?? '',
            $payload['ProductType'] ?? '',
            $payload['PaymentMethod'] ?? '',
            $payload['status'] ?? '',
            $payload['easykashRef'] ?? '',
            $payload['customerReference'] ?? '',
        ];

        return implode('', $fields);
    }

    /**
     * Extract payment ID and academy ID from customer reference.
     *
     * Customer reference format: {academy_id}-{payment_id}-{timestamp}
     *
     * @return array{payment_id: int|null, academy_id: int|null}
     */
    public static function parseCustomerReference(string $customerReference): array
    {
        $parts = explode('-', $customerReference);

        return [
            'academy_id' => isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : null,
            'payment_id' => isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : null,
            'timestamp' => isset($parts[2]) && is_numeric($parts[2]) ? (int) $parts[2] : null,
        ];
    }
}
