<?php

namespace App\Services\Payment;

use Throwable;
use App\Constants\DefaultAcademy;
use App\Contracts\Payment\PaymentGatewayInterface;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\NotificationService;
use App\Services\Payment\DTOs\PaymentResult;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * Handles the side-effects of a PaymentResult: updating the Payment record
 * and dispatching payment success/failure notifications.
 *
 * Also provides formatting helpers that turn a PaymentResult DTO into the
 * array shape returned to callers (controllers, API, etc.).
 */
class PaymentResultHandler
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    /**
     * Update payment record from gateway result and send notifications.
     */
    public function updatePaymentFromResult(Payment $payment, PaymentResult $result, string $gateway): void
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

        // CRITICAL: Save gateway response for debugging and audit trail
        if ($result->rawResponse) {
            $updateData['gateway_response'] = $result->rawResponse;
        }

        // Update status if appropriate
        if ($result->isSuccessful()) {
            $updateData['status'] = PaymentStatus::COMPLETED;
            $updateData['payment_date'] = now();
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
     * Send payment success/failure notifications to user.
     */
    public function sendPaymentNotifications(Payment $payment, PaymentResult $result): void
    {
        try {
            // Guard against duplicate payment notifications
            if ($payment->payment_notification_sent_at) {
                Log::info('Payment notification already sent, skipping', [
                    'payment_id' => $payment->id,
                    'sent_at' => $payment->payment_notification_sent_at,
                    'result_status' => $result->isSuccessful() ? 'success' : ($result->isFailed() ? 'failed' : 'pending'),
                ]);
                return;
            }

            // Get the user from payment
            $user = $payment->user;
            if (! $user) {
                return;
            }

            if ($result->isSuccessful()) {
                // Prepare notification data for successful payment
                $notificationData = [
                    'amount' => $payment->amount,
                    'currency' => $payment->currency ?? getCurrencyCode(null, $payment->academy),
                    'description' => $payment->description ?? __('payments.service.subscription_ref'),
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id ?? null,
                    'subdomain' => $payment->academy?->subdomain ?? DefaultAcademy::subdomain(),
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

                // Mark payment notification as sent
                $payment->update(['payment_notification_sent_at' => now()]);
            } elseif ($result->isFailed()) {
                // Send failure notification
                $this->notificationService->send(
                    $user,
                    NotificationType::PAYMENT_FAILED,
                    [
                        'amount' => $payment->amount,
                        'currency' => $payment->currency ?? getCurrencyCode(null, $payment->academy),
                        'reason' => $result->errorMessage ?? __('payments.status_display.failed'),
                    ],
                    '/payments',
                    ['payment_id' => $payment->id],
                    true  // Mark as important
                );

                // Mark payment notification as sent
                $payment->update(['payment_notification_sent_at' => now()]);
            }
        } catch (ModelNotFoundException $e) {
            Log::warning('Payment notification failed - related model not found', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to send payment notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Format PaymentResult DTO as array for controller response.
     */
    public function formatResultAsArray(PaymentResult $result, PaymentGatewayInterface $gateway): array
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
    public function getMethodDisplayName(string $method): string
    {
        return match ($method) {
            'card' => __('payments.method_types.card'),
            'wallet' => __('payments.method_types.wallet'),
            'bank_transfer' => __('payments.method_types.bank_transfer'),
            'bank_installments' => __('payments.method_types.bank_installments'),
            default => $method,
        };
    }

    /**
     * Get icon for payment method.
     */
    public function getMethodIcon(string $method): string
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
