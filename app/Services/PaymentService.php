<?php

namespace App\Services;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Contracts\PaymentServiceInterface;
use App\Models\Academy;
use App\Models\BaseSubscription;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Services\Payment\AcademyPaymentGatewayFactory;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentMethodService;
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
        private AcademyPaymentGatewayFactory $gatewayFactory,
        private PaymentMethodService $paymentMethodService,
    ) {}

    /**
     * Process payment with the appropriate gateway.
     *
     * This is the main entry point for initiating payments.
     */
    public function processPayment(Payment $payment, array $paymentData = []): array
    {
        try {
            // Get academy and gateway using the factory for academy-aware configuration
            $academy = $payment->academy;
            $gatewayName = $payment->payment_gateway ?? config('payments.default', 'paymob');

            // Use factory to get academy-configured gateway, fallback to default gateway manager
            $gateway = $academy
                ? $this->gatewayFactory->getGateway($academy, $gatewayName)
                : $this->gatewayManager->driver($gatewayName);

            // Check if gateway is configured
            if (! $gateway->isConfigured()) {
                throw PaymentException::notConfigured($gatewayName);
            }

            // Log the attempt
            PaymentAuditLog::logAttempt($payment, $gatewayName);

            // Determine the correct webhook URL based on gateway
            $webhookUrl = match ($gatewayName) {
                'easykash' => route('webhooks.easykash'),
                default => route('webhooks.paymob'),
            };

            // Get subdomain for route generation
            $subdomain = $academy?->subdomain ?? 'itqan-academy';

            // For EasyKash, use the global callback (handles tenant routing internally)
            $successUrl = $paymentData['success_url'] ?? ($gatewayName === 'easykash'
                ? route('payments.easykash.callback')
                : route('payments.callback', ['subdomain' => $subdomain, 'payment' => $payment->id]));

            $cancelUrl = $paymentData['cancel_url'] ?? route('payments.failed', ['subdomain' => $subdomain, 'payment' => $payment->id]);

            // Create payment intent
            $intent = PaymentIntent::fromPayment($payment, array_merge($paymentData, [
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'webhook_url' => $webhookUrl,
            ]));

            // Process with gateway
            $result = $gateway->createPaymentIntent($intent);

            // Update payment record
            $this->updatePaymentFromResult($payment, $result, $gatewayName);

            return $this->formatResultAsArray($result, $gateway);
        } catch (PaymentException $e) {
            Log::error('Payment processing error', [
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
            Log::error('Database error during payment processing', [
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
            Log::error('Invalid payment data', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'بيانات الدفع غير صحيحة',
                'error_code' => 'INVALID_DATA',
            ];
        } catch (\Throwable $e) {
            Log::critical('Unexpected payment processing error', [
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
     * Accepts either:
     * 1. A Payment object - processes the existing payment record
     * 2. A BaseSubscription + renewal price - creates payment and charges saved card
     *
     * Called by HandlesSubscriptionRenewal trait for automatic renewals.
     *
     * @param  Payment|BaseSubscription  $paymentOrSubscription
     * @param  float|null  $renewalPrice  Required if passing a subscription
     */
    public function processSubscriptionRenewal(
        Payment|BaseSubscription $paymentOrSubscription,
        ?float $renewalPrice = null
    ): array {
        // Handle existing Payment object (backward compatibility)
        if ($paymentOrSubscription instanceof Payment) {
            return $this->processPaymentRenewal($paymentOrSubscription);
        }

        // Handle BaseSubscription with saved payment method
        return $this->processSubscriptionAutoRenewal($paymentOrSubscription, $renewalPrice);
    }

    /**
     * Process renewal for an existing Payment record.
     */
    protected function processPaymentRenewal(Payment $payment): array
    {
        Log::info('Processing payment renewal', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'subscription_type' => $payment->payable_type,
        ]);

        // processPayment() handles all exceptions internally and returns an array
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
    }

    /**
     * Process automatic renewal for a subscription using saved payment method.
     *
     * This method:
     * 1. Gets the student's default saved payment method
     * 2. Creates a new Payment record for the renewal
     * 3. Charges the saved payment method
     * 4. Updates the payment status based on result
     */
    protected function processSubscriptionAutoRenewal(
        BaseSubscription $subscription,
        ?float $renewalPrice
    ): array {
        // Validate renewal price
        if (! $renewalPrice || $renewalPrice <= 0) {
            $renewalPrice = $subscription->calculateRenewalPrice();
        }

        if ($renewalPrice <= 0) {
            Log::warning('Subscription renewal price is zero or negative', [
                'subscription_id' => $subscription->id,
                'renewal_price' => $renewalPrice,
            ]);

            return [
                'success' => false,
                'error' => 'مبلغ التجديد غير صالح',
                'error_code' => 'INVALID_RENEWAL_AMOUNT',
            ];
        }

        // Get student
        $student = $subscription->student;
        if (! $student) {
            Log::error('Subscription has no student', [
                'subscription_id' => $subscription->id,
            ]);

            return [
                'success' => false,
                'error' => 'لا يوجد طالب مرتبط بالاشتراك',
                'error_code' => 'NO_STUDENT',
            ];
        }

        $academy = $subscription->academy;

        // Determine the gateway to use for renewal
        $gatewayName = config('payments.default', 'paymob');

        // Get student's default saved payment method for this gateway
        $savedPaymentMethod = $this->paymentMethodService->getUsablePaymentMethod(
            $student,
            $gatewayName
        );

        if (! $savedPaymentMethod) {
            Log::warning('No saved payment method for subscription renewal', [
                'subscription_id' => $subscription->id,
                'student_id' => $student->id,
                'gateway' => $gatewayName,
            ]);

            return [
                'success' => false,
                'error' => 'لا توجد طريقة دفع محفوظة للتجديد التلقائي',
                'error_code' => 'NO_SAVED_PAYMENT_METHOD',
            ];
        }

        return DB::transaction(function () use ($subscription, $renewalPrice, $student, $academy, $gatewayName, $savedPaymentMethod) {
            try {
                // Create a new Payment record for the renewal
                $payment = Payment::create([
                    'academy_id' => $academy->id,
                    'user_id' => $student->id,
                    'payable_type' => get_class($subscription),
                    'payable_id' => $subscription->id,
                    'amount' => $renewalPrice,
                    'currency' => $subscription->currency ?? getCurrencyCode(null, $academy),
                    'status' => 'pending',
                    'payment_gateway' => $gatewayName,
                    'payment_method' => 'card',
                    'description' => 'تجديد تلقائي: '.($subscription->package_name_ar ?? 'اشتراك'),
                    'is_recurring' => true,
                    'recurring_type' => 'auto_renewal',
                    'saved_payment_method_id' => $savedPaymentMethod->id,
                ]);

                Log::info('Created renewal payment record', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $renewalPrice,
                    'saved_payment_method_id' => $savedPaymentMethod->id,
                ]);

                // Charge the saved payment method
                $result = $this->paymentMethodService->chargePaymentMethod(
                    $savedPaymentMethod,
                    (int) ($renewalPrice * 100), // Convert to cents
                    $subscription->currency ?? getCurrencyCode(null, $academy),
                    [
                        'payment_id' => $payment->id,
                        'subscription_id' => $subscription->id,
                        'subscription_type' => $subscription->getSubscriptionType(),
                        'academy_id' => $academy->id,
                        'is_renewal' => true,
                    ]
                );

                // Update payment record based on result
                if ($result->isSuccessful()) {
                    $payment->update([
                        'status' => 'success',
                        'paid_at' => now(),
                        'transaction_id' => $result->transactionId,
                        'gateway_intent_id' => $result->transactionId,
                        'gateway_order_id' => $result->gatewayOrderId,
                        'notes' => 'Auto-renewal successful via saved card',
                    ]);

                    // Update saved payment method last used
                    $savedPaymentMethod->touchLastUsed();

                    // Send success notification
                    $this->sendPaymentNotifications($payment, $result);

                    Log::info('Subscription auto-renewal successful', [
                        'payment_id' => $payment->id,
                        'subscription_id' => $subscription->id,
                        'transaction_id' => $result->transactionId,
                    ]);

                    return [
                        'success' => true,
                        'payment_id' => $payment->id,
                        'transaction_id' => $result->transactionId,
                    ];
                }

                // Payment failed
                $payment->update([
                    'status' => 'failed',
                    'notes' => 'Auto-renewal failed: '.($result->errorMessage ?? 'Unknown error'),
                ]);

                // Send failure notification
                $this->sendPaymentNotifications($payment, $result);

                Log::warning('Subscription auto-renewal failed', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'error_code' => $result->errorCode,
                    'error' => $result->errorMessage,
                ]);

                return [
                    'success' => false,
                    'payment_id' => $payment->id,
                    'error' => $result->getDisplayError(),
                    'error_code' => $result->errorCode ?? 'CHARGE_FAILED',
                ];
            } catch (\Exception $e) {
                Log::error('Exception during subscription auto-renewal', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e; // Re-throw to trigger transaction rollback
            }
        });
    }

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
                errorMessageAr: 'لا يوجد رقم معاملة للتحقق',
            );
        }

        return $gateway->verifyPayment($transactionId, $data);
    }

    /**
     * Get available payment methods for an academy.
     */
    public function getAvailablePaymentMethods($academy = null): array
    {
        $methods = [];

        // Use factory for academy-specific gateways if academy is provided
        if ($academy) {
            $gateways = $this->gatewayFactory->getAvailableGatewaysForAcademy($academy);

            foreach ($gateways as $name => $gateway) {
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

            return $methods;
        }

        // Fallback to all configured gateways
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
     *
     * If academy is provided, uses the factory to get academy-configured gateway.
     */
    public function gateway(?string $name = null, ?Academy $academy = null): PaymentGatewayInterface
    {
        if ($academy) {
            return $this->gatewayFactory->getGateway($academy, $name);
        }

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
                    'currency' => $payment->currency ?? getCurrencyCode(null, $payment->academy),
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
                        'currency' => $payment->currency ?? getCurrencyCode(null, $payment->academy),
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
