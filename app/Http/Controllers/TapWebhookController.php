<?php

namespace App\Http\Controllers;

use App\Constants\DefaultAcademy;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\PaymentWebhookEvent;
use App\Models\QuranSubscription;
use App\Services\NotificationService;
use App\Services\Payment\DTOs\WebhookPayload;
use App\Services\Payment\Exceptions\WebhookValidationException;
use App\Services\Payment\Gateways\TapGateway;
use App\Services\Payment\InvoiceService;
use App\Services\Payment\PaymentStateMachine;
use App\Services\Payment\TapSignatureService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Controller for handling Tap Payment webhook callbacks.
 *
 * Processes charge status updates from Tap and updates
 * local payment records accordingly.
 */
class TapWebhookController extends Controller
{
    use ApiResponses;

    public function __construct(
        private TapSignatureService $signatureService,
        private PaymentStateMachine $stateMachine,
    ) {}

    /**
     * Handle incoming Tap webhook (POST from Tap server).
     */
    public function handle(Request $request): JsonResponse
    {
        // Security: Verify webhook is from allowed IPs (if configured)
        $allowedIps = config('payments.gateways.tap.webhook_ips', []);
        if (! empty($allowedIps) && ! in_array($request->ip(), $allowedIps)) {
            Log::channel('payments')->warning('Tap webhook from unauthorized IP', [
                'ip' => $request->ip(),
                'allowed_ips' => $allowedIps,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        Log::channel('payments')->info('Tap webhook received', [
            'charge_id' => $request->input('id'),
            'status' => $request->input('status'),
            'amount' => $request->input('amount'),
            'currency' => $request->input('currency'),
            'ip' => $request->ip(),
        ]);

        try {
            // Step 1: Verify signature
            if (! $this->signatureService->verify($request)) {
                throw WebhookValidationException::invalidSignature('tap');
            }

            // Step 2: Parse payload
            $payload = WebhookPayload::fromTap($request->all());

            // Step 3: Check for duplicate event (idempotency)
            $eventId = $payload->getIdempotencyKey();
            if (PaymentWebhookEvent::eventExists($eventId)) {
                Log::channel('payments')->info('Duplicate Tap webhook event ignored', [
                    'event_id' => $eventId,
                ]);

                return response()->json([
                    'status' => 'ignored',
                    'message' => 'Duplicate event',
                ]);
            }

            // Step 4: Store webhook event (sanitize sensitive fields)
            $safePayload = collect($request->all())
                ->except(['hashstring', 'card', 'source'])
                ->toArray();
            $webhookEvent = PaymentWebhookEvent::createFromPayload(
                gateway: 'tap',
                eventType: $payload->eventType,
                eventId: $eventId,
                payload: $safePayload,
                paymentId: $payload->paymentId,
                academyId: $payload->academyId,
            );

            // Step 5: Process the webhook
            $result = $this->processWebhook($payload, $webhookEvent);

            return response()->json($result);
        } catch (WebhookValidationException $e) {
            Log::channel('payments')->error('Tap webhook validation failed', [
                'error' => $e->getMessage(),
                'code' => $e->getErrorCode(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        } catch (QueryException $e) {
            Log::channel('payments')->error('Database error during Tap webhook processing', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Database error occurred',
            ], 500);
        } catch (InvalidArgumentException $e) {
            Log::channel('payments')->error('Invalid Tap webhook data', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data format',
            ], 400);
        } catch (Throwable $e) {
            Log::channel('payments')->critical('Unexpected Tap webhook processing error', [
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
     * Handle Tap redirect callback (after user completes payment on Tap's page).
     *
     * Tap appends ?tap_id={charge_id} to the redirect URL configured in the charge.
     *
     * Note: The webhook is authoritative for activation. The callback only stores
     * the tap_id and redirects the user to the appropriate page.
     */
    public function callback(Request $request): RedirectResponse
    {
        $tapId = $request->input('tap_id', '');

        Log::channel('payments')->info('Tap callback received', [
            'tap_id' => $tapId,
            'all_params' => $request->query(),
        ]);

        // Find payment by gateway transaction ID or metadata payment_id
        $payment = null;

        // Primary: find by gateway_transaction_id (set during charge creation)
        if ($tapId) {
            $payment = Payment::withoutGlobalScopes()->with('academy')
                ->where('gateway_transaction_id', $tapId)
                ->orWhere('gateway_intent_id', $tapId)
                ->first();
        }

        // Fallback: look up by payment_id in metadata via Tap API
        if (! $payment && $tapId) {
            $payment = $this->findPaymentFromTapApi($tapId);
        }

        if (! $payment) {
            Log::channel('payments')->error('Tap callback: payment not found', [
                'tap_id' => $tapId,
            ]);

            return redirect(config('app.url'))
                ->with('error', 'لم يتم العثور على الدفعة');
        }

        // Store the tap_id as the gateway transaction ID if not already set
        if (! $payment->gateway_transaction_id && $tapId) {
            $payment->update(['gateway_transaction_id' => $tapId]);
        }

        // Verify with Tap API to get authoritative status
        $subdomain = $payment->academy?->subdomain ?? DefaultAcademy::subdomain();

        try {
            $gateway = new TapGateway(config('payments.gateways.tap', []));
            $result = $gateway->verifyPayment($tapId);

            if ($result->isSuccessful()) {
                // Use DB transaction with lock to prevent race condition with webhook
                $activated = DB::transaction(function () use ($payment, $result) {
                    $lockedPayment = Payment::withoutGlobalScopes()->lockForUpdate()->find($payment->id);
                    if (! $lockedPayment || $lockedPayment->status === PaymentStatus::COMPLETED) {
                        return false;
                    }

                    $lockedPayment->update([
                        'status' => PaymentStatus::COMPLETED,
                        'payment_date' => now(),
                        'paid_at' => now(),
                        'receipt_number' => $lockedPayment->receipt_number ?? ('REC-'.$lockedPayment->academy_id.'-'.$lockedPayment->id.'-'.time()),
                        'gateway_transaction_id' => $result->transactionId ?? $lockedPayment->gateway_transaction_id,
                    ]);

                    $this->handleSuccessfulPayment($lockedPayment);

                    return true;
                });

                if (! $activated) {
                    Log::channel('payments')->info('Tap callback: payment already completed (webhook processed it first)', [
                        'payment_id' => $payment->id,
                    ]);
                }

                $redirectUrl = route('student.subscriptions', ['subdomain' => $subdomain]);

                return redirect()->to($redirectUrl)
                    ->with('success', __('payments.notifications.payment_success'));
            }

            // Payment not yet captured — redirect to subscriptions, webhook will activate when ready
            $redirectUrl = route('student.subscriptions', ['subdomain' => $subdomain]);

            return redirect()->to($redirectUrl)
                ->with('info', 'تم استلام الدفع وجاري المعالجة');
        } catch (Exception $e) {
            Log::channel('payments')->error('Tap callback: verification exception', [
                'payment_id' => $payment->id,
                'tap_id' => $tapId,
                'error' => $e->getMessage(),
            ]);

            $redirectUrl = route('student.subscriptions', ['subdomain' => $subdomain]);

            return redirect()->to($redirectUrl)
                ->with('info', 'تم استلام الدفع وجاري المعالجة');
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
            $payment = Payment::withoutGlobalScopes()->find($payload->paymentId);
        }

        // Try to find by tap charge ID
        if (! $payment && $payload->transactionId) {
            $payment = Payment::withoutGlobalScopes()
                ->where('gateway_transaction_id', $payload->transactionId)
                ->orWhere('gateway_intent_id', $payload->transactionId)
                ->first();
        }

        if (! $payment) {
            Log::channel('payments')->warning('Payment not found for Tap webhook', [
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
            Log::channel('payments')->error('Academy ID mismatch in Tap webhook', [
                'expected' => $payment->academy_id,
                'received' => $payload->academyId,
            ]);

            $webhookEvent->markAsFailed('Academy ID mismatch');

            return [
                'status' => 'error',
                'message' => 'Tenant mismatch',
            ];
        }

        // Update payment status with row-level locking to prevent race conditions
        return DB::transaction(function () use ($payment, $payload, $webhookEvent) {
            $payment = Payment::withoutGlobalScopes()->lockForUpdate()->find($payment->id);

            if (! $payment) {
                $webhookEvent->markAsFailed('Payment not found during locked read');

                return [
                    'status' => 'error',
                    'message' => 'Payment not found during processing',
                ];
            }

            $oldStatus = $payment->status;
            $newStatus = $payload->status->value;

            // Check if transition is valid
            if (! $this->stateMachine->canTransition($oldStatus, $newStatus)) {
                Log::channel('payments')->warning('Invalid status transition for Tap payment', [
                    'payment_id' => $payment->id,
                    'from' => $oldStatus,
                    'to' => $newStatus,
                ]);

                $webhookEvent->markAsProcessed();

                return [
                    'status' => 'ignored',
                    'message' => 'Invalid status transition',
                ];
            }

            // Update payment record
            $updateData = [
                'status' => $newStatus,
                'gateway_transaction_id' => $payload->transactionId,
                'gateway_order_id' => $payload->orderId,
            ];

            if ($payload->isSuccessful()) {
                $updateData['paid_at'] = $payload->processedAt ?? now();
                $updateData['payment_date'] = $payload->processedAt ?? now();
                $updateData['receipt_number'] = $payment->receipt_number ?? ('REC-'.$payment->academy_id.'-'.$payment->id.'-'.time());
            }

            if ($payload->paymentMethod) {
                $updateData['payment_method_type'] = $payload->paymentMethod;
            }

            $payment->update($updateData);

            // Store additional gateway response data
            $payment->updateGatewayResponse([
                'tap_charge_id' => $payload->transactionId,
                'tap_status' => $payload->metadata['charge_status'] ?? null,
                'payment_method' => $payload->paymentMethod ?? null,
                'card_brand' => $payload->cardBrand ?? null,
                'card_last_four' => $payload->cardLastFour ?? null,
                'reference_transaction' => $payload->metadata['reference_transaction'] ?? null,
            ]);

            // Log the status change
            PaymentAuditLog::logStatusChange(
                payment: $payment,
                fromStatus: $oldStatus,
                toStatus: $newStatus,
                notes: "Tap Webhook: {$payload->eventType}"
            );

            // Mark webhook as processed
            $webhookEvent->update(['payment_id' => $payment->id]);
            $webhookEvent->markAsProcessed();

            // Handle post-payment actions
            if ($payload->isSuccessful()) {
                $this->handleSuccessfulPayment($payment);
            }

            Log::channel('payments')->info('Payment updated from Tap webhook', [
                'payment_id' => $payment->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'tap_charge_id' => $payload->transactionId,
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
    private function handleSuccessfulPayment(Payment $payment): void
    {
        Log::channel('payments')->info('Tap: handleSuccessfulPayment started', [
            'payment_id' => $payment->id,
            'payable_type' => $payment->payable_type,
            'payable_id' => $payment->payable_id,
        ]);

        // Activate related subscription if exists (polymorphic)
        if ($payment->payable_type && $payment->payable_id) {
            $payable = $payment->payable;

            if ($payable && method_exists($payable, 'activateFromPayment')) {
                $payable->activateFromPayment($payment);

                Log::channel('payments')->info('Tap: activateFromPayment completed', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $payable->id,
                ]);
            } else {
                Log::channel('payments')->warning('Tap: payable does not have activateFromPayment method', [
                    'payment_id' => $payment->id,
                    'payable_class' => $payable ? get_class($payable) : null,
                ]);
            }
        } elseif ($payment->subscription_id && $payment->payment_type === 'subscription') {
            // Legacy fallback: direct subscription_id lookup (for Quran subscriptions)
            $subscription = QuranSubscription::find($payment->subscription_id);
            if ($subscription && $subscription->payment_status->value !== 'paid') {
                $subscription->activateFromPayment($payment);
            }
        } else {
            Log::channel('payments')->warning('Tap: no subscription linkage found', [
                'payment_id' => $payment->id,
            ]);
        }

        $this->sendPaymentSuccessNotification($payment);
        $this->generateInvoice($payment);
    }

    /**
     * Send payment success notification to user.
     */
    private function sendPaymentSuccessNotification(Payment $payment): void
    {
        try {
            if ($payment->payment_notification_sent_at) {
                return;
            }

            $user = $payment->user;
            if (! $user) {
                return;
            }

            $notificationService = app(NotificationService::class);
            $subscriptionName = __('payments.notifications.payment');

            if ($payment->payable) {
                if (method_exists($payment->payable, 'getSubscriptionDisplayName')) {
                    $subscriptionName = $payment->payable->getSubscriptionDisplayName();
                } elseif (method_exists($payment->payable, 'getSubscriptionType')) {
                    $subscriptionType = $payment->payable->getSubscriptionType();
                    $subscriptionName = match ($subscriptionType) {
                        'quran' => __('payments.notifications.quran_subscription'),
                        'academic' => __('payments.notifications.academic_subscription'),
                        'course' => __('payments.notifications.course_subscription'),
                        default => __('payments.notifications.generic_subscription'),
                    };
                }
            }

            $notificationService->sendPaymentSuccessNotification($user, [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->gateway_transaction_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? getCurrencyCode(null, $payment->academy),
                'description' => $subscriptionName,
                'subscription_id' => $payment->payable_id,
            ]);

            $payment->update(['payment_notification_sent_at' => now()]);
        } catch (Exception $e) {
            Log::error('Failed to send Tap payment success notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate invoice PDF for the payment.
     */
    private function generateInvoice(Payment $payment): void
    {
        try {
            $invoiceService = app(InvoiceService::class);
            $result = $invoiceService->generateInvoiceWithPdf($payment);
            $invoiceData = $result['invoice'];

            $user = $payment->user;
            if ($user) {
                $notificationService = app(NotificationService::class);
                $notificationService->send(
                    $user,
                    NotificationType::INVOICE_GENERATED,
                    [
                        'invoice_number' => $invoiceData->invoiceNumber,
                        'amount' => $invoiceData->amount,
                        'currency' => $invoiceData->currency,
                    ],
                    '/payments',
                    ['payment_id' => $payment->id, 'invoice_number' => $invoiceData->invoiceNumber],
                    false
                );
            }
        } catch (Exception $e) {
            Log::channel('payments')->error('Tap: failed to generate invoice', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fetch charge from Tap API and resolve to a local Payment record.
     */
    private function findPaymentFromTapApi(string $tapId): ?Payment
    {
        try {
            $gateway = new TapGateway(config('payments.gateways.tap', []));
            $result = $gateway->verifyPayment($tapId);

            if ($result->rawResponse) {
                $metadata = $result->rawResponse['metadata'] ?? [];
                $paymentId = $metadata['payment_id'] ?? null;

                if ($paymentId) {
                    return Payment::withoutGlobalScopes()->with('academy')->find((int) $paymentId);
                }
            }
        } catch (Exception $e) {
            Log::channel('payments')->warning('Tap: could not fetch charge from API for callback lookup', [
                'tap_id' => $tapId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
