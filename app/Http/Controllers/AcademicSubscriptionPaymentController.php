<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\UserType;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSubscription;
use App\Models\Academy;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AcademicSubscriptionPaymentController extends Controller
{
    use ApiResponses;

    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Show payment form for academic subscription
     */
    public function create(Request $request, $subdomain, $subscriptionId): View|RedirectResponse
    {
        $academy = $request->academy ?? Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        if (! Auth::check() || Auth::user()->user_type !== UserType::STUDENT->value) {
            return redirect()->route('login', ['subdomain' => $academy->subdomain])
                ->with('error', __('payments.academic_payment.login_required'));
        }

        $user = Auth::user();

        // Get the subscription (must be pending payment)
        $subscription = AcademicSubscription::where('academy_id', $academy->id)
            ->where('id', $subscriptionId)
            ->where('student_id', $user->id)
            ->where('payment_status', SubscriptionPaymentStatus::PENDING)
            ->with(['teacher.user', 'subject', 'gradeLevel'])
            ->first();

        if (! $subscription) {
            return redirect()->route('student.subscriptions', ['subdomain' => $academy->subdomain])
                ->with('error', __('payments.academic_payment.not_found'));
        }

        // Calculate payment details
        $finalPrice = $subscription->final_price ?? $subscription->monthly_price;
        $discountAmount = $subscription->discount_amount ?? 0;
        $originalPrice = $finalPrice + $discountAmount;
        $taxAmount = $this->calculateTax((float) $finalPrice);
        $totalAmount = $finalPrice + $taxAmount;

        // Get available payment methods
        $paymentMethods = $this->paymentService->getAvailablePaymentMethods($academy);

        return view('payments.academic-subscription', compact(
            'academy',
            'subscription',
            'originalPrice',
            'discountAmount',
            'finalPrice',
            'taxAmount',
            'totalAmount',
            'paymentMethods'
        ));
    }

    /**
     * Process academic subscription payment
     */
    public function store(Request $request, $subdomain, $subscriptionId): JsonResponse
    {
        $academy = $request->academy ?? Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            return $this->notFound('Academy not found');
        }

        $user = Auth::user();

        if (! $user || $user->user_type !== UserType::STUDENT->value) {
            return $this->unauthorized(__('payments.academic_payment.login_required'));
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:credit_card,mada,stc_pay,paymob,tapay,bank_transfer',
            'card_number' => 'required_if:payment_method,credit_card,mada,paymob,tapay|string',
            'expiry_month' => 'required_if:payment_method,credit_card,mada,paymob,tapay|integer|min:1|max:12',
            'expiry_year' => 'required_if:payment_method,credit_card,mada,paymob,tapay|integer|min:2024',
            'cvv' => 'required_if:payment_method,credit_card,mada,paymob,tapay|string|size:3',
            'cardholder_name' => 'required_if:payment_method,credit_card,mada,paymob,tapay|string|max:255',
            'phone' => 'required_if:payment_method,stc_pay|string',
            'payment_gateway' => 'nullable|string',
        ]);

        // Get subscription
        $subscription = AcademicSubscription::where('academy_id', $academy->id)
            ->where('id', $subscriptionId)
            ->where('student_id', $user->id)
            ->where('payment_status', SubscriptionPaymentStatus::PENDING)
            ->first();

        if (! $subscription) {
            return $this->notFound(__('payments.academic_payment.not_found'));
        }

        $finalPrice = $subscription->final_price ?? $subscription->monthly_price;
        $taxAmount = $this->calculateTax((float) $finalPrice);
        $totalAmount = $finalPrice + $taxAmount;

        try {
            // Cancel any previous failed/pending payments for this subscription
            Payment::where('subscription_id', $subscription->id)
                ->where('payment_type', 'subscription')
                ->where('status', 'pending')
                ->update(['status' => 'cancelled', 'payment_status' => 'cancelled']);

            // Create payment record
            $payment = Payment::create([
                'academy_id' => $academy->id,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'payment_code' => $this->generatePaymentCode($academy->id),
                'payment_method' => $validated['payment_method'],
                'payment_gateway' => $validated['payment_gateway'] ?? $this->getGatewayForMethod($validated['payment_method']),
                'payment_type' => 'subscription',
                'amount' => $totalAmount,
                'net_amount' => $finalPrice,
                'currency' => getCurrencyCode(null, $academy),
                'tax_amount' => $taxAmount,
                'tax_percentage' => 15,
                'status' => 'pending',
                'payment_status' => 'pending',
                'created_by' => $user->id,
            ]);

            // Process payment with gateway
            $gatewayResult = $this->paymentService->processPayment($payment, $validated);

            // Handle redirect-based gateways
            if (! empty($gatewayResult['redirect_url'])) {
                $payment->update([
                    'gateway_intent_id' => $gatewayResult['transaction_id'] ?? null,
                    'gateway_response' => $gatewayResult['data'] ?? [],
                ]);

                Log::info('Academic subscription payment redirect', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'redirect_url' => $gatewayResult['redirect_url'],
                ]);

                return $this->success([
                    'redirect_url' => $gatewayResult['redirect_url'],
                    'requires_redirect' => true,
                ], __('payments.academic_payment.redirecting'));
            }

            if ($gatewayResult['success'] ?? false) {
                // Immediate payment success
                DB::transaction(function () use ($payment, $gatewayResult, $subscription) {
                    $payment->update([
                        'status' => SessionStatus::COMPLETED,
                        'payment_status' => 'completed',
                        'gateway_transaction_id' => $gatewayResult['data']['transaction_id'] ?? null,
                        'receipt_number' => $gatewayResult['data']['receipt_number'] ?? null,
                        'gateway_response' => $gatewayResult['data'],
                        'payment_date' => now(),
                        'processed_at' => now(),
                        'confirmed_at' => now(),
                    ]);

                    $subscription->activateFromPayment($payment);
                });

                return $this->success([
                    'redirect_url' => route('student.academic-subscriptions.show', [
                        'subdomain' => $subscription->academy->subdomain,
                        'subscriptionId' => $subscription->id,
                    ]),
                ], __('payments.academic_payment.success'));
            } else {
                $payment->update([
                    'status' => 'failed',
                    'payment_status' => 'failed',
                    'failure_reason' => $gatewayResult['error'] ?? 'Unknown error',
                    'gateway_response' => $gatewayResult['data'] ?? [],
                ]);

                return $this->error($gatewayResult['error'] ?? __('payments.academic_payment.failed'), 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing academic subscription payment: '.$e->getMessage(), [
                'subscription_id' => $subscriptionId,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->serverError(__('payments.academic_payment.error'));
        }
    }

    private function calculateTax(float $amount): float
    {
        return round($amount * 0.15, 2);
    }

    private function generatePaymentCode($academyId): string
    {
        $prefix = 'ASP-'.str_pad($academyId ?: 1, 2, '0', STR_PAD_LEFT).'-';
        $timestamp = now()->format('ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix.$timestamp.'-'.$random;
    }

    private function getGatewayForMethod(string $method): string
    {
        $gateways = [
            'credit_card' => 'easykash',
            'card' => 'easykash',
            'mada' => 'easykash',
            'wallet' => 'easykash',
            'easykash' => 'easykash',
            'stc_pay' => 'stc_pay',
            'paymob' => 'paymob',
            'tapay' => 'tapay',
            'bank_transfer' => 'manual',
        ];

        return $gateways[$method] ?? 'easykash';
    }
}
