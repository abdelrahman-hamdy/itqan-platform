<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Throwable;
use App\Constants\DefaultAcademy;
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
use App\Services\Payment\PaymentRenewalService;
use App\Services\Payment\PaymentResultHandler;
use App\Services\Payment\PaymentStateMachine;
use App\Services\Payment\PaymentVerificationService;
use Illuminate\Support\Facades\Log;

/**
 * Orchestration service for payment operations.
 *
 * This service acts as the main entry point for payment processing,
 * delegating to specific gateway implementations via the PaymentGatewayManager.
 * Complex sub-concerns are delegated to focused sub-services.
 */
class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager,
        private PaymentStateMachine $stateMachine,
        private AcademyPaymentGatewayFactory $gatewayFactory,
        private PaymentResultHandler $resultHandler,
        private PaymentRenewalService $renewalService,
        private PaymentVerificationService $verificationService,
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
                'tap' => route('webhooks.tap'),
                default => route('webhooks.paymob'),
            };

            // Get subdomain for route generation
            $subdomain = $academy?->subdomain ?? DefaultAcademy::subdomain();

            // Generate callback URL - EasyKash and Tap use global routes, Paymob uses subdomain route
            // CRITICAL: EasyKash/Tap need absolute URLs (not relative paths) for redirect flow to work correctly
            $successUrl = $paymentData['success_url'] ?? match ($gatewayName) {
                'easykash' => url('/payments/easykash/callback'),
                'tap' => url('/payments/tap/callback'),
                default => route('payments.callback', ['subdomain' => $subdomain, 'payment' => $payment->id]),
            };

            $cancelUrl = $paymentData['cancel_url'] ?? route('payments.failed', ['subdomain' => $subdomain, 'paymentId' => $payment->id]);

            // Create payment intent
            $intent = PaymentIntent::fromPayment($payment, array_merge($paymentData, [
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'webhook_url' => $webhookUrl,
            ]));

            // Process with gateway
            $result = $gateway->createPaymentIntent($intent);

            // Update payment record and send notifications
            $this->resultHandler->updatePaymentFromResult($payment, $result, $gatewayName);

            return $this->resultHandler->formatResultAsArray($result, $gateway);
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
        } catch (QueryException $e) {
            Log::error('Database error during payment processing', [
                'payment_id' => $payment->id,
                'gateway' => $payment->payment_gateway,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [
                'success' => false,
                'error' => __('payments.service.database_error'),
                'error_code' => 'PAYMENT_FAILED',
            ];
        } catch (InvalidArgumentException $e) {
            Log::error('Invalid payment data', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => __('payments.service.invalid_data'),
                'error_code' => 'PAYMENT_FAILED',
            ];
        } catch (Throwable $e) {
            Log::critical('Unexpected payment processing error', [
                'payment_id' => $payment->id,
                'gateway' => $payment->payment_gateway,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);

            return [
                'success' => false,
                'error' => __('payments.service.unexpected_processing_error'),
                'error_code' => 'PAYMENT_FAILED',
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
        return $this->renewalService->processSubscriptionAutoRenewal($paymentOrSubscription, $renewalPrice);
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
     * Verify a payment with the gateway.
     */
    public function verifyPayment(Payment $payment, array $data = []): PaymentResult
    {
        return $this->verificationService->verifyPayment($payment, $data);
    }

    /**
     * Process a refund for a payment.
     */
    public function refund(Payment $payment, ?int $amountInCents = null, ?string $reason = null): PaymentResult
    {
        return $this->verificationService->refund($payment, $amountInCents, $reason);
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
                        'name' => $this->resultHandler->getMethodDisplayName($method),
                        'icon' => $this->resultHandler->getMethodIcon($method),
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
                    'name' => $this->resultHandler->getMethodDisplayName($method),
                    'icon' => $this->resultHandler->getMethodIcon($method),
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
}
