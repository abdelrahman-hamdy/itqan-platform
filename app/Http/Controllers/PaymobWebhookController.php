<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\PaymentWebhookEvent;
use App\Services\Payment\DTOs\TokenizationResult;
use App\Services\Payment\DTOs\WebhookPayload;
use App\Services\Payment\Exceptions\WebhookValidationException;
use App\Services\Payment\Gateways\PaymobGateway;
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
            $payment = Payment::find($payload->paymentId);
        }

        // Try to find by transaction ID if not found
        if (! $payment && $payload->transactionId) {
            $payment = Payment::where('transaction_id', $payload->transactionId)
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
        $expectedAmount = (int) ($payment->amount * 100);
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

        // Update payment status
        return DB::transaction(function () use ($payment, $payload, $webhookEvent) {
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
                $updateData['paid_at'] = $payload->processedAt ?? now();
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
            $subscriptionName = 'دفعة';
            $subscriptionType = null;

            if ($payment->payable) {
                if (method_exists($payment->payable, 'getSubscriptionDisplayName')) {
                    $subscriptionName = $payment->payable->getSubscriptionDisplayName();
                } elseif (method_exists($payment->payable, 'getSubscriptionType')) {
                    $subscriptionType = $payment->payable->getSubscriptionType();
                    $subscriptionName = match ($subscriptionType) {
                        'quran' => 'اشتراك القرآن',
                        'academic' => 'اشتراك أكاديمي',
                        'course' => 'اشتراك الدورة',
                        default => 'اشتراك',
                    };
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
            ];

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
     * Generate invoice/receipt for the payment.
     */
    private function generateInvoice(Payment $payment): void
    {
        try {
            // Invoice generation service not yet implemented
            // When implemented, this should:
            // 1. Generate PDF invoice with payment details using a PDF library (e.g., DomPDF, TCPDF)
            // 2. Store the PDF in storage/app/invoices/{payment_id}.pdf
            // 3. Send the invoice via email to the user
            // 4. Optionally create an Invoice model record with file path
            Log::info('Invoice generation triggered (not yet implemented)', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
            ]);

            // For now, just send invoice generated notification
            $user = $payment->user;
            if ($user) {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->send(
                    $user,
                    \App\Enums\NotificationType::INVOICE_GENERATED,
                    [
                        'invoice_number' => $payment->payment_code ?? $payment->id,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency ?? getCurrencyCode(null, $payment->academy),
                    ],
                    route('payments.invoice', ['payment' => $payment->id]),
                    ['payment_id' => $payment->id],
                    false
                );
            }
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
     */
    public function callback(Request $request, Payment $payment): \Illuminate\Http\RedirectResponse
    {
        $transactionId = $request->input('id');
        $isSuccess = $request->input('success') === 'true';

        Log::channel('payments')->info('Paymob callback received', [
            'payment_id' => $payment->id,
            'success' => $request->input('success'),
            'transaction_id' => $transactionId,
        ]);

        // Get subdomain for redirect
        $subdomain = $payment->academy?->subdomain ?? 'itqan-academy';

        // Verify with Paymob API if needed
        if ($isSuccess && $transactionId) {
            // Use academy-specific gateway config
            $gatewayFactory = app(\App\Services\Payment\AcademyPaymentGatewayFactory::class);
            $gateway = $payment->academy
                ? $gatewayFactory->getGateway($payment->academy, 'paymob')
                : new PaymobGateway(config('payments.gateways.paymob'));

            $result = $gateway->verifyPayment($transactionId);

            if ($result->isSuccessful()) {
                // Update payment if webhook hasn't done it yet
                if ($payment->status !== 'success') {
                    $payment->update([
                        'status' => 'success',
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                        'gateway_transaction_id' => $transactionId,
                    ]);

                    // Activate the subscription if exists
                    if ($payment->subscription) {
                        $payment->subscription->update([
                            'status' => 'active',
                            'payment_status' => 'paid',
                        ]);
                    }
                }

                return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                    ->with('success', 'تمت عملية الدفع بنجاح وتم تفعيل اشتراكك');
            }

            Log::channel('payments')->warning('Paymob verification failed', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
                'error' => $result->errorMessage,
            ]);
        }

        $errorMessage = isset($result) ? ($result->errorMessage ?? 'خطأ غير معروف') : 'فشل التحقق من الدفع';

        return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
            ->with('error', 'فشلت عملية الدفع: '.$errorMessage);
    }
}
