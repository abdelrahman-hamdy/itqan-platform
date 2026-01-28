<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessCourseEnrollmentPaymentRequest;
use App\Http\Requests\ProcessPaymentRefundRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Academy;
use App\Models\CourseSubscription;
use App\Models\Payment;
use App\Models\RecordedCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use ApiResponses;

    /**
     * Show payment form for course enrollment
     */
    public function create(RecordedCourse $course): View|RedirectResponse
    {
        if (! Auth::check()) {
            $subdomain = request()->route('subdomain') ?? 'itqan-academy';

            return redirect()->route('login', ['subdomain' => $subdomain]);
        }

        $this->authorize('create', Payment::class);

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
        $finalPrice = $course->price;
        $taxAmount = $this->calculateTax($finalPrice);
        $totalAmount = $finalPrice + $taxAmount;

        return view('payments.create', compact(
            'course',
            'enrollment',
            'finalPrice',
            'taxAmount',
            'totalAmount'
        ));
    }

    /**
     * Process payment
     */
    public function store(ProcessCourseEnrollmentPaymentRequest $request, RecordedCourse $course): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $user = Auth::user();

        $validated = $request->validated();

        // Get enrollment
        $enrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('payment_status', 'pending')
            ->first();

        if (! $enrollment) {
            return $this->notFound('لم يتم العثور على طلب التسجيل');
        }

        $finalPrice = $course->price;
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
                    'currency' => getCurrencyCode(null, $course->academy), // Always use academy's configured currency
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

            return $this->success([
                'success' => true,
                'message' => 'تم الدفع بنجاح',
                'redirect_url' => route('courses.learn', $course),
            ]);

        } catch (\Exception $e) {
            return $this->error('فشل في عملية الدفع: '.$e->getMessage(), 400);
        }
    }

    /**
     * Show payment success page
     */
    public function showSuccess(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['subscription', 'user']);

        return view('payments.success', compact('payment'));
    }

    /**
     * Show payment failed page
     */
    public function showFailed(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['subscription', 'user']);

        return view('payments.failed', compact('payment'));
    }

    /**
     * Process refund request
     */
    public function refund(ProcessPaymentRefundRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('refund', $payment);

        $validated = $request->validated();

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

            return $this->success(null, 'تم معالجة طلب الاسترداد بنجاح');

        } catch (\Exception $e) {
            return $this->error('فشل في معالجة الاسترداد: '.$e->getMessage(), 400);
        }
    }

    /**
     * Show user's payment history
     */
    public function history(): View|RedirectResponse
    {
        if (! Auth::check()) {
            $subdomain = request()->route('subdomain') ?? 'itqan-academy';

            return redirect()->route('login', ['subdomain' => $subdomain]);
        }

        $this->authorize('viewAny', Payment::class);

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
    public function downloadReceipt(Payment $payment): RedirectResponse
    {
        $this->authorize('downloadReceipt', $payment);

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
    public function getPaymentMethods(Academy $academy): JsonResponse
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

        return $this->success(['methods' => $methods]);
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
            'credit_card' => 'easykash',
            'card' => 'easykash',
            'mada' => 'easykash',
            'wallet' => 'easykash',
            'fawry' => 'easykash',
            'aman' => 'easykash',
            'meeza' => 'easykash',
            'easykash' => 'easykash',
            'stc_pay' => 'stc_pay',
            'bank_transfer' => 'manual',
        ];

        return $gateways[$method] ?? 'easykash';
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
