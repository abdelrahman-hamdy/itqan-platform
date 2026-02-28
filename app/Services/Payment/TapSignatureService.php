<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for verifying Tap Payment webhook signatures.
 *
 * Tap uses a hashstring included in the webhook payload for authentication.
 * The hash is calculated from specific charge fields using the merchant's secret key.
 *
 * Two-layer verification:
 * 1. Primary: Verify the hashstring included in the webhook body.
 * 2. Fallback: If hashstring is missing, fetch the charge from Tap API to confirm.
 */
class TapSignatureService
{
    public function __construct(
        private ?string $secretKey = null
    ) {
        $this->secretKey ??= config('payments.gateways.tap.secret_key');
    }

    /**
     * Verify the webhook from Tap.
     *
     * Returns true if the webhook is authentic, false otherwise.
     */
    public function verify(Request $request): bool
    {
        if (empty($this->secretKey)) {
            Log::warning('Tap secret key not configured — cannot verify webhook');

            return false;
        }

        $payload = $request->all();

        // Primary verification: hashstring included in Tap webhook body
        $hashString = $payload['hashstring'] ?? '';
        if (! empty($hashString)) {
            $isValid = $this->verifyHash($payload, $hashString);
            if (! $isValid) {
                Log::warning('Tap hashstring verification failed', [
                    'charge_id' => $payload['id'] ?? null,
                    'received_hash_prefix' => substr($hashString, 0, 20),
                ]);
            }

            return $isValid;
        }

        // Fallback: no hashstring — verify by fetching charge from Tap API
        $chargeId = $payload['id'] ?? null;
        if (! $chargeId) {
            Log::warning('Tap webhook missing both hashstring and charge id — rejecting');

            return false;
        }

        Log::info('Tap webhook missing hashstring — falling back to API verification', [
            'charge_id' => $chargeId,
        ]);

        return $this->verifyViaApi($chargeId, $payload['status'] ?? null);
    }

    /**
     * Verify webhook hash.
     *
     * Tap's hashstring format:
     *   x_id[charge_id]x_amount[amount]x_currency[currency]x_status[status]
     *
     * The calculated HMAC-SHA256 of the above string (using the merchant's secret key)
     * should equal the hashstring sent by Tap.
     */
    public function verifyHash(array $payload, string $receivedHash): bool
    {
        $toHash = sprintf(
            'x_id%sx_amount%sx_currency%sx_status%s',
            $payload['id'] ?? '',
            $payload['amount'] ?? '',
            $payload['currency'] ?? '',
            $payload['status'] ?? '',
        );

        $calculated = hash_hmac('sha256', $toHash, $this->secretKey);

        return hash_equals($calculated, $receivedHash);
    }

    /**
     * Fallback: fetch charge from Tap API and confirm the status matches the webhook payload.
     *
     * This prevents processing forged webhooks that are missing the hashstring.
     */
    private function verifyViaApi(string $chargeId, ?string $claimedStatus): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Accept' => 'application/json',
            ])->timeout(10)->get(config('payments.gateways.tap.base_url', 'https://api.tap.company/v2')."/charges/{$chargeId}");

            if (! $response->successful()) {
                Log::warning('Tap API fallback verification failed — non-200 response', [
                    'charge_id' => $chargeId,
                    'http_status' => $response->status(),
                ]);

                return false;
            }

            $charge = $response->json();
            $apiStatus = strtoupper($charge['status'] ?? '');
            $webhookStatus = strtoupper($claimedStatus ?? '');

            // Statuses must match for the webhook to be considered authentic
            if ($apiStatus !== $webhookStatus) {
                Log::warning('Tap API fallback verification — status mismatch', [
                    'charge_id' => $chargeId,
                    'api_status' => $apiStatus,
                    'webhook_status' => $webhookStatus,
                ]);

                return false;
            }

            Log::info('Tap webhook verified via API fallback', [
                'charge_id' => $chargeId,
                'status' => $apiStatus,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Tap API fallback verification exception', [
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
