<?php

namespace App\Http\Controllers;

use App\Constants\DefaultAcademy;
use App\Enums\PaymentStatus;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\PaymentWebhookEvent;
use App\Models\QuranSubscription;
use App\Services\Payment\DTOs\TokenizationResult;
use App\Services\Payment\DTOs\WebhookPayload;
use App\Services\Payment\Exceptions\WebhookValidationException;
use App\Services\Payment\PaymentMethodService;
use App\Services\Payment\PaymentStateMachine;
use App\Services\Payment\PaymobSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling Paymob webhook callbacks.
 *
 * Processes payment status updates from Paymob and updates
 * local payment records accordingly.
 */
class PaymobWebhookController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PaymobSignatureService $signatureService,
        private PaymentStateMachine $stateMachine,
        private PaymentMethodService $paymentMethodService,
    ) {}

    /**
     * Handle incoming Paymob webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        // Security: Verify webhook is from allowed IPs (if configured)
        $allowedIps = config('payments.gateways.paymob.webhook_ips', []);
        if (! empty($allowedIps) && ! in_array($request->ip(), $allowedIps)) {
            Log::channel('payments')->warning('Webhook from unauthorized IP', [
                'ip' => $request->ip(),
                'allowed_ips' => $allowedIps,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        Log::channel('payments')->info('Paymob webhook received', [
            'type' => $request->input('type'),
            'transaction_id' => $request->input('obj.id'),
            'ip' => $request->ip(),
        ]);

        try {
            // Step 1: Verify signature
            if (! $this->signatureService->verify($request)) {
                throw WebhookValidationException::invalidSignature('paymob');
            }

            // Step 2: Parse payload
            $payload = WebhookPayload::fromPaymob($request->all());

            // Step 3: Check for duplicate event (idempotency)
            $eventId = $payload->getIdempotencyKey();
            if (PaymentWebhookEvent::exists($eventId)) {
                Log::channel('payments')->info('Duplicate webhook event ignored', [
                    'event_id' => $eventId,
                ]);

                return response()->json([
                    'status' => 'ignored',
                    'message' => 'Duplicate event',
                ]);
            }

            // Step 4: Store webhook event
            $webhookEvent = PaymentWebhookEvent::createFromPayload(
                gateway: 'paymob',
                eventType: $payload->eventType,
                eventId: $eventId,
                payload: $request->all(),
                paymentId: $payload->paymentId,
                academyId: $payload->academyId,
            );

            // Step 5: Process the webhook
            $result = $this->processWebhook($payload, $webhookEvent);

            return response()->json($result);
        } catch (WebhookValidationException $e) {
            Log::channel('payments')->error('Webhook validation failed', [
                'error' => $e->getMessage(),
                'code' => $e->getErrorCode(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::channel('payments')->error('Database error during webhook processing', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Database error occurred',
            ], 500);
        } catch (\InvalidArgumentException $e) {
            Log::channel('payments')->error('Invalid webhook data', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data format',
            ], 400);
        } catch (\Throwable $e) {
            Log::channel('payments')->critical('Unexpected webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal error',
            ], 500);
        }
    }

    /**
     * Process the webhook payload.
     */
    private function processWebhook(WebhookPayload $payload, PaymentWebhookEvent $webhookEvent): array
    {
        // Find the payment
        $payment = null;

        if ($payload->paymentId) {
            // Bypass tenant scope - webhooks arrive without tenant context
            $payment = Payment::withoutGlobalScopes()->find($payload->paymentId);
        }

        // Try to find by transaction ID if not found
        if (! $payment && $payload->transactionId) {
            // Bypass tenant scope - webhooks arrive without tenant context
            $payment = Payment::withoutGlobalScopes()
                ->where('transaction_id', $payload->transactionId)
                ->orWhere('gateway_intent_id', $payload->transactionId)
                ->first();
        }

        if (! $payment) {
            Log::channel('payments')->warning('Payment not found for webhook', [
                'payment_id' => $payload->paymentId,
                'transaction_id' => $payload->transactionId,
            ]);

            $webhookEvent->markAsFailed('Payment not found');

            return [
                'status' => 'error',
                'message' => 'Payment not found',
            ];
        }

        // Verify academy_id if available
        if ($payload->academyId && $payment->academy_id !== $payload->academyId) {
            Log::channel('payments')->error('Academy ID mismatch in webhook', [
                'expected' => $payment->academy_id,
                'received' => $payload->academyId,
            ]);

            $webhookEvent->markAsFailed('Academy ID mismatch');

            return [
                'status' => 'error',
                'message' => 'Tenant mismatch',
            ];
        }

        // Verify amount
        $expectedAmount = (int) round($payment->amount * 100);
        if ($payload->amountInCents !== $expectedAmount) {
            Log::channel('payments')->error('Amount mismatch in webhook', [
                'expected' => $expectedAmount,
                'received' => $payload->amountInCents,
            ]);

            $webhookEvent->markAsFailed('Amount mismatch');

            return [
                'status' => 'error',
                'message' => 'Amount mismatch',
            ];
        }

        // Update payment status with row-level locking to prevent race conditions
        // between webhook and callback processing the same payment simultaneously
        return DB::transaction(function () use ($payment, $payload, $webhookEvent) {
            // Lock the payment row and re-read to get the latest status
            // Bypass tenant scope - webhooks arrive without tenant context
            $payment = Payment::withoutGlobalScopes()->lockForUpdate()->find($payment->id);

            if (! $payment) {
                $webhookEvent->markAsFailed('Payment not found during locked read');

                return [
                    'status' => 'error',
                    'message' => 'Payment not found',
                ];
            }

            $oldStatus = $payment->status;
            $newStatus = $payload->status->value;

            // Check if transition is valid
            if (! $this->stateMachine->canTransition($oldStatus, $newStatus)) {
                Log::channel('payments')->warning('Invalid status transition', [
                    'payment_id' => $payment->id,
                    'from' => $oldStatus,
                    'to' => $newStatus,
                ]);

                // Still mark as processed since we received it
                $webhookEvent->markAsProcessed();

                return [
                    'status' => 'ignored',
                    'message' => 'Invalid status transition',
                ];
            }

            // Update payment
            $updateData = [
                'status' => $newStatus,
                'transaction_id' => $payload->transactionId,
            ];

            if ($payload->isSuccessful()) {
                $updateData['payment_status'] = 'paid';
                $updateData['paid_at'] = $payload->processedAt ?? now();
            } elseif ($newStatus === 'failed') {
                $updateData['payment_status'] = 'failed';
            }

            if ($payload->cardBrand) {
                $updateData['card_brand'] = $payload->cardBrand;
            }
            if ($payload->cardLastFour) {
                $updateData['card_last_four'] = $payload->cardLastFour;
            }
            if ($payload->paymentMethod) {
                $updateData['payment_method_type'] = $payload->paymentMethod;
            }

            $payment->update($updateData);

            // Log the status change
            PaymentAuditLog::logStatusChange(
                payment: $payment,
                fromStatus: $oldStatus,
                toStatus: $newStatus,
                notes: "Webhook: {$payload->eventType}"
            );

            // Mark webhook as processed
            $webhookEvent->update(['payment_id' => $payment->id]);
            $webhookEvent->markAsProcessed();

            // Handle post-payment actions
            if ($payload->isSuccessful()) {
                $this->handleSuccessfulPayment($payment, $payload);
            }

            Log::channel('payments')->info('Payment updated from webhook', [
                'payment_id' => $payment->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            return [
                'status' => 'success',
                'message' => 'Payment updated',
                'payment_id' => $payment->id,
            ];
        });
    }

    /**
     * Handle successful payment post-processing.
     */
    private function handleSuccessfulPayment(Payment $payment, ?WebhookPayload $payload = null): void
    {
        // Activate related subscription if exists
        if ($payment->payable_type && $payment->payable_id) {
            $payable = $payment->payable;

            if ($payable && method_exists($payable, 'activateFromPayment')) {
                $payable->activateFromPayment($payment);
            }
        }

        // Save card token if payment was set to save card
        if ($payload && $payload->hasTokenizationData() && $payment->save_card) {
            $this->saveCardFromWebhook($payment, $payload);
        }

        // Send success notification
        $this->sendPaymentSuccessNotification($payment);

        // Generate invoice/receipt
        $this->generateInvoice($payment);
    }

    /**
     * Save card token from webhook payload.
     */
    private function saveCardFromWebhook(Payment $payment, WebhookPayload $payload): void
    {
        try {
            $user = $payment->user;
            $academy = $payment->academy;

            if (! $user || ! $academy) {
                Log::warning('Cannot save card: missing user or academy', [
                    'payment_id' => $payment->id,
                    'has_user' => (bool) $user,
                    'has_academy' => (bool) $academy,
                ]);

                return;
            }

            // Create TokenizationResult from webhook payload
            $tokenResult = TokenizationResult::success(
                token: $payload->cardToken,
                cardBrand: $payload->cardBrand,
                lastFour: $payload->cardLastFour,
                expiryMonth: $payload->cardExpiryMonth,
                expiryYear: $payload->cardExpiryYear,
                holderName: $payload->cardHolderName,
                gatewayCustomerId: $payload->gatewayCustomerId,
                rawResponse: $payload->rawPayload,
                metadata: [
                    'saved_from' => 'webhook',
                    'transaction_id' => $payload->transactionId,
                    'payment_id' => $payment->id,
                ],
            );

            // Check if this is the user's first saved payment method (will be default)
            $hasExistingMethods = $this->paymentMethodService->hasPaymentMethods($user, $payload->gateway);

            // Save the payment method
            $savedMethod = $this->paymentMethodService->saveFromTokenizationResult(
                user: $user,
                academy: $academy,
                gateway: $payload->gateway,
                tokenResult: $tokenResult,
                setAsDefault: ! $hasExistingMethods, // Set as default if first card
            );

            if ($savedMethod) {
                // Update payment with saved payment method reference
                $payment->update([
                    'saved_payment_method_id' => $savedMethod->id,
                    'card_token' => $payload->cardToken,
                ]);

                Log::info('Card saved from webhook', [
                    'payment_id' => $payment->id,
                    'saved_payment_method_id' => $savedMethod->id,
                    'user_id' => $user->id,
                    'brand' => $payload->cardBrand,
                    'last_four' => $payload->cardLastFour,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to save card from webhook', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - card saving failure shouldn't fail the payment
        }
    }

    /**
     * Send payment success notification to user.
     */
    private function sendPaymentSuccessNotification(Payment $payment): void
    {
        try {
            $user = $payment->user;
            if (! $user) {
                Log::warning('Cannot send payment notification: user not found', [
                    'payment_id' => $payment->id,
                ]);

                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);

            // Get subscription name if available
            $subscriptionName = __('payments.notifications.payment');
            $subscriptionType = null;

            if ($payment->payable) {
                $payable = $payment->payable;

                // Determine subscription type using instanceof
                if ($payable instanceof QuranSubscription) {
                    $subscriptionType = 'quran';
                    $subscriptionName = $payable->package_name_ar
                        ?? $payable->package?->name
                        ?? __('payments.quran_subscription');
                } elseif ($payable instanceof AcademicSubscription) {
                    $subscriptionType = 'academic';
                    $subscriptionName = $payable->package_name_ar
                        ?? $payable->package?->name
                        ?? $payable->subject_name
                        ?? __('payments.academic_subscription');
                } elseif ($payable instanceof CourseSubscription) {
                    $subscriptionType = 'course';
                    $subscriptionName = $payable->course?->title
                        ?? __('payments.course_subscription');
                } else {
                    $subscriptionName = __('payments.notifications.generic_subscription');
                }
            }

            $paymentData = [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? getCurrencyCode(null, $payment->academy),
                'description' => $subscriptionName,
                'subscription_id' => $payment->payable_id,
                'subscription_type' => $subscriptionType,
                'subdomain' => $payment->academy?->subdomain ?? DefaultAcademy::subdomain(),
            ];

            // Add circle/course IDs for proper URL generation
            if ($payment->payable) {
                $payable = $payment->payable;

                if ($payable instanceof QuranSubscription) {
                    // Individual circle - check both HasOne relationship and polymorphic
                    if ($payable->individualCircle?->id) {
                        $paymentData['individual_circle_id'] = $payable->individualCircle->id;
                    } elseif ($payable->education_unit_type === 'App\\Models\\QuranIndividualCircle' && $payable->education_unit_id) {
                        $paymentData['individual_circle_id'] = $payable->education_unit_id;
                    }
                    // Group circle - check both column and polymorphic
                    if ($payable->quran_circle_id) {
                        $paymentData['circle_id'] = $payable->quran_circle_id;
                    } elseif ($payable->education_unit_type === 'App\\Models\\QuranCircle' && $payable->education_unit_id) {
                        $paymentData['circle_id'] = $payable->education_unit_id;
                    }
                } elseif ($payable instanceof CourseSubscription) {
                    if ($payable->course_id) {
                        $paymentData['course_id'] = $payable->course_id;
                    }
                }
            }

            $notificationService->sendPaymentSuccessNotification($user, $paymentData);

            Log::info('Payment success notification sent', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send payment success notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate invoice data for the payment and send notification.
     */
    private function generateInvoice(Payment $payment): void
    {
        try {
            $invoiceService = app(\App\Services\Payment\InvoiceService::class);
            $invoiceData = $invoiceService->generateInvoice($payment);

            // Send invoice generated notification to the user
            $user = $payment->user;
            if ($user) {
                $subdomain = $payment->academy?->subdomain ?? \App\Constants\DefaultAcademy::subdomain();

                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->send(
                    $user,
                    \App\Enums\NotificationType::INVOICE_GENERATED,
                    [
                        'invoice_number' => $invoiceData->invoiceNumber,
                        'amount' => $invoiceData->amount,
                        'currency' => $invoiceData->currency,
                    ],
                    "/payments/{$payment->id}",
                    ['payment_id' => $payment->id, 'invoice_number' => $invoiceData->invoiceNumber],
                    false
                );
            }

            Log::info('Invoice generated and notification sent', [
                'payment_id' => $payment->id,
                'invoice_number' => $invoiceData->invoiceNumber,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate invoice', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle Paymob redirect callback (for redirect flow).
     *
     * Route: /payments/{payment}/callback
     *
     * The callback URL from Paymob contains all transaction details including:
     * - id: Paymob transaction ID
     * - success: 'true' or 'false'
     * - amount_cents: Amount in cents
     * - currency: Currency code
     * - error_occured: Whether an error occurred
     * - txn_response_code: Response code (e.g., 'APPROVED')
     */
    public function callback(Request $request, int|string $payment): \Illuminate\Http\RedirectResponse
    {
        // Manually fetch the Payment model (route model binding not working in this context)
        // Bypass tenant scope - callback arrives from payment gateway without tenant context
        $payment = Payment::withoutGlobalScopes()->findOrFail($payment);

        $transactionId = $request->input('id');
        $isSuccess = $request->input('success') === 'true';
        $errorOccurred = $request->input('error_occured') === 'true';
        $txnResponseCode = $request->input('txn_response_code');

        Log::channel('payments')->info('Paymob callback received', [
            'payment_id' => $payment->id,
            'success' => $request->input('success'),
            'transaction_id' => $transactionId,
            'txn_response_code' => $txnResponseCode,
            'error_occured' => $request->input('error_occured'),
        ]);

        // Get subdomain for redirect
        $subdomain = $payment->academy?->subdomain ?? DefaultAcademy::subdomain();

        // Trust the callback data from Paymob when success=true and no errors
        // The callback comes directly from Paymob with transaction details
        if ($isSuccess && ! $errorOccurred && $transactionId) {
            // Use DB::transaction with lockForUpdate to prevent race condition
            // between webhook and callback processing the same payment simultaneously
            DB::transaction(function () use ($payment, $transactionId, $txnResponseCode, $request) {
                // Lock the payment row and re-read to get the latest status
                // Bypass tenant scope - callback arrives from payment gateway without tenant context
                $freshPayment = Payment::withoutGlobalScopes()->lockForUpdate()->find($payment->id);

                if (! $freshPayment) {
                    return;
                }

                // If already completed (e.g., webhook processed it first), skip processing
                if ($freshPayment->status === PaymentStatus::COMPLETED) {
                    Log::channel('payments')->info('Payment already completed, callback skipping processing', [
                        'payment_id' => $freshPayment->id,
                        'transaction_id' => $transactionId,
                    ]);

                    return;
                }

                $freshPayment->update([
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'gateway_transaction_id' => $transactionId,
                    'gateway_response' => [
                        'transaction_id' => $transactionId,
                        'amount_cents' => $request->input('amount_cents'),
                        'currency' => $request->input('currency'),
                        'txn_response_code' => $txnResponseCode,
                        'source_type' => $request->input('source_data.type'),
                        'source_pan' => $request->input('source_data.pan'),
                    ],
                ]);

                Log::channel('payments')->info('Payment marked as successful', [
                    'payment_id' => $freshPayment->id,
                    'transaction_id' => $transactionId,
                ]);

                // Activate the subscription if exists
                // Try payable polymorphic relationship first, then fall back to subscription_id
                $subscription = $freshPayment->payable;

                // Fallback: If no payable relationship, try to find subscription by subscription_id
                if (! $subscription && $freshPayment->subscription_id) {
                    // Try QuranSubscription first (most common)
                    $subscription = \App\Models\QuranSubscription::find($freshPayment->subscription_id);

                    // If not found, try AcademicSubscription
                    if (! $subscription) {
                        $subscription = \App\Models\AcademicSubscription::find($freshPayment->subscription_id);
                    }

                    // If found, update the payment's payable relationship for future reference
                    if ($subscription) {
                        $freshPayment->update([
                            'payable_type' => get_class($subscription),
                            'payable_id' => $subscription->id,
                        ]);
                    }
                }

                if ($subscription && method_exists($subscription, 'activateFromPayment')) {
                    // Use activateFromPayment to properly activate subscription
                    // This handles status update, individual circle creation, and notifications
                    $subscription->activateFromPayment($freshPayment);

                    Log::channel('payments')->info('Subscription activated via activateFromPayment', [
                        'subscription_id' => $subscription->id,
                        'subscription_type' => get_class($subscription),
                    ]);
                } else {
                    Log::channel('payments')->warning('No subscription found to activate', [
                        'payment_id' => $freshPayment->id,
                        'subscription_id' => $freshPayment->subscription_id,
                        'payable_type' => $freshPayment->payable_type,
                        'payable_id' => $freshPayment->payable_id,
                    ]);
                }

                // Send payment success notification (webhook will also send this if it arrives,
                // but callback often arrives first so we send it here too)
                $this->sendPaymentSuccessNotification($freshPayment);
            });

            return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                ->with('success', __('payments.notifications.payment_success'));
        }

        // Payment failed
        $errorMessage = $request->input('data.message') ?? __('payments.notifications.payment_failed');

        Log::channel('payments')->warning('Paymob payment failed', [
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
            'error_occured' => $errorOccurred,
            'txn_response_code' => $txnResponseCode,
            'error_message' => $errorMessage,
        ]);

        // Update payment status to failed (also with locking to prevent race conditions)
        DB::transaction(function () use ($payment, $transactionId, $txnResponseCode, $errorMessage) {
            // Bypass tenant scope - callback arrives from payment gateway without tenant context
            $freshPayment = Payment::withoutGlobalScopes()->lockForUpdate()->find($payment->id);

            if (! $freshPayment) {
                return;
            }

            // Don't overwrite a completed payment with failed status
            if ($freshPayment->status === PaymentStatus::COMPLETED) {
                Log::channel('payments')->info('Payment already completed, skipping failed status update', [
                    'payment_id' => $freshPayment->id,
                ]);

                return;
            }

            if ($freshPayment->status->value !== 'failed') {
                $freshPayment->update([
                    'status' => 'failed',
                    'payment_status' => 'failed',
                    'gateway_transaction_id' => $transactionId,
                    'gateway_response' => [
                        'error' => true,
                        'txn_response_code' => $txnResponseCode,
                        'message' => $errorMessage,
                    ],
                ]);
            }
        });

        return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
            ->with('error', __('payments.notifications.payment_failed_with_reason', ['reason' => $errorMessage]));
    }
}
