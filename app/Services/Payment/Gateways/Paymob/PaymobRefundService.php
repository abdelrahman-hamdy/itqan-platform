<?php

namespace App\Services\Payment\Gateways\Paymob;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\DTOs\PaymentResult;

/**
 * Handles refunds, voids, and transaction inquiries for Paymob.
 */
class PaymobRefundService
{
    public function __construct(
        protected PaymobApiClient $client,
        protected array $config
    ) {}

    /**
     * Process a refund.
     */
    public function refund(string $transactionId, ?int $amountInCents = null, ?string $reason = null): PaymentResult
    {
        try {
            $requestBody = [
                'transaction_id' => $transactionId,
            ];

            if ($amountInCents !== null) {
                $requestBody['amount_cents'] = $amountInCents;
            }

            $response = $this->client->request('POST', '/api/acceptance/void_refund/refund', $requestBody, [
                'Authorization' => 'Token '.$this->config['secret_key'],
            ]);

            if ($response['success'] && ($response['data']['success'] ?? false)) {
                $data = $response['data'];

                return PaymentResult::success(
                    transactionId: (string) ($data['id'] ?? $transactionId),
                    rawResponse: $data,
                    metadata: [
                        'refund_id' => $data['id'] ?? null,
                        'refunded_amount_cents' => $data['amount_cents'] ?? $amountInCents,
                    ],
                );
            }

            return PaymentResult::failed(
                errorCode: 'REFUND_FAILED',
                errorMessage: $response['data']['message'] ?? 'Refund failed',
                errorMessageAr: __('payments.paymob.refund_failed'),
                transactionId: $transactionId,
                rawResponse: $response['data'] ?? [],
            );
        } catch (Exception $e) {
            Log::channel('payments')->error('Paymob refund exception', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            return PaymentResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.paymob.refund_error'),
                transactionId: $transactionId,
            );
        }
    }

    /**
     * Check if partial refunds are supported.
     */
    public function supportsPartialRefunds(): bool
    {
        return true;
    }

    /**
     * Get refund window in days.
     */
    public function getRefundWindow(): ?int
    {
        return 180;
    }

    /**
     * Check if void operations are supported.
     */
    public function supportsVoid(): bool
    {
        return true;
    }

    /**
     * Get the time window for voiding transactions.
     */
    public function getVoidWindow(): ?int
    {
        return 24;
    }

    /**
     * Void an authorized/pending transaction.
     */
    public function void(string $transactionId, ?string $reason = null): PaymentResult
    {
        try {
            $response = $this->client->request('POST', '/api/acceptance/void_refund/void', [
                'transaction_id' => $transactionId,
            ], [
                'Authorization' => 'Token '.$this->config['secret_key'],
            ]);

            if ($response['success'] && ($response['data']['success'] ?? false)) {
                $data = $response['data'];

                return PaymentResult::success(
                    transactionId: (string) ($data['id'] ?? $transactionId),
                    rawResponse: $data,
                    metadata: [
                        'void_id' => $data['id'] ?? null,
                        'voided_amount_cents' => $data['amount_cents'] ?? null,
                    ],
                );
            }

            return PaymentResult::failed(
                errorCode: 'VOID_FAILED',
                errorMessage: $response['data']['message'] ?? 'Void failed',
                errorMessageAr: __('payments.paymob.void_failed'),
                transactionId: $transactionId,
                rawResponse: $response['data'] ?? [],
            );
        } catch (Exception $e) {
            Log::channel('payments')->error('Paymob void exception', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            return PaymentResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.paymob.void_error'),
                transactionId: $transactionId,
            );
        }
    }

    /**
     * Check if a specific transaction can be voided.
     */
    public function canVoid(string $transactionId, callable $verifyPaymentCallback): bool
    {
        try {
            $result = $verifyPaymentCallback($transactionId);

            if (! $result->isSuccessful()) {
                return false;
            }

            $txn = $result->rawResponse;

            if ($txn['is_voided'] ?? false) {
                return false;
            }
            if ($txn['is_refunded'] ?? false) {
                return false;
            }

            if (isset($txn['created_at'])) {
                $createdAt = Carbon::parse($txn['created_at']);
                $hoursSinceCreation = now()->diffInHours($createdAt);

                if ($hoursSinceCreation > 24) {
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get detailed transaction information (alias for verifyPayment).
     */
    public function inquire(string $transactionId, callable $verifyPaymentCallback): PaymentResult
    {
        return $verifyPaymentCallback($transactionId);
    }

    /**
     * Get transaction by order ID.
     */
    public function getTransactionByOrderId(string $orderId): ?array
    {
        try {
            $authToken = $this->client->getAuthToken();
            if (! $authToken) {
                return null;
            }

            $response = $this->client->request('GET', "/api/ecommerce/orders/{$orderId}", [], [
                'Authorization' => "Bearer {$authToken}",
            ]);

            if ($response['success']) {
                return $response['data'];
            }

            return null;
        } catch (Exception $e) {
            Log::channel('payments')->error('Paymob order inquiry exception', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
