<?php

namespace App\Services;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Contracts\PaymentServiceInterface;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestration service for payment operations.
 *
 * This service acts as the main entry point for payment processing,
 * delegating to specific gateway implementations via the PaymentGatewayManager.
 */
class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager,
        private PaymentStateMachine $stateMachine,
        private NotificationService $notificationService,
    ) {}

    /**
     * Process payment with the appropriate gateway.
     *
     * This is the main entry point for initiating payments.
     */
    public function processPayment(Payment $payment, array $paymentData = []): array
    {
        try {
            // Get the gateway
            $gatewayName = $payment->payment_gateway ?? config('payments.default', 'paymob');
            $gateway = $this->gatewayManager->driver($gatewayName);

            // Check if gateway is configured
            if (! $gateway->isConfigured()) {
                throw PaymentException::notConfigured($gatewayName);
            }

            // Log the attempt
            PaymentAuditLog::logAttempt($payment, $gatewayName);

            // Create payment intent
            $intent = PaymentIntent::fromPayment($payment, array_merge($paymentData, [
                'success_url' => $paymentData['success_url'] ?? route('payments.callback', ['payment' => $payment->id]),
                'cancel_url' => $paymentData['cancel_url'] ?? route('payments.failed', ['payment' => $payment->id]),
                'webhook_url' => route('webhooks.paymob'),
            ]));

            // Process with gateway
            $result = $gateway->createPaymentIntent($intent);

            // Update payment record
            $this->updatePaymentFromResult($payment, $result, $gatewayName);

            return $this->formatResultAsArray($result, $gateway);
        } catch (PaymentException $e) {
            Log::channel('payments')->error('Payment processing error', [
                'payment_id' => $payment->id,
                'gateway' => $payment->payment_gateway,
                'error_code' => $e->getErrorCode(),
                'error' => $e->getMessage(),
            ]);

            PaymentAuditLog::logAttempt($payment, $payment->payment_gateway ?? 'unknown', $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getErrorMessageAr(),
                'error_code' => $e->getErrorCode(),
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            Log::channel('payments')->error('Database error during payment processing', [
                'payment_id' => $payment->id,
                'gateway' => $payment->payment_gateway,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [
                'success' => false,
                'error' => 'خطأ في قاعدة البيانات',
                'error_code' => 'DATABASE_ERROR',
            ];
        } catch (\InvalidArgumentException $e) {
            Log::channel('payments')->error('Invalid payment data', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'بيانات الدفع غير صحيحة',
                'error_code' => 'INVALID_DATA',
            ];
        } catch (\Throwable $e) {
            Log::channel('payments')->critical('Unexpected payment processing error', [
                'payment_id' => $payment->id,
                'gateway' => $payment->payment_gateway,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);

            return [
                'success' => false,
                'error' => 'حدث خطأ غير متوقع أثناء معالجة الدفع',
                'error_code' => 'UNEXPECTED_ERROR',
            ];
        }
    }

    /**
     * Process subscription renewal payment.
     *
     * Called by SubscriptionRenewalService for automatic renewals.
     */
    public function processSubscriptionRenewal(Payment $payment): array
    {
        Log::channel('payments')->info('Processing subscription renewal', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'subscription_type' => $payment->payable_type,
        ]);

        try {
            // For renewals, we typically use stored payment method or require re-authentication
            // For now, create a new payment intent that requires user action
            $result = $this->processPayment($payment, [
                'is_renewal' => true,
            ]);

            // If successful or pending, the subscription service will handle activation
            if ($result['success'] ?? false) {
                $payment->update([
                    'notes' => ($payment->notes ?? '')."\nAuto-renewal processed",
                ]);
            }

            return $result;
        } catch (PaymentException $e) {
            Log::channel('payments')->error('Payment error during subscription renewal', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'code' => $e->getErrorCode(),
            ]);

            return [
                'success' => false,
                'error' => $e->getErrorMessageAr(),
                'error_code' => $e->getErrorCode(),
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            Log::channel('payments')->error('Database error during subscription renewal', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'خطأ في قاعدة البيانات',
                'error_code' => 'DATABASE_ERROR',
            ];
        } catch (\Throwable $e) {
            Log::channel('payments')->critical('Unexpected error during subscription renewal', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);

            return [
                'success' => false,
                'error' => 'فشل في تجديد الاشتراك تلقائياً',
                'error_code' => 'RENEWAL_FAILED',
            ];
        }
    }

    /**
     * Verify a payment with the gateway.
     */
    public function verifyPayment(Payment $payment, array $data = []): PaymentResult
    {
        $gatewayName = $payment->payment_gateway ?? config('payments.default', 'paymob');
        $gateway = $this->gatewayManager->driver($gatewayName);

        $transactionId = $payment->transaction_id ?? $payment->gateway_intent_id;

        if (! $transactionId) {
            return PaymentResult::failed(
                errorCode: 'NO_TRANSACTION_ID',
                errorMessage: 'No transaction ID to verify',
                errorMessageAr: 'لا يوجد رقم معاملة للتحقق',
            );
        }

        return $gateway->verifyPayment($transactionId, $data);
    }

    /**
     * Process a refund for a payment.
     */
    public function refund(Payment $payment, ?int $amountInCents = null, ?string $reason = null): PaymentResult
    {
        $gatewayName = $payment->payment_gateway ?? config('payments.default', 'paymob');
        $gateway = $this->gatewayManager->driver($gatewayName);

        // Check if refund is allowed
        if (! $this->stateMachine->canRefund($payment->status)) {
            return PaymentResult::failed(
                errorCode: 'REFUND_NOT_ALLOWED',
                errorMessage: 'Refund not allowed for this payment status',
                errorMessageAr: 'لا يمكن الاسترداد لهذه الحالة',
            );
        }

        // Check if gateway supports refunds
        if (! $gateway instanceof \App\Contracts\Payment\SupportsRefunds) {
            return PaymentResult::failed(
                errorCode: 'REFUND_NOT_SUPPORTED',
                errorMessage: 'Gateway does not support refunds',
                errorMessageAr: 'بوابة الدفع لا تدعم الاسترداد',
            );
        }

        $transactionId = $payment->transaction_id;
        if (! $transactionId) {
            return PaymentResult::failed(
                errorCode: 'NO_TRANSACTION_ID',
                errorMessage: 'No transaction ID for refund',
                errorMessageAr: 'لا يوجد رقم معاملة للاسترداد',
            );
        }

        $result = $gateway->refund($transactionId, $amountInCents, $reason);

        if ($result->isSuccessful()) {
            $refundAmount = $amountInCents ?? (int) ($payment->amount * 100);

            DB::transaction(function () use ($payment, $refundAmount) {
                $newRefundedTotal = ($payment->refunded_amount ?? 0) + $refundAmount;
                $fullAmount = (int) ($payment->amount * 100);

                $payment->update([
                    'refunded_amount' => $newRefundedTotal,
                    'refunded_at' => now(),
                    'status' => $newRefundedTotal >= $fullAmount ? 'refunded' : 'partially_refunded',
                ]);
            });

            PaymentAuditLog::logRefund(
                payment: $payment,
                refundAmountCents: $amountInCents ?? (int) ($payment->amount * 100),
                transactionId: $result->transactionId ?? $transactionId,
            );
        }

        return $result;
    }

    /**
     * Get available payment methods for an academy.
     */
    public function getAvailablePaymentMethods($academy = null): array
    {
        $methods = [];

        foreach ($this->gatewayManager->getConfiguredGateways() as $name => $gateway) {
            foreach ($gateway->getSupportedMethods() as $method) {
                $key = $name.'_'.$method;
                $methods[$key] = [
                    'name' => $this->getMethodDisplayName($method),
                    'icon' => $this->getMethodIcon($method),
                    'gateway' => $name,
                    'method' => $method,
                ];
            }
        }

        // Add regional methods based on academy
        if ($academy) {
            // Filter or add methods based on academy region/settings
        }

        return $methods;
    }

    /**
     * Calculate fees for a payment method.
     */
    public function calculateFees(float $amount, string $paymentMethod): array
    {
        $fees = config('payments.fees', [
            'card' => 0.025,
            'wallet' => 0.02,
            'bank_transfer' => 0.01,
        ]);

        $feeRate = $fees[$paymentMethod] ?? 0.025;
        $feeAmount = round($amount * $feeRate, 2);

        return [
            'fee_rate' => $feeRate,
            'fee_amount' => $feeAmount,
            'total_with_fees' => $amount + $feeAmount,
        ];
    }

    /**
     * Get a specific gateway instance.
     */
    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        return $this->gatewayManager->driver($name);
    }

    /**
     * Update payment record from gateway result.
     */
    private function updatePaymentFromResult(Payment $payment, PaymentResult $result, string $gateway): void
    {
        $updateData = [
            'payment_gateway' => $gateway,
        ];

        if ($result->transactionId) {
            $updateData['gateway_intent_id'] = $result->transactionId;
        }

        if ($result->gatewayOrderId) {
            $updateData['gateway_order_id'] = $result->gatewayOrderId;
        }

        if ($result->clientSecret) {
            $updateData['client_secret'] = $result->clientSecret;
        }

        if ($result->redirectUrl) {
            $updateData['redirect_url'] = $result->redirectUrl;
        }

        if ($result->iframeUrl) {
            $updateData['iframe_url'] = $result->iframeUrl;
        }

        // Update status if appropriate
        if ($result->isSuccessful()) {
            $updateData['status'] = 'success';
            $updateData['paid_at'] = now();
            $updateData['transaction_id'] = $result->transactionId;
        } elseif ($result->isFailed()) {
            $updateData['status'] = 'failed';
        }

        $payment->update($updateData);

        // Send notifications after payment status is updated
        $this->sendPaymentNotifications($payment, $result);
    }

    /**
     * Send payment success/failure notifications to user
     */
    private function sendPaymentNotifications(Payment $payment, PaymentResult $result): void
    {
        try {
            // Get the user from payment
            $user = $payment->user;
            if (! $user) {
                return;
            }

            if ($result->isSuccessful()) {
                // Prepare notification data for successful payment
                $notificationData = [
                    'amount' => $payment->amount,
                    'currency' => $payment->currency ?? 'SAR',
                    'description' => $payment->description ?? 'الاشتراك',
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id ?? null,
                ];

                // Add subscription context if available
                if ($payment->payable) {
                    $notificationData['subscription_id'] = $payment->payable_id;

                    if ($payment->payable_type === 'App\\Models\\QuranSubscription') {
                        $notificationData['subscription_type'] = 'quran';
                        if ($payment->payable->quran_circle_id) {
                            $notificationData['circle_id'] = $payment->payable->quran_circle_id;
                        }
                    } elseif ($payment->payable_type === 'App\\Models\\AcademicSubscription') {
                        $notificationData['subscription_type'] = 'academic';
                    } elseif ($payment->payable_type === 'App\\Models\\CourseSubscription') {
                        $notificationData['subscription_type'] = 'course';
                    }
                }

                $this->notificationService->sendPaymentSuccessNotification($user, $notificationData);
            } elseif ($result->isFailed()) {
                // Send failure notification
                $this->notificationService->send(
                    $user,
                    \App\Enums\NotificationType::PAYMENT_FAILED,
                    [
                        'amount' => $payment->amount,
                        'currency' => $payment->currency ?? 'SAR',
                        'reason' => $result->errorMessage ?? 'فشل الدفع',
                    ],
                    '/payments',
                    ['payment_id' => $payment->id],
                    true  // Mark as important
                );
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Payment notification failed - related model not found', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send payment notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Format PaymentResult DTO as array for controller response.
     */
    private function formatResultAsArray(PaymentResult $result, PaymentGatewayInterface $gateway): array
    {
        if ($result->isSuccessful()) {
            return [
                'success' => true,
                'data' => [
                    'transaction_id' => $result->transactionId,
                    'gateway_order_id' => $result->gatewayOrderId,
                    'gateway_response' => $result->rawResponse,
                ],
            ];
        }

        if ($result->isPending()) {
            $response = [
                'success' => true,
                'pending' => true,
                'data' => [
                    'transaction_id' => $result->transactionId,
                    'client_secret' => $result->clientSecret,
                ],
            ];

            if ($result->hasRedirect()) {
                $response['requires_redirect'] = true;
                $response['redirect_url'] = $result->redirectUrl;
            }

            if ($result->hasIframe()) {
                $response['requires_iframe'] = true;
                $response['iframe_url'] = $result->iframeUrl;
            }

            return $response;
        }

        return [
            'success' => false,
            'error' => $result->getDisplayError(),
            'error_code' => $result->errorCode,
        ];
    }

    /**
     * Get display name for payment method.
     */
    private function getMethodDisplayName(string $method): string
    {
        return match ($method) {
            'card' => 'بطاقة ائتمانية',
            'wallet' => 'محفظة إلكترونية',
            'bank_transfer' => 'تحويل بنكي',
            'bank_installments' => 'تقسيط بنكي',
            default => $method,
        };
    }

    /**
     * Get icon for payment method.
     */
    private function getMethodIcon(string $method): string
    {
        return match ($method) {
            'card' => 'ri-bank-card-line',
            'wallet' => 'ri-wallet-3-line',
            'bank_transfer' => 'ri-bank-line',
            'bank_installments' => 'ri-funds-box-line',
            default => 'ri-secure-payment-line',
        };
    }
}
