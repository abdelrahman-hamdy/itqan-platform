<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Enums\SubscriptionStatus;
use App\Models\QuranSubscription;
use App\Models\Payment;
use App\Models\Academy;
use App\Services\PaymentService;
use App\Http\Requests\ProcessQuranSubscriptionPaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\SessionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class QuranSubscriptionPaymentController extends Controller
{
    use ApiResponses;
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Show payment form for Quran subscription
     */
    public function create(Request $request, $subscriptionId): View|RedirectResponse
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

        // Get available payment methods
        $paymentMethods = $this->paymentService->getAvailablePaymentMethods($academy);

        return view('payments.quran-subscription', compact(
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
     * Process Quran subscription payment
     */
    public function store(ProcessQuranSubscriptionPaymentRequest $request, $subscriptionId): JsonResponse
    {
        $academy = $request->academy ?? Academy::where('subdomain', 'itqan-academy')->first();

        if (!$academy) {
            return $this->notFound('Academy not found');
        }

        $user = Auth::user();

        $validated = $request->validated();

        // Get subscription
        $subscription = QuranSubscription::where('academy_id', $academy->id)
            ->where('id', $subscriptionId)
            ->where('student_id', $user->id)
            ->where('payment_status', 'pending')
            ->first();

        if (!$subscription) {
            return $this->notFound('لم يتم العثور على الاشتراك');
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
                $gatewayResult = $this->paymentService->processPayment($payment, $validated);

                if ($gatewayResult['success']) {
                    // Mark payment as completed
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
                    
                    // Update subscription
                    $subscription->update([
                        'payment_status' => 'current',
                        'status' => SubscriptionStatus::ACTIVE,
                        'last_payment_at' => now(),
                        'last_payment_amount' => $totalAmount,
                    ]);

                    // Send payment confirmation email to student
                    $this->sendPaymentConfirmation($user, $subscription, $payment, $totalAmount);

                    // Send notification to teacher about new subscription
                    $this->notifyTeacherAboutNewSubscription($subscription);
                    
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

            return $this->success([
                'redirect_url' => route('student.profile', ['subdomain' => $academy->subdomain])
            ], 'تم الدفع بنجاح! مرحباً بك في رحلة تعلم القرآن الكريم');

        } catch (\Exception $e) {
            Log::error('Error processing Quran subscription payment: ' . $e->getMessage());

            return $this->serverError('حدث خطأ أثناء عملية الدفع. يرجى المحاولة مرة أخرى');
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
            'paymob' => 'paymob',
            'tapay' => 'tapay',
            'bank_transfer' => 'manual'
        ];

        return $gateways[$method] ?? 'moyasar';
    }

    /**
     * Send payment confirmation notification to student
     */
    private function sendPaymentConfirmation($user, $subscription, $payment, $totalAmount): void
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);

            $paymentData = [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->gateway_transaction_id,
                'amount' => $totalAmount,
                'currency' => $subscription->currency ?? 'SAR',
                'description' => 'اشتراك القرآن الكريم',
                'subscription_id' => $subscription->id,
                'subscription_type' => 'quran',
            ];

            $notificationService->sendPaymentSuccessNotification($user, $paymentData);

            Log::info('Payment confirmation sent to student', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify teacher about new subscription
     */
    private function notifyTeacherAboutNewSubscription($subscription): void
    {
        try {
            $teacher = $subscription->quranTeacher;
            if (!$teacher) {
                Log::warning('Cannot notify teacher: teacher not found', [
                    'subscription_id' => $subscription->id,
                ]);
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);

            $student = $subscription->student;
            $studentName = $student ? ($student->first_name . ' ' . $student->last_name) : 'طالب جديد';

            $notificationService->send(
                $teacher,
                \App\Enums\NotificationType::SUBSCRIPTION_ACTIVATED,
                [
                    'student_name' => $studentName,
                    'subscription_type' => 'قرآن كريم',
                    'package_name' => $subscription->package?->name ?? 'باقة قرآن',
                    'start_date' => $subscription->starts_at?->format('Y-m-d'),
                ],
                route('teacher.quran-circles.index'),
                [
                    'subscription_id' => $subscription->id,
                    'student_id' => $subscription->student_id,
                ],
                true
            );

            Log::info('Teacher notified about new subscription', [
                'teacher_id' => $teacher->id,
                'subscription_id' => $subscription->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to notify teacher about new subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}