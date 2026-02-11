<?php

namespace App\Http\Controllers;

use App\Constants\DefaultAcademy;
use App\Enums\PaymentStatus;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\PaymentWebhookEvent;
use App\Services\Payment\DTOs\WebhookPayload;
use App\Services\Payment\EasyKashSignatureService;
use App\Services\Payment\Exceptions\WebhookValidationException;
use App\Services\Payment\Gateways\EasyKashGateway;
use App\Services\Payment\PaymentStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling EasyKash webhook callbacks.
 *
 * Processes payment status updates from EasyKash and updates
 * local payment records accordingly.
 */
class EasyKashWebhookController extends Controller
{
    use ApiResponses;

    public function __construct(
        private EasyKashSignatureService $signatureService,
        private PaymentStateMachine $stateMachine,
    ) {}

    /**
     * Handle incoming EasyKash webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        // Security: Verify webhook is from allowed IPs (if configured)
        $allowedIps = config('payments.gateways.easykash.webhook_ips', []);
        if (! empty($allowedIps) && ! in_array($request->ip(), $allowedIps)) {
            Log::channel('payments')->warning('EasyKash webhook from unauthorized IP', [
                'ip' => $request->ip(),
                'allowed_ips' => $allowedIps,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        Log::channel('payments')->info('EasyKash webhook received', [
            'status' => $request->input('status'),
            'easykash_ref' => $request->input('easykashRef'),
            'customer_reference' => $request->input('customerReference'),
            'ip' => $request->ip(),
        ]);

        try {
            // Step 1: Verify signature
            if (! $this->signatureService->verify($request)) {
                throw WebhookValidationException::invalidSignature('easykash');
            }

            // Step 2: Parse payload
            $payload = WebhookPayload::fromEasyKash($request->all());

            // Step 3: Check for duplicate event (idempotency)
            $eventId = $payload->getIdempotencyKey();
            if (PaymentWebhookEvent::eventExists($eventId)) {
                Log::channel('payments')->info('Duplicate EasyKash webhook event ignored', [
                    'event_id' => $eventId,
                ]);

                return response()->json([
                    'status' => 'ignored',
                    'message' => 'Duplicate event',
                ]);
            }

            // Step 4: Store webhook event
            $webhookEvent = PaymentWebhookEvent::createFromPayload(
                gateway: 'easykash',
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
            Log::channel('payments')->error('EasyKash webhook validation failed', [
                'error' => $e->getMessage(),
                'code' => $e->getErrorCode(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::channel('payments')->error('Database error during EasyKash webhook processing', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Database error occurred',
            ], 500);
        } catch (\InvalidArgumentException $e) {
            Log::channel('payments')->error('Invalid EasyKash webhook data', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data format',
            ], 400);
        } catch (\Throwable $e) {
            Log::channel('payments')->critical('Unexpected EasyKash webhook processing error', [
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
        // Find the payment by payment_id first
        $payment = null;

        if ($payload->paymentId) {
            // Bypass tenant scope - webhooks arrive without tenant context
            $payment = Payment::withoutGlobalScopes()->find($payload->paymentId);
        }

        // Try to find by transaction ID (easykashRef) if not found
        if (! $payment && $payload->transactionId) {
            // Bypass tenant scope - webhooks arrive without tenant context
            $payment = Payment::withoutGlobalScopes()
                ->where('gateway_transaction_id', $payload->transactionId)
                ->orWhere('gateway_intent_id', $payload->transactionId)
                ->first();
        }

        // Try to find by customer reference stored in metadata
        if (! $payment && ! empty($payload->rawPayload['customerReference'])) {
            $customerRef = $payload->rawPayload['customerReference'];
            $parsed = EasyKashSignatureService::parseCustomerReference($customerRef);

            if ($parsed['payment_id']) {
                // Bypass tenant scope - webhooks arrive without tenant context
                $payment = Payment::withoutGlobalScopes()->find($parsed['payment_id']);
            }
        }

        if (! $payment) {
            Log::channel('payments')->warning('Payment not found for EasyKash webhook', [
                'payment_id' => $payload->paymentId,
                'transaction_id' => $payload->transactionId,
                'customer_reference' => $payload->rawPayload['customerReference'] ?? null,
            ]);

            $webhookEvent->markAsFailed('Payment not found');

            return [
                'status' => 'error',
                'message' => 'Payment not found',
            ];
        }

        // Verify academy_id if available
        if ($payload->academyId && $payment->academy_id !== $payload->academyId) {
            Log::channel('payments')->error('Academy ID mismatch in EasyKash webhook', [
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
            Log::channel('payments')->error('Amount mismatch in EasyKash webhook', [
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
                    'message' => 'Payment not found during processing',
                ];
            }

            $oldStatus = $payment->status;
            $newStatus = $payload->status->value;

            // Check if transition is valid
            if (! $this->stateMachine->canTransition($oldStatus, $newStatus)) {
                Log::channel('payments')->warning('Invalid status transition for EasyKash payment', [
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
                'gateway_transaction_id' => $payload->transactionId,
                'gateway_order_id' => $payload->orderId, // ProductCode
            ];

            if ($payload->isSuccessful()) {
                $updateData['paid_at'] = $payload->processedAt ?? now();
            }

            if ($payload->paymentMethod) {
                $updateData['payment_method_type'] = $payload->paymentMethod;
            }

            // Store voucher code for cash payments (Fawry/Aman)
            if (! empty($payload->metadata['voucher'])) {
                $updateData['voucher_code'] = $payload->metadata['voucher'];
            }

            $payment->update($updateData);

            // Store additional gateway response data
            $payment->updateGatewayResponse([
                'easykash_ref' => $payload->transactionId,
                'product_code' => $payload->orderId,
                'payment_method' => $payload->rawPayload['PaymentMethod'] ?? null,
                'product_type' => $payload->rawPayload['ProductType'] ?? null,
                'voucher' => $payload->metadata['voucher'] ?? null,
                'buyer_name' => $payload->metadata['buyer_name'] ?? null,
                'buyer_email' => $payload->metadata['buyer_email'] ?? null,
            ]);

            // Log the status change
            PaymentAuditLog::logStatusChange(
                payment: $payment,
                fromStatus: $oldStatus,
                toStatus: $newStatus,
                notes: "EasyKash Webhook: {$payload->eventType}"
            );

            // Mark webhook as processed
            $webhookEvent->update(['payment_id' => $payment->id]);
            $webhookEvent->markAsProcessed();

            // Handle post-payment actions
            if ($payload->isSuccessful()) {
                $this->handleSuccessfulPayment($payment);
            }

            Log::channel('payments')->info('Payment updated from EasyKash webhook', [
                'payment_id' => $payment->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'easykash_ref' => $payload->transactionId,
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
        Log::channel('payments')->info('EasyKash: handleSuccessfulPayment started', [
            'payment_id' => $payment->id,
            'payable_type' => $payment->payable_type,
            'payable_id' => $payment->payable_id,
            'subscription_id' => $payment->subscription_id,
        ]);

        // Activate related subscription if exists (polymorphic)
        if ($payment->payable_type && $payment->payable_id) {
            Log::channel('payments')->info('EasyKash: using polymorphic payable', [
                'payment_id' => $payment->id,
                'payable_type' => $payment->payable_type,
            ]);

            $payable = $payment->payable;

            if ($payable) {
                Log::channel('payments')->info('EasyKash: payable found', [
                    'payment_id' => $payment->id,
                    'payable_class' => get_class($payable),
                    'has_activate_method' => method_exists($payable, 'activateFromPayment'),
                ]);

                if (method_exists($payable, 'activateFromPayment')) {
                    Log::channel('payments')->info('EasyKash: calling activateFromPayment', [
                        'payment_id' => $payment->id,
                    ]);

                    $payable->activateFromPayment($payment);

                    Log::channel('payments')->info('EasyKash: activateFromPayment completed', [
                        'payment_id' => $payment->id,
                        'subscription_id' => $payable->id,
                    ]);
                } else {
                    Log::channel('payments')->warning('EasyKash: payable does not have activateFromPayment method', [
                        'payment_id' => $payment->id,
                        'payable_class' => get_class($payable),
                    ]);
                }
            } else {
                Log::channel('payments')->warning('EasyKash: payable not found', [
                    'payment_id' => $payment->id,
                    'payable_type' => $payment->payable_type,
                    'payable_id' => $payment->payable_id,
                ]);
            }
        } elseif ($payment->subscription_id && $payment->payment_type === 'subscription') {
            Log::channel('payments')->info('EasyKash: using legacy subscription_id fallback', [
                'payment_id' => $payment->id,
                'subscription_id' => $payment->subscription_id,
            ]);

            // Legacy fallback: direct subscription_id lookup (for Quran subscriptions)
            $subscription = \App\Models\QuranSubscription::find($payment->subscription_id);
            if ($subscription && $subscription->payment_status->value !== 'paid') {
                $subscription->update([
                    'payment_status' => \App\Enums\SubscriptionPaymentStatus::PAID,
                    'status' => \App\Enums\SessionSubscriptionStatus::ACTIVE,
                    'last_payment_at' => now(),
                    'last_payment_amount' => $payment->amount,
                ]);

                Log::channel('payments')->info('EasyKash: Quran subscription activated from legacy subscription_id', [
                    'subscription_id' => $subscription->id,
                    'payment_id' => $payment->id,
                ]);
            } else {
                Log::channel('payments')->warning('EasyKash: legacy subscription not found or already paid', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $payment->subscription_id,
                    'found' => $subscription !== null,
                    'already_paid' => $subscription ? ($subscription->payment_status->value === 'paid') : false,
                ]);
            }
        } else {
            Log::channel('payments')->warning('EasyKash: no subscription linkage found', [
                'payment_id' => $payment->id,
                'payable_type' => $payment->payable_type,
                'payable_id' => $payment->payable_id,
                'subscription_id' => $payment->subscription_id,
            ]);
        }

        // Send success notification
        Log::channel('payments')->info('EasyKash: sending payment success notification', [
            'payment_id' => $payment->id,
        ]);

        $this->sendPaymentSuccessNotification($payment);

        Log::channel('payments')->info('EasyKash: handleSuccessfulPayment completed', [
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * Send payment success notification to user.
     */
    private function sendPaymentSuccessNotification(Payment $payment): void
    {
        try {
            $user = $payment->user;
            if (! $user) {
                Log::warning('Cannot send EasyKash payment notification: user not found', [
                    'payment_id' => $payment->id,
                ]);

                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);

            // Get subscription name if available
            $subscriptionName = __('payments.notifications.payment');
            $subscriptionType = null;

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

            $paymentData = [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->gateway_transaction_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? getCurrencyCode(null, $payment->academy),
                'description' => $subscriptionName,
                'subscription_id' => $payment->payable_id,
                'subscription_type' => $subscriptionType,
            ];

            $notificationService->sendPaymentSuccessNotification($user, $paymentData);

            Log::info('EasyKash payment success notification sent', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send EasyKash payment success notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle EasyKash redirect callback (after user completes payment).
     *
     * EasyKash redirects with: ?status=PAID|NEW|FAILED&providerRefNum=xxx&customerReference=xxx
     *
     * Note: This callback is on the main domain (no subdomain) because EasyKash
     * requires a single callback URL. We find the academy from the payment and
     * redirect to the tenant-specific success/failed page.
     */
    public function callback(Request $request): RedirectResponse
    {
        Log::channel('payments')->info('EasyKash callback received', [
            'status' => $request->input('status'),
            'provider_ref_num' => $request->input('providerRefNum'),
            'customer_reference' => $request->input('customerReference'),
        ]);

        $status = strtoupper($request->input('status', ''));
        $customerReference = $request->input('customerReference', '');

        // Parse customer reference to find payment
        $parsed = EasyKashSignatureService::parseCustomerReference($customerReference);
        $payment = null;
        $providerRefNum = $request->input('providerRefNum');

        // CRITICAL: Try providerRefNum FIRST (most reliable with EasyKash)
        // EasyKash has a bug where customerReference is often wrong, but providerRefNum is unique per transaction
        if ($providerRefNum) {
            // Find the most recent pending payment (within last 10 minutes)
            $payment = Payment::withoutGlobalScopes()->with('academy')
                ->where('payment_method', 'easykash')
                ->where('status', PaymentStatus::PENDING)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($payment) {
                Log::channel('payments')->info('EasyKash callback: found payment by recent pending lookup', [
                    'payment_id' => $payment->id,
                    'provider_ref_num' => $providerRefNum,
                    'customer_reference_received' => $customerReference,
                    'gateway_intent_id_in_db' => $payment->gateway_intent_id,
                ]);
            }
        }

        // Fallback: Try to find by exact gateway_intent_id
        if (!$payment && $customerReference) {
            // Bypass tenant scope - callback arrives on main domain without tenant context
            $payment = Payment::withoutGlobalScopes()->with('academy')
                ->where('gateway_intent_id', $customerReference)
                ->first();

            if ($payment) {
                Log::channel('payments')->info('EasyKash callback: found payment by exact gateway_intent_id match', [
                    'payment_id' => $payment->id,
                    'customer_reference' => $customerReference,
                ]);
            }
        }

        // Fallback: Try by parsing payment ID from customerReference
        if (! $payment && $parsed['payment_id']) {
            // Bypass tenant scope - callback arrives on main domain without tenant context
            $candidatePayment = Payment::withoutGlobalScopes()->with('academy')->find($parsed['payment_id']);

            // CRITICAL: Verify this is a recent pending payment to avoid activating old payments
            if ($candidatePayment && $candidatePayment->status === PaymentStatus::PENDING) {
                // Extract timestamp from customerReference (first 12 digits: ymdHis)
                $timestampFromRef = substr($customerReference, 0, 12);
                $timestampFromPayment = $candidatePayment->created_at->format('ymdHis');

                // Only use this payment if timestamps are close (within 1 hour)
                if (abs((int)$timestampFromRef - (int)$timestampFromPayment) < 10000) {
                    $payment = $candidatePayment;
                    Log::channel('payments')->info('EasyKash callback: found payment by parsed ID with timestamp validation', [
                        'payment_id' => $payment->id,
                        'parsed_id' => $parsed['payment_id'],
                    ]);
                } else {
                    Log::channel('payments')->warning('EasyKash callback: parsed payment ID timestamp mismatch', [
                        'parsed_id' => $parsed['payment_id'],
                        'timestamp_from_ref' => $timestampFromRef,
                        'timestamp_from_payment' => $timestampFromPayment,
                    ]);
                }
            }
        }

        // Try by providerRefNum (least reliable)
        if (! $payment) {
            $providerRefNum = $request->input('providerRefNum');
            if ($providerRefNum) {
                // Bypass tenant scope - callback arrives on main domain without tenant context
                $payment = Payment::withoutGlobalScopes()->with('academy')
                    ->where('gateway_transaction_id', $providerRefNum)
                    ->orWhere('gateway_order_id', $providerRefNum)
                    ->first();

                if ($payment) {
                    Log::channel('payments')->info('EasyKash callback: found payment by providerRefNum', [
                        'payment_id' => $payment->id,
                        'provider_ref_num' => $providerRefNum,
                    ]);
                }
            }
        }

        if (! $payment) {
            Log::channel('payments')->error('EasyKash callback: payment not found', [
                'customer_reference' => $customerReference,
                'provider_ref_num' => $request->input('providerRefNum'),
            ]);

            // Redirect to main domain with error (no academy context available)
            return redirect(config('app.url'))
                ->with('error', 'لم يتم العثور على الدفعة');
        }

        // Build tenant-aware URL
        $tenantUrl = $this->buildTenantUrl($payment);

        if ($status === 'PAID' || $status === 'DELIVERED') {
            Log::channel('payments')->info('EasyKash callback: payment marked as PAID, verifying with API', [
                'payment_id' => $payment->id,
                'customer_reference' => $customerReference,
                'status' => $status,
            ]);

            try {
                // Verify with EasyKash API to confirm
                $gateway = $this->getGatewayForPayment($payment);
                $result = $gateway->verifyPayment($customerReference, [
                    'customerReference' => $customerReference,
                ]);

                Log::channel('payments')->info('EasyKash verification result', [
                    'payment_id' => $payment->id,
                    'is_successful' => $result->isSuccessful(),
                    'status' => $result->status->value ?? 'unknown',
                    'transaction_id' => $result->transactionId ?? null,
                ]);

                if ($result->isSuccessful()) {
                    // Update payment if webhook hasn't done it yet
                    if ($payment->status !== PaymentStatus::COMPLETED) {
                        Log::channel('payments')->info('EasyKash callback: updating payment to completed', [
                            'payment_id' => $payment->id,
                            'old_status' => $payment->status->value,
                        ]);

                        $payment->update([
                            'status' => PaymentStatus::COMPLETED,
                            'paid_at' => now(),
                            'gateway_transaction_id' => $result->transactionId ?? $payment->gateway_transaction_id,
                        ]);

                        // Activate subscription
                        Log::channel('payments')->info('EasyKash callback: calling handleSuccessfulPayment', [
                            'payment_id' => $payment->id,
                            'payable_type' => $payment->payable_type,
                            'payable_id' => $payment->payable_id,
                        ]);

                        $this->handleSuccessfulPayment($payment);

                        Log::channel('payments')->info('EasyKash callback: subscription activation completed', [
                            'payment_id' => $payment->id,
                        ]);
                    } else {
                        Log::channel('payments')->info('EasyKash callback: payment already completed (webhook processed it first)', [
                            'payment_id' => $payment->id,
                        ]);
                    }

                    // Redirect directly to subscriptions page (consistent with Paymob)
                    $subdomain = $payment->academy?->subdomain ?? \App\Constants\DefaultAcademy::subdomain();
                    return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                        ->with('success', __('payments.notifications.payment_success'));
                } else {
                    Log::channel('payments')->warning('EasyKash callback: verification failed - payment not successful', [
                        'payment_id' => $payment->id,
                        'verification_status' => $result->status->value ?? 'unknown',
                        'customer_reference' => $customerReference,
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('payments')->error('EasyKash callback: verification failed with exception', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // For NEW status (pending) - cash payments need voucher
        if ($status === 'NEW') {
            // Check if there's a pending route, otherwise use success with info
            return redirect($tenantUrl.'/payments/'.$payment->id.'/success')
                ->with('info', 'في انتظار الدفع. استخدم رمز الفاتورة للدفع.');
        }

        // Failed payment - redirect to subscription page with error message
        return $this->redirectToSubscriptionPage($payment, $tenantUrl, 'فشلت عملية الدفع. يرجى المحاولة مرة أخرى.');
    }

    /**
     * Redirect user to subscription page with error message.
     *
     * This is used for failed payments to return the user to the subscription page
     * where they can try again, instead of showing a 404 error.
     *
     * Handles different subscription types:
     * - Quran subscriptions → /quran-teachers/{profileId}/subscribe/{packageId}?period={cycle}
     * - Academic subscriptions → /academic-packages/teachers/{teacherId}/subscribe/{packageId}?period={cycle}
     * - Interactive courses → /interactive-courses/{courseId}
     * - Recorded courses → /courses/{courseId}
     */
    private function redirectToSubscriptionPage(Payment $payment, string $tenantUrl, string $errorMessage): RedirectResponse
    {
        $url = null;

        // Try to determine subscription type from payment_type or payable_type
        $paymentType = $payment->payment_type ?? '';

        // Handle Quran Subscription
        if ($payment->subscription_id || str_contains($paymentType, 'quran')) {
            $url = $this->getQuranSubscriptionRedirectUrl($payment, $tenantUrl);
        }

        // Handle Academic Subscription
        if (! $url && str_contains($paymentType, 'academic')) {
            $url = $this->getAcademicSubscriptionRedirectUrl($payment, $tenantUrl);
        }

        // Handle Course Subscription (Interactive or Recorded)
        if (! $url && str_contains($paymentType, 'course')) {
            $url = $this->getCourseSubscriptionRedirectUrl($payment, $tenantUrl);
        }

        // Handle polymorphic payable relationship
        if (! $url && $payment->payable_type && $payment->payable_id) {
            $url = $this->getPayableRedirectUrl($payment, $tenantUrl);
        }

        // Fallback: Try subscription_id with QuranSubscription (legacy)
        if (! $url && $payment->subscription_id) {
            $url = $this->getQuranSubscriptionRedirectUrl($payment, $tenantUrl);
        }

        if ($url) {
            return redirect($url)->with('error', $errorMessage);
        }

        // Final fallback to main tenant URL
        return redirect($tenantUrl)->with('error', $errorMessage);
    }

    /**
     * Get redirect URL for Quran subscription.
     */
    private function getQuranSubscriptionRedirectUrl(Payment $payment, string $tenantUrl): ?string
    {
        $subscription = \App\Models\QuranSubscription::find($payment->subscription_id);

        if (! $subscription) {
            return null;
        }

        // Get the QuranTeacherProfile ID (not user_id)
        // The quran_teacher_id in subscription is actually the user_id
        $teacherProfile = \App\Models\QuranTeacherProfile::where('user_id', $subscription->quran_teacher_id)->first();

        if (! $teacherProfile) {
            return null;
        }

        $url = $tenantUrl.'/quran-teachers/'.$teacherProfile->id;

        // Add package if available
        if ($subscription->package_id) {
            $url .= '/subscribe/'.$subscription->package_id;
        }

        // Add billing cycle as period parameter
        $period = $this->getBillingCyclePeriod($subscription->billing_cycle ?? null);
        if ($period) {
            $url .= '?period='.$period;
        }

        return $url;
    }

    /**
     * Get redirect URL for Academic subscription.
     */
    private function getAcademicSubscriptionRedirectUrl(Payment $payment, string $tenantUrl): ?string
    {
        // Try to find by payable or metadata
        $subscription = null;

        if ($payment->payable_type === \App\Models\AcademicSubscription::class) {
            $subscription = $payment->payable;
        }

        if (! $subscription) {
            return null;
        }

        if (! $subscription->teacher_id || ! $subscription->academic_package_id) {
            return null;
        }

        $url = $tenantUrl.'/academic-packages/teachers/'.$subscription->teacher_id.'/subscribe/'.$subscription->academic_package_id;

        // Add billing cycle as period parameter
        $period = $this->getBillingCyclePeriod($subscription->billing_cycle ?? null);
        if ($period) {
            $url .= '?period='.$period;
        }

        return $url;
    }

    /**
     * Get redirect URL for Course subscription (Interactive or Recorded).
     */
    private function getCourseSubscriptionRedirectUrl(Payment $payment, string $tenantUrl): ?string
    {
        $subscription = null;

        if ($payment->payable_type === \App\Models\CourseSubscription::class) {
            $subscription = $payment->payable;
        }

        if (! $subscription) {
            return null;
        }

        // Interactive course
        if ($subscription->interactive_course_id) {
            return $tenantUrl.'/interactive-courses/'.$subscription->interactive_course_id;
        }

        // Recorded course
        if ($subscription->recorded_course_id) {
            return $tenantUrl.'/courses/'.$subscription->recorded_course_id;
        }

        return null;
    }

    /**
     * Get redirect URL from polymorphic payable relationship.
     */
    private function getPayableRedirectUrl(Payment $payment, string $tenantUrl): ?string
    {
        $payable = $payment->payable;

        if (! $payable) {
            return null;
        }

        // Handle based on payable type
        if ($payable instanceof \App\Models\QuranSubscription) {
            $teacherProfile = \App\Models\QuranTeacherProfile::where('user_id', $payable->quran_teacher_id)->first();
            if ($teacherProfile && $payable->package_id) {
                $url = $tenantUrl.'/quran-teachers/'.$teacherProfile->id.'/subscribe/'.$payable->package_id;
                $period = $this->getBillingCyclePeriod($payable->billing_cycle ?? null);

                return $period ? $url.'?period='.$period : $url;
            }
        }

        if ($payable instanceof \App\Models\AcademicSubscription) {
            if ($payable->teacher_id && $payable->academic_package_id) {
                $url = $tenantUrl.'/academic-packages/teachers/'.$payable->teacher_id.'/subscribe/'.$payable->academic_package_id;
                $period = $this->getBillingCyclePeriod($payable->billing_cycle ?? null);

                return $period ? $url.'?period='.$period : $url;
            }
        }

        if ($payable instanceof \App\Models\CourseSubscription) {
            if ($payable->interactive_course_id) {
                return $tenantUrl.'/interactive-courses/'.$payable->interactive_course_id;
            }
            if ($payable->recorded_course_id) {
                return $tenantUrl.'/courses/'.$payable->recorded_course_id;
            }
        }

        if ($payable instanceof \App\Models\InteractiveCourseEnrollment) {
            return $tenantUrl.'/interactive-courses/'.$payable->course_id;
        }

        return null;
    }

    /**
     * Convert BillingCycle enum to period string for URL.
     */
    private function getBillingCyclePeriod($billingCycle): ?string
    {
        if (! $billingCycle) {
            return 'monthly'; // Default
        }

        // Handle enum or string
        $value = is_object($billingCycle) ? $billingCycle->value : $billingCycle;

        return match ($value) {
            'monthly', 'month' => 'monthly',
            'quarterly', 'quarter' => 'quarterly',
            'yearly', 'year', 'annual' => 'yearly',
            default => 'monthly',
        };
    }

    /**
     * Build the tenant-aware base URL for redirects.
     */
    private function buildTenantUrl(Payment $payment): string
    {
        $academy = $payment->academy;

        if (! $academy) {
            return config('app.url');
        }

        $subdomain = $academy->subdomain;
        $domain = config('app.domain', 'itqan-platform.test');
        $scheme = app()->environment('local') ? 'http' : 'https';

        // Always include subdomain for tenant-scoped routes
        // Default to configured default academy if subdomain is empty
        if (empty($subdomain)) {
            $subdomain = DefaultAcademy::subdomain();
        }

        return $scheme.'://'.$subdomain.'.'.$domain;
    }

    /**
     * Get the appropriate gateway for a payment (academy-aware).
     */
    private function getGatewayForPayment(Payment $payment): EasyKashGateway
    {
        $academy = $payment->academy;

        if ($academy) {
            $factory = app(\App\Services\Payment\AcademyPaymentGatewayFactory::class);

            try {
                $gateway = $factory->getGateway($academy, 'easykash');
                if ($gateway instanceof EasyKashGateway) {
                    return $gateway;
                }
            } catch (\Exception $e) {
                Log::channel('payments')->warning('Could not get academy-specific gateway', [
                    'academy_id' => $academy->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fall back to global config
        return new EasyKashGateway(config('payments.gateways.easykash'));
    }
}
