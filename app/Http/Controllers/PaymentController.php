<?php

namespace App\Http\Controllers;

use App\Constants\DefaultAcademy;
use App\Http\Requests\ProcessCourseEnrollmentPaymentRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Academy;
use App\Models\CourseSubscription;
use App\Models\Payment;
use App\Models\RecordedCourse;
use App\Services\Payment\InvoiceService;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    use ApiResponses;

    /**
     * Show payment form for course enrollment
     */
    public function create(RecordedCourse $course): View|RedirectResponse
    {
        if (! Auth::check()) {
            $subdomain = request()->route('subdomain') ?? DefaultAcademy::subdomain();

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
                    'payment_gateway' => $validated['payment_gateway'] ?? $this->getGatewayForMethod($validated['payment_method']),
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
                    throw new Exception($gatewayResult['error']);
                }
            });

            return $this->success([
                'success' => true,
                'message' => 'تم الدفع بنجاح',
                'redirect_url' => route('courses.learn', $course),
            ]);

        } catch (Exception $e) {
            return $this->error('فشل في عملية الدفع: '.$e->getMessage(), 400);
        }
    }

    /**
     * Show payment success page
     */
    public function showSuccess($payment): View
    {
        // Load payment without global scopes (for cross-academy callbacks)
        if (is_string($payment) || is_int($payment)) {
            $payment = Payment::withoutGlobalScopes()->findOrFail($payment);
        }

        // If the user is authenticated, enforce ownership (prevents viewing others' receipts).
        // We intentionally skip auth for unauthenticated visits because payment-gateway
        // redirect callbacks (e.g. EasyKash) may arrive without a user session.
        if (auth()->check() && auth()->id() !== (int) $payment->user_id) {
            abort(403);
        }

        $payment->load(['payable', 'user']);

        // Check if this was a mobile-initiated purchase
        if (session('purchase_source') === 'mobile') {
            $subscription = $payment->payable;

            // Generate deeplink back to mobile app
            $deeplinkUrl = 'itqan://purchase-complete?'.http_build_query([
                'subscription_id' => $subscription?->id,
                'subscription_type' => $subscription ? class_basename(get_class($subscription)) : null,
                'status' => 'success',
            ]);

            return view('payments.mobile-success', [
                'payment' => $payment,
                'subscription' => $subscription,
                'deeplink_url' => $deeplinkUrl,
                'auto_redirect_seconds' => 5,
            ]);
        }

        // Normal web success flow
        return view('payments.success', compact('payment'));
    }

    /**
     * Show payment failed page
     */
    public function showFailed(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['payable', 'user']);

        return view('payments.failed', compact('payment'));
    }

    /**
     * Download payment invoice PDF
     */
    public function downloadReceipt(Request $request): StreamedResponse
    {
        // No academy context in payment receipt download — intentional cross-tenant access
        $payment = Payment::withoutGlobalScopes()->findOrFail($request->route('payment'));

        $this->authorize('downloadReceipt', $payment);

        if (! $payment->is_successful) {
            abort(404, 'لا يمكن تحميل فاتورة لدفعة غير مكتملة');
        }

        $invoiceService = app(InvoiceService::class);
        $pdfPath = $invoiceService->getOrGeneratePdf($payment);

        if (! $pdfPath || ! Storage::disk('local')->exists($pdfPath)) {
            abort(404, 'الفاتورة غير متوفرة');
        }

        return Storage::disk('local')->download($pdfPath, 'invoice-'.$payment->payment_code.'.pdf');
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
        switch ($payment->payment_gateway) {
            case 'easykash':
                $paymentService = app(PaymentService::class);

                return $paymentService->processPayment($payment);
            default:
                return [
                    'success' => false,
                    'error' => 'Payment gateway not supported',
                ];
        }
    }
}
