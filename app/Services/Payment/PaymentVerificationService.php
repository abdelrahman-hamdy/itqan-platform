<?php

namespace App\Services\Payment;

use Exception;
use App\Contracts\Payment\SupportsRefunds;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Support\Facades\Log;

/**
 * Handles payment verification and refund operations by delegating to the
 * appropriate gateway implementation.
 */
class PaymentVerificationService
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager,
        private AcademyPaymentGatewayFactory $gatewayFactory,
    ) {}

    /**
     * Verify a payment with the gateway.
     */
    public function verifyPayment(Payment $payment, array $data = []): PaymentResult
    {
        $academy = $payment->academy;
        $gatewayName = $payment->payment_gateway ?? config('payments.default', 'paymob');

        // Use factory to get academy-configured gateway, fallback to default gateway manager
        $gateway = $academy
            ? $this->gatewayFactory->getGateway($academy, $gatewayName)
            : $this->gatewayManager->driver($gatewayName);

        $transactionId = $payment->transaction_id ?? $payment->gateway_intent_id;

        if (! $transactionId) {
            return PaymentResult::failed(
                errorCode: 'NO_TRANSACTION_ID',
                errorMessage: 'No transaction ID to verify',
                errorMessageAr: __('payments.service.no_transaction_id'),
            );
        }

        return $gateway->verifyPayment($transactionId, $data);
    }

    /**
     * Process a refund for a payment.
     */
    public function refund(Payment $payment, ?int $amountInCents = null, ?string $reason = null): PaymentResult
    {
        try {
            $academy = $payment->academy;
            $gatewayName = $payment->payment_gateway ?? config('payments.default', 'paymob');

            // Use factory to get academy-configured gateway, fallback to default gateway manager
            $gateway = $academy
                ? $this->gatewayFactory->getGateway($academy, $gatewayName)
                : $this->gatewayManager->driver($gatewayName);

            // Check if gateway supports refunds
            if (! $gateway instanceof SupportsRefunds) {
                return PaymentResult::failed(
                    errorCode: 'REFUNDS_NOT_SUPPORTED',
                    errorMessage: "Gateway {$gatewayName} does not support refunds",
                    errorMessageAr: __('payments.service.refund_not_supported'),
                );
            }

            $transactionId = $payment->transaction_id ?? $payment->gateway_intent_id;

            if (! $transactionId) {
                return PaymentResult::failed(
                    errorCode: 'NO_TRANSACTION_ID',
                    errorMessage: 'No transaction ID to refund',
                    errorMessageAr: __('payments.service.no_refund_transaction'),
                );
            }

            // If amount is not specified, refund full amount
            if ($amountInCents === null) {
                $amountInCents = (int) round($payment->amount * 100);
            }

            // Log the refund attempt
            PaymentAuditLog::logAttempt($payment, $gatewayName, "Refund attempt: {$amountInCents} cents");

            $result = $gateway->refund($transactionId, $amountInCents, $reason);

            if ($result->isSuccessful()) {
                // Update payment status
                $payment->update([
                    'status' => 'refunded',
                    'refund_reason' => $reason,
                    'refunded_at' => now(),
                    'refund_amount' => $amountInCents / 100,
                ]);

                Log::info('Payment refunded successfully', [
                    'payment_id' => $payment->id,
                    'amount_cents' => $amountInCents,
                    'reason' => $reason,
                ]);
            } else {
                Log::warning('Payment refund failed', [
                    'payment_id' => $payment->id,
                    'error_code' => $result->errorCode,
                    'error' => $result->errorMessage,
                ]);
            }

            return $result;
        } catch (Exception $e) {
            Log::error('Exception during payment refund', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failed(
                errorCode: 'REFUND_ERROR',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.service.refund_error'),
            );
        }
    }
}
