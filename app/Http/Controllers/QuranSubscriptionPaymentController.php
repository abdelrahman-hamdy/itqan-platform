<?php

namespace App\Http\Controllers;

use App\Models\QuranSubscription;
use App\Models\Payment;
use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuranSubscriptionPaymentController extends Controller
{
    /**
     * Show payment form for Quran subscription
     */
    public function create(Request $request, $subscriptionId)
    {
        $academy = $request->academy ?? Academy::where('subdomain', 'itqan-academy')->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        if (!Auth::check() || Auth::user()->user_type !== 'student') {
            return redirect()->route('login', ['subdomain' => $academy->subdomain])
                ->with('error', 'يجب تسجيل الدخول كطالب للوصول لصفحة الدفع');
        }

        $user = Auth::user();

        // Get the subscription
        $subscription = QuranSubscription::where('academy_id', $academy->id)
            ->where('id', $subscriptionId)
            ->where('student_id', $user->id)
            ->where('payment_status', 'pending')
            ->with(['quranTeacher', 'package', 'student'])
            ->first();

        if (!$subscription) {
            return redirect()->route('student.profile', ['subdomain' => $academy->subdomain])
                ->with('error', 'لم يتم العثور على الاشتراك أو تم دفع رسومه مسبقاً');
        }

        // Calculate payment details
        $originalPrice = $subscription->total_price;
        $discountAmount = $subscription->discount_amount ?? 0;
        $finalPrice = $subscription->final_price;
        $taxAmount = $this->calculateTax((float) $finalPrice);
        $totalAmount = $finalPrice + $taxAmount;

        return view('payments.quran-subscription', compact(
            'academy',
            'subscription',
            'originalPrice',
            'discountAmount',
            'finalPrice',
            'taxAmount',
            'totalAmount'
        ));
    }

    /**
     * Process Quran subscription payment
     */
    public function store(Request $request, $subscriptionId)
    {
        $academy = $request->academy ?? Academy::where('subdomain', 'itqan-academy')->first();
        
        if (!$academy) {
            return response()->json(['error' => 'Academy not found'], 404);
        }

        if (!Auth::check() || Auth::user()->user_type !== 'student') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        
        $validated = $request->validate([
            'payment_method' => 'required|in:credit_card,mada,stc_pay,bank_transfer',
            'card_number' => 'required_if:payment_method,credit_card,mada|string',
            'expiry_month' => 'required_if:payment_method,credit_card,mada|integer|min:1|max:12',
            'expiry_year' => 'required_if:payment_method,credit_card,mada|integer|min:2024',
            'cvv' => 'required_if:payment_method,credit_card,mada|string|size:3',
            'cardholder_name' => 'required_if:payment_method,credit_card,mada|string|max:255'
        ]);

        // Get subscription
        $subscription = QuranSubscription::where('academy_id', $academy->id)
            ->where('id', $subscriptionId)
            ->where('student_id', $user->id)
            ->where('payment_status', 'pending')
            ->first();

        if (!$subscription) {
            return response()->json([
                'error' => 'لم يتم العثور على الاشتراك'
            ], 404);
        }

        $finalPrice = $subscription->final_price;
        $taxAmount = $this->calculateTax((float) $finalPrice);
        $totalAmount = $finalPrice + $taxAmount;

        try {
            DB::transaction(function() use ($user, $academy, $subscription, $validated, $totalAmount, $taxAmount) {
                
                $payment = Payment::create([
                    'academy_id' => $academy->id,
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'payment_code' => $this->generatePaymentCode($academy->id),
                    'payment_method' => $validated['payment_method'],
                    'payment_gateway' => $this->getGatewayForMethod($validated['payment_method']),
                    'payment_type' => 'quran_subscription',
                    'amount' => $totalAmount,
                    'currency' => $subscription->currency,
                    'tax_amount' => $taxAmount,
                    'tax_percentage' => 15, // Saudi VAT
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'created_by' => $user->id,
                ]);

                // Process payment with gateway
                $gatewayResult = $this->processWithGateway($payment, $validated);

                if ($gatewayResult['success']) {
                    // Mark payment as completed
                    $payment->update([
                        'status' => 'completed',
                        'payment_status' => 'completed',
                        'gateway_transaction_id' => $gatewayResult['data']['transaction_id'] ?? null,
                        'receipt_number' => $gatewayResult['data']['receipt_number'] ?? null,
                        'gateway_response' => $gatewayResult['data'],
                        'payment_date' => now(),
                        'processed_at' => now(),
                        'confirmed_at' => now(),
                    ]);
                    
                    // Update subscription
                    $subscription->update([
                        'payment_status' => 'current',
                        'subscription_status' => 'active',
                        'last_payment_at' => now(),
                        'last_payment_amount' => $totalAmount,
                    ]);

                    // TODO: Send payment confirmation email
                    // TODO: Send notification to teacher about new subscription
                    
                } else {
                    // Mark payment as failed
                    $payment->update([
                        'status' => 'failed',
                        'payment_status' => 'failed',
                        'failure_reason' => $gatewayResult['error'],
                        'gateway_response' => $gatewayResult['data'] ?? [],
                    ]);
                    throw new \Exception($gatewayResult['error']);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'تم الدفع بنجاح! مرحباً بك في رحلة تعلم القرآن الكريم',
                'redirect_url' => route('student.profile', ['subdomain' => $academy->subdomain])
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing Quran subscription payment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'حدث خطأ أثناء عملية الدفع. يرجى المحاولة مرة أخرى'
            ], 500);
        }
    }

    /**
     * Calculate tax amount
     */
    private function calculateTax(float $amount): float
    {
        // 15% VAT in Saudi Arabia
        return round($amount * 0.15, 2);
    }

    /**
     * Generate unique payment code
     */
    private function generatePaymentCode($academyId): string
    {
        $academyId = $academyId ?: 1;
        $prefix = 'QSP-' . str_pad($academyId, 2, '0', STR_PAD_LEFT) . '-';
        $timestamp = now()->format('ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $timestamp . '-' . $random;
    }

    /**
     * Get gateway for payment method
     */
    private function getGatewayForMethod(string $method): string
    {
        $gateways = [
            'credit_card' => 'moyasar',
            'mada' => 'moyasar',
            'stc_pay' => 'stc_pay',
            'bank_transfer' => 'manual'
        ];

        return $gateways[$method] ?? 'moyasar';
    }

    /**
     * Process payment with external gateway
     */
    private function processWithGateway(Payment $payment, array $paymentData): array
    {
        // This is a mock implementation
        // In real application, you would integrate with actual payment gateways
        
        switch ($payment->payment_gateway) {
            case 'moyasar':
                return $this->processMoyasarPayment($payment, $paymentData);
            case 'stc_pay':
                return $this->processStcPayPayment($payment, $paymentData);
            default:
                return [
                    'success' => false,
                    'error' => 'Payment gateway not supported'
                ];
        }
    }

    /**
     * Mock Moyasar payment processing
     */
    private function processMoyasarPayment(Payment $payment, array $paymentData): array
    {
        // Mock successful payment
        // In real implementation, you would call Moyasar API
        
        return [
            'success' => true,
            'data' => [
                'transaction_id' => 'QS_TXN_' . time(),
                'receipt_number' => 'QS_REC_' . $payment->id . '_' . time(),
                'gateway_response' => 'Quran subscription payment processed successfully'
            ]
        ];
    }

    /**
     * Mock STC Pay payment processing
     */
    private function processStcPayPayment(Payment $payment, array $paymentData): array
    {
        // Mock successful payment
        return [
            'success' => true,
            'data' => [
                'transaction_id' => 'QS_STC_' . time(),
                'receipt_number' => 'QS_STC_REC_' . $payment->id . '_' . time(),
                'gateway_response' => 'Quran subscription payment via STC Pay processed successfully'
            ]
        ];
    }
}