<?php

namespace App\Services\Payment;

use Exception;
use App\Enums\PaymentStatus;
use App\Models\BaseSubscription;
use App\Models\Payment;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\PaymentMethodService;
use App\Services\Payment\PaymentResultHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles subscription auto-renewal payments using saved payment methods.
 *
 * This service owns the logic for creating a new Payment record on behalf of
 * an auto-renewing subscription and charging the student's saved card.
 * Notification side-effects are delegated to PaymentResultHandler.
 */
class PaymentRenewalService
{
    public function __construct(
        private PaymentMethodService $paymentMethodService,
        private PaymentResultHandler $resultHandler,
    ) {}

    /**
     * Process automatic renewal for a subscription using saved payment method.
     *
     * This method:
     * 1. Gets the student's default saved payment method for this gateway
     * 2. Creates a new Payment record for the renewal
     * 3. Charges the saved payment method
     * 4. Updates the payment status based on result
     */
    public function processSubscriptionAutoRenewal(
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
                'error' => __('payments.service.invalid_renewal_amount'),
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
                'error' => __('payments.service.no_student_linked'),
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
                'error' => __('payments.service.no_saved_method'),
                'error_code' => 'NO_SAVED_PAYMENT_METHOD',
            ];
        }

        return DB::transaction(function () use ($subscription, $renewalPrice, $student, $academy, $gatewayName, $savedPaymentMethod) {
            try {
                // Detect purchase source from session (for mobile-initiated renewals)
                $purchaseSource = session('purchase_source', 'web');

                // Create metadata with purchase source tracking
                $metadata = [
                    'purchase_source' => $purchaseSource,
                    'is_auto_renewal' => true,
                    'user_agent' => request()?->userAgent(),
                    'ip_address' => request()?->ip(),
                ];

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
                    'description' => __('payments.service.auto_renewal_description', ['package' => $subscription->package_name_ar ?? __('payments.service.subscription_label')]),
                    'is_recurring' => true,
                    'recurring_type' => 'auto_renewal',
                    'saved_payment_method_id' => $savedPaymentMethod->id,
                    'metadata' => json_encode($metadata),
                ]);

                Log::info('Created renewal payment record', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $renewalPrice,
                    'saved_payment_method_id' => $savedPaymentMethod->id,
                    'purchase_source' => $purchaseSource,
                ]);

                // Charge the saved payment method
                $result = $this->paymentMethodService->chargePaymentMethod(
                    $savedPaymentMethod,
                    (int) round($renewalPrice * 100), // Convert to cents
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
                        'status' => PaymentStatus::COMPLETED,
                        'payment_date' => now(),
                        'paid_at' => now(),
                        'receipt_number' => 'REC-'.$payment->academy_id.'-'.$payment->id.'-'.time(),
                        'transaction_id' => $result->transactionId,
                        'gateway_intent_id' => $result->transactionId,
                        'gateway_order_id' => $result->gatewayOrderId,
                        'notes' => 'Auto-renewal successful via saved card',
                    ]);

                    // Update subscription's purchase_source field
                    $subscription->update([
                        'purchase_source' => $purchaseSource,
                    ]);

                    // Update saved payment method last used
                    $savedPaymentMethod->touchLastUsed();

                    // Send success notification
                    $this->resultHandler->sendPaymentNotifications($payment, $result);

                    Log::info('Subscription auto-renewal successful', [
                        'payment_id' => $payment->id,
                        'subscription_id' => $subscription->id,
                        'transaction_id' => $result->transactionId,
                        'purchase_source' => $purchaseSource,
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
                $this->resultHandler->sendPaymentNotifications($payment, $result);

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
            } catch (Exception $e) {
                // Get failure count from subscription metadata
                $metadata = $subscription->metadata ?? [];
                $failureCount = $metadata['renewal_failed_count'] ?? 0;

                // Check if saved payment method exists and is expired
                $hasSavedCard = isset($savedPaymentMethod);
                $cardExpired = $savedPaymentMethod?->isExpired() ?? false;

                Log::error('Exception during subscription auto-renewal', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'failure_count' => $failureCount,
                    'has_saved_card' => $hasSavedCard,
                    'card_expired' => $cardExpired,
                ]);

                // Add Sentry context for auto-renewal errors
                if (app()->bound('sentry')) {
                    app('sentry')->captureException($e, [
                        'tags' => [
                            'payment_type' => 'auto_renewal',
                            'gateway' => $gatewayName ?? 'paymob',
                            'failure_count' => $failureCount,
                        ],
                        'extra' => [
                            'subscription_id' => $subscription->id,
                            'subscription_type' => class_basename($subscription),
                            'student_id' => $student->id,
                            'has_saved_card' => $hasSavedCard,
                            'card_expired' => $cardExpired,
                            'renewal_price' => $renewalPrice,
                            'next_billing_date' => $subscription->next_billing_date?->format('Y-m-d'),
                        ],
                    ]);
                }

                throw $e; // Re-throw to trigger transaction rollback
            }
        });
    }
}
