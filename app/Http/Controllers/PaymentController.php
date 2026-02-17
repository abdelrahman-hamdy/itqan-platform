<?php

namespace App\Http\Controllers;

use Exception;
use App\Services\Payment\InvoiceService;
use App\Services\PaymentService;
use App\Services\Payment\AcademyPaymentGatewayFactory;
use App\Constants\DefaultAcademy;
use App\Http\Requests\ProcessCourseEnrollmentPaymentRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Academy;
use App\Models\CourseSubscription;
use App\Models\Payment;
use App\Models\RecordedCourse;
use App\Models\SavedPaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        // Don't authorize - success page should be accessible to anyone with the URL
        // (EasyKash callbacks may not have user session)

        $payment->load(['subscription', 'user']);

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

        $payment->load(['subscription', 'user']);

        return view('payments.failed', compact('payment'));
    }

    /**
     * Download payment invoice PDF
     */
    public function downloadReceipt(Request $request): StreamedResponse
    {
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

    /**
     * Handle Paymob tokenization callback after card save.
     */
    public function tokenizationCallback(Request $request): RedirectResponse
    {
        $subdomain = $request->route('subdomain') ?? DefaultAcademy::subdomain();

        Log::channel('payments')->info('Tokenization callback received', [
            'all_params' => $request->all(),
            'user_id' => Auth::id(),
        ]);

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('student.payments', ['subdomain' => $subdomain])
                ->with('error', __('student.saved_payment_methods.tokenization_failed'));
        }

        // Get academy
        $academy = $user->academy ?? Academy::where('subdomain', $subdomain)->first();
        if (! $academy) {
            return redirect()->route('student.payments', ['subdomain' => $subdomain])
                ->with('error', __('student.saved_payment_methods.tokenization_failed'));
        }

        // Check if tokenization was successful
        $isSuccess = $request->input('success') === 'true' || $request->input('success') === true;

        if (! $isSuccess) {
            Log::channel('payments')->warning('Tokenization failed', [
                'user_id' => $user->id,
                'response' => $request->all(),
            ]);

            return redirect()->route('student.payments', ['subdomain' => $subdomain])
                ->with('error', __('student.saved_payment_methods.tokenization_failed'));
        }

        // Get transaction ID from callback
        $transactionId = $request->input('id');
        if (! $transactionId) {
            Log::channel('payments')->error('No transaction ID in callback', [
                'user_id' => $user->id,
                'response' => $request->all(),
            ]);

            return redirect()->route('student.payments', ['subdomain' => $subdomain])
                ->with('error', __('student.saved_payment_methods.tokenization_failed'));
        }

        try {
            // Call Paymob API to get full transaction details including card token
            $gateway = app(AcademyPaymentGatewayFactory::class)
                ->getGateway($academy, 'paymob');

            $verifyResult = $gateway->verifyPayment($transactionId);

            if (! $verifyResult->isSuccessful()) {
                Log::channel('payments')->error('Failed to verify tokenization transaction', [
                    'user_id' => $user->id,
                    'transaction_id' => $transactionId,
                    'error' => $verifyResult->errorMessage,
                ]);

                return redirect()->route('student.payments', ['subdomain' => $subdomain])
                    ->with('error', __('student.saved_payment_methods.tokenization_failed'));
            }

            // Extract card token from API response
            $cardToken = $verifyResult->metadata['card_token'] ?? null;
            $cardBrand = $verifyResult->metadata['card_brand'] ?? 'unknown';
            $lastFour = $verifyResult->metadata['card_last_four'] ?? '****';

            // Also try to get from raw response if not in metadata
            if (! $cardToken && isset($verifyResult->rawResponse['source_data']['token'])) {
                $cardToken = $verifyResult->rawResponse['source_data']['token'];
                $cardBrand = $verifyResult->rawResponse['source_data']['sub_type'] ?? 'unknown';
                $lastFour = substr($verifyResult->rawResponse['source_data']['pan'] ?? '', -4) ?: '****';
            }

            if (! $cardToken) {
                Log::channel('payments')->error('No card token in Paymob API response', [
                    'user_id' => $user->id,
                    'transaction_id' => $transactionId,
                    'raw_response' => $verifyResult->rawResponse,
                ]);

                return redirect()->route('student.payments', ['subdomain' => $subdomain])
                    ->with('error', __('student.saved_payment_methods.tokenization_failed'));
            }

            // Check if card already exists
            $existingCard = SavedPaymentMethod::where('user_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('token', $cardToken)
                ->first();

            if ($existingCard) {
                return redirect()->route('student.payments', ['subdomain' => $subdomain])
                    ->with('info', __('student.saved_payment_methods.card_already_saved'));
            }

            // Check if this is the first card (will be default)
            $hasExistingCards = SavedPaymentMethod::where('user_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('gateway', 'paymob')
                ->where('is_active', true)
                ->exists();

            // Save the card
            $savedMethod = SavedPaymentMethod::create([
                'academy_id' => $academy->id,
                'user_id' => $user->id,
                'gateway' => 'paymob',
                'token' => $cardToken,
                'type' => 'card',
                'brand' => strtolower($cardBrand),
                'last_four' => $lastFour,
                'expiry_month' => null,
                'expiry_year' => null,
                'holder_name' => $user->name,
                'is_default' => ! $hasExistingCards,
                'is_active' => true,
                'metadata' => [
                    'saved_from' => 'tokenization_callback',
                    'transaction_id' => $transactionId,
                    'raw_response' => $request->except(['token', 'card_token']),
                ],
            ]);

            Log::channel('payments')->info('Card saved from tokenization callback', [
                'user_id' => $user->id,
                'saved_payment_method_id' => $savedMethod->id,
                'brand' => $cardBrand,
                'last_four' => $lastFour,
            ]);

            return redirect()->route('student.payments', ['subdomain' => $subdomain])
                ->with('success', __('student.saved_payment_methods.card_saved_success'));

        } catch (Exception $e) {
            Log::channel('payments')->error('Failed to save card from callback', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('student.payments', ['subdomain' => $subdomain])
                ->with('error', __('student.saved_payment_methods.tokenization_failed'));
        }
    }
}
