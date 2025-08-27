<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\CourseSubscription;
use App\Models\Payment;
use App\Models\RecordedCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Show payment form for course enrollment
     */
    public function create(RecordedCourse $course)
    {
        if (! Auth::check()) {
            $subdomain = request()->route('subdomain') ?? 'itqan-academy';

            return redirect()->route('login', ['subdomain' => $subdomain]);
        }

        $user = Auth::user();

        // Check if course enrollment exists and is pending payment
        $enrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('payment_status', 'pending')
            ->first();

        if (! $enrollment) {
            return redirect()->route('courses.show', $course)
                ->with('error', 'لم يتم العثور على طلب التسجيل');
        }

        if ($course->is_free) {
            return redirect()->route('courses.enroll', $course);
        }

        $course->load(['academy']);

        // Calculate payment details
        $originalPrice = $course->price;
        $discountAmount = $course->discount_price ? ($originalPrice - $course->discount_price) : 0;
        $finalPrice = $course->discount_price ?? $originalPrice;
        $taxAmount = $this->calculateTax($finalPrice);
        $totalAmount = $finalPrice + $taxAmount;

        return view('payments.create', compact(
            'course',
            'enrollment',
            'originalPrice',
            'discountAmount',
            'finalPrice',
            'taxAmount',
            'totalAmount'
        ));
    }

    /**
     * Process payment
     */
    public function store(Request $request, RecordedCourse $course)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        $validated = $request->validate([
            'payment_method' => 'required|in:credit_card,mada,stc_pay,bank_transfer',
            'card_number' => 'required_if:payment_method,credit_card,mada|string',
            'expiry_month' => 'required_if:payment_method,credit_card,mada|integer|min:1|max:12',
            'expiry_year' => 'required_if:payment_method,credit_card,mada|integer|min:2024',
            'cvv' => 'required_if:payment_method,credit_card,mada|string|size:3',
            'cardholder_name' => 'required_if:payment_method,credit_card,mada|string|max:255',
        ]);

        // Get enrollment
        $enrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('payment_status', 'pending')
            ->first();

        if (! $enrollment) {
            return response()->json([
                'error' => 'لم يتم العثور على طلب التسجيل',
            ], 404);
        }

        $finalPrice = $course->discount_price ?? $course->price;
        $taxAmount = $this->calculateTax($finalPrice);
        $totalAmount = $finalPrice + $taxAmount;

        try {
            DB::transaction(function () use ($user, $course, $enrollment, $validated, $totalAmount, $taxAmount) {
                // Create payment record
                $payment = Payment::createPayment([
                    'academy_id' => $course->academy_id,
                    'user_id' => $user->id,
                    'subscription_id' => null, // This is for course subscription, not general subscription
                    'payment_method' => $validated['payment_method'],
                    'payment_gateway' => $this->getGatewayForMethod($validated['payment_method']),
                    'payment_type' => 'course_enrollment',
                    'amount' => $totalAmount,
                    'currency' => $course->currency,
                    'tax_amount' => $taxAmount,
                    'tax_percentage' => 15, // Saudi VAT
                    'status' => 'pending',
                    'payment_status' => 'pending',
                ]);

                // Process payment with gateway
                $gatewayResult = $this->processWithGateway($payment, $validated);

                if ($gatewayResult['success']) {
                    // Mark payment as completed
                    $payment->markAsCompleted($gatewayResult['data']);

                    // Update enrollment
                    $enrollment->update([
                        'payment_status' => 'paid',
                        'status' => 'active',
                    ]);
                } else {
                    // Mark payment as failed
                    $payment->markAsFailed($gatewayResult['error'], $gatewayResult['data'] ?? []);
                    throw new \Exception($gatewayResult['error']);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'تم الدفع بنجاح',
                'redirect_url' => route('courses.learn', $course),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'فشل في عملية الدفع: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Show payment success page
     */
    public function success(Payment $payment)
    {
        if (! Auth::check() || $payment->user_id !== Auth::id()) {
            abort(403);
        }

        $payment->load(['subscription', 'user']);

        return view('payments.success', compact('payment'));
    }

    /**
     * Show payment failed page
     */
    public function failed(Payment $payment)
    {
        if (! Auth::check() || $payment->user_id !== Auth::id()) {
            abort(403);
        }

        $payment->load(['subscription', 'user']);

        return view('payments.failed', compact('payment'));
    }

    /**
     * Process refund request
     */
    public function refund(Request $request, Payment $payment)
    {
        $this->authorize('refund', $payment);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$payment->refundable_amount,
            'reason' => 'required|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($payment, $validated) {
                // Process refund with gateway
                $gatewayResult = $this->processRefundWithGateway($payment, $validated['amount']);

                if ($gatewayResult['success']) {
                    $payment->processRefund($validated['amount'], $validated['reason']);
                } else {
                    throw new \Exception($gatewayResult['error']);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'تم معالجة طلب الاسترداد بنجاح',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'فشل في معالجة الاسترداد: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Show user's payment history
     */
    public function history()
    {
        if (! Auth::check()) {
            $subdomain = request()->route('subdomain') ?? 'itqan-academy';

            return redirect()->route('login', ['subdomain' => $subdomain]);
        }

        $user = Auth::user();

        $payments = Payment::where('user_id', $user->id)
            ->with(['subscription', 'academy'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('payments.history', compact('payments'));
    }

    /**
     * Download payment receipt
     */
    public function downloadReceipt(Payment $payment)
    {
        if (! Auth::check() || $payment->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $payment->is_successful) {
            abort(404, 'لا يمكن تحميل إيصال لدفعة غير مكتملة');
        }

        // Generate receipt if not exists
        if (! $payment->receipt_url) {
            $receiptUrl = $payment->generateReceipt();
        } else {
            $receiptUrl = $payment->receipt_url;
        }

        return redirect($receiptUrl);
    }

    /**
     * Get payment methods for academy
     */
    public function getPaymentMethods(Academy $academy)
    {
        // This would return available payment methods based on academy settings
        $methods = [
            'credit_card' => [
                'name' => 'بطاقة ائتمان',
                'icon' => 'credit-card',
                'enabled' => true,
            ],
            'mada' => [
                'name' => 'مدى',
                'icon' => 'mada',
                'enabled' => true,
            ],
            'stc_pay' => [
                'name' => 'STC Pay',
                'icon' => 'stc-pay',
                'enabled' => true,
            ],
            'bank_transfer' => [
                'name' => 'تحويل بنكي',
                'icon' => 'bank',
                'enabled' => false,
            ],
        ];

        return response()->json([
            'success' => true,
            'methods' => $methods,
        ]);
    }

    /**
     * Calculate tax amount (VAT)
     */
    private function calculateTax(float $amount): float
    {
        return ($amount * 15) / 100; // 15% VAT in Saudi Arabia
    }

    /**
     * Get payment gateway for method
     */
    private function getGatewayForMethod(string $method): string
    {
        $gateways = [
            'credit_card' => 'moyasar',
            'mada' => 'moyasar',
            'stc_pay' => 'stc_pay',
            'bank_transfer' => 'manual',
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
                    'error' => 'Payment gateway not supported',
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
                'transaction_id' => 'TXN_'.time(),
                'receipt_number' => 'REC_'.$payment->id.'_'.time(),
                'gateway_response' => 'Payment processed successfully',
            ],
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
                'transaction_id' => 'STC_'.time(),
                'receipt_number' => 'STC_REC_'.$payment->id.'_'.time(),
                'gateway_response' => 'STC Pay payment processed successfully',
            ],
        ];
    }

    /**
     * Process refund with gateway
     */
    private function processRefundWithGateway(Payment $payment, float $amount): array
    {
        // Mock refund processing
        return [
            'success' => true,
            'data' => [
                'refund_id' => 'REF_'.time(),
                'refunded_amount' => $amount,
            ],
        ];
    }
}
