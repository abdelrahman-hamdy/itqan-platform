<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourse;
use App\Models\Payment;
use App\Models\QuranSubscription;
use App\Models\RecordedCourse;
use App\Models\SavedPaymentMethod;
use App\Services\Payment\PaymentMethodService;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentApiController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected PaymentService $paymentService,
        protected PaymentMethodService $paymentMethodService
    ) {}

    /**
     * Create a payment intent for Paymob.
     * Returns iframe URL or redirect URL for the payment gateway.
     */
    public function createIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => 'required|string|size:3',
            'payment_type' => 'required|string|in:course,subscription,session,service',
            'payment_method' => 'nullable|string|in:card,wallet,apple_pay',
            'course_id' => 'nullable|integer|required_if:payment_type,course',
            'subscription_id' => 'nullable|integer|required_if:payment_type,subscription',
            'save_card' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized('يجب تسجيل الدخول أولاً');
        }

        try {
            // Get current academy from context
            $academyId = $user->academy_id ?? request()->get('academy_id');

            // SECURITY: Resolve canonical amount from DB — never trust client-supplied amount.
            $canonicalAmountCents = $this->resolveCanonicalAmountCents($validated, $academyId);
            if ($canonicalAmountCents === null) {
                return $this->error('لم يتم العثور على السعر المطلوب', 404);
            }

            // Create payment record first
            $payment = Payment::createPayment([
                'academy_id' => $academyId,
                'user_id' => $user->id,
                'amount' => $canonicalAmountCents / 100, // Convert from cents
                'currency' => $validated['currency'],
                'payment_type' => $validated['payment_type'],
                'payment_method' => $validated['payment_method'] ?? 'card',
                'payment_gateway' => 'paymob',
                'status' => 'pending',
                'payment_status' => 'pending',
                'save_card' => $validated['save_card'] ?? false,
            ]);

            // Create payment intent with Paymob
            $gateway = $this->paymentService->gateway('paymob');

            $result = $gateway->createPaymentIntent([
                'amount' => $canonicalAmountCents,
                'currency' => $validated['currency'],
                'payment_method' => $validated['payment_method'] ?? 'card',
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                    'phone' => $user->phone,
                ],
                'metadata' => [
                    'payment_id' => $payment->id,
                    'user_id' => $user->id,
                    'payment_type' => $validated['payment_type'],
                    'save_card' => $validated['save_card'] ?? false,
                ],
            ]);

            if ($result->isSuccessful()) {
                // Update payment with gateway info
                $payment->update([
                    'gateway_payment_id' => $result->transactionId,
                ]);

                return $this->success([
                    'success' => true,
                    'iframe_url' => $result->redirectUrl,
                    'payment_id' => $payment->id,
                ]);
            }

            // Mark payment as failed
            $payment->markAsFailed($result->errorMessage);

            return $this->error($result->errorMessage ?? 'فشل إنشاء عملية الدفع', 400);

        } catch (Exception $e) {
            Log::error('Payment intent creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'data' => $validated,
            ]);

            return $this->error(__('payments.service.unexpected_processing_error'), 500);
        }
    }

    /**
     * Resolve the canonical price in cents from the database based on payment_type.
     *
     * Returns null if the payable entity cannot be found or has no valid price.
     * This prevents clients from supplying a manipulated amount.
     *
     * @param  array       $validated  Validated request data
     * @param  int|null    $academyId  Current tenant academy ID
     * @return int|null    Price in cents, or null on failure
     */
    private function resolveCanonicalAmountCents(array $validated, ?int $academyId): ?int
    {
        $type = $validated['payment_type'];

        if ($type === 'course') {
            $courseId = $validated['course_id'] ?? null;
            if (! $courseId) {
                return null;
            }

            // Try InteractiveCourse first, then RecordedCourse, scoped to academy
            $course = InteractiveCourse::when($academyId, fn ($q) => $q->where('academy_id', $academyId))
                ->find($courseId);
            if ($course) {
                $priceDecimal = $course->student_price ?? 0;

                return (int) round($priceDecimal * 100);
            }

            $course = RecordedCourse::when($academyId, fn ($q) => $q->where('academy_id', $academyId))
                ->find($courseId);
            if ($course) {
                $priceDecimal = $course->price ?? 0;

                return (int) round($priceDecimal * 100);
            }

            return null;
        }

        if ($type === 'subscription') {
            $subscriptionId = $validated['subscription_id'] ?? null;
            if (! $subscriptionId) {
                return null;
            }

            // Try QuranSubscription
            $sub = QuranSubscription::when($academyId, fn ($q) => $q->where('academy_id', $academyId))
                ->find($subscriptionId);
            if ($sub) {
                $price = $sub->getPriceForBillingCycle() ?? $sub->final_price ?? 0;

                return (int) round($price * 100);
            }

            // Try AcademicSubscription
            $sub = AcademicSubscription::when($academyId, fn ($q) => $q->where('academy_id', $academyId))
                ->find($subscriptionId);
            if ($sub) {
                $price = $sub->getPriceForBillingCycle() ?? $sub->final_price ?? 0;

                return (int) round($price * 100);
            }

            return null;
        }

        // For 'session' and 'service' types there is no canonical price in DB yet —
        // reject these until server-side pricing is implemented.
        return null;
    }

    /**
     * Charge a saved payment method.
     */
    public function chargeSaved(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'saved_payment_method_id' => 'required|integer|exists:saved_payment_methods,id',
            'currency' => 'required|string|size:3',
            'payment_type' => 'required|string|in:course,subscription,session,service',
            'course_id' => 'nullable|integer|required_if:payment_type,course',
            'subscription_id' => 'nullable|integer|required_if:payment_type,subscription',
        ]);

        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized('يجب تسجيل الدخول أولاً');
        }

        // Get the saved payment method and verify ownership
        $savedMethod = SavedPaymentMethod::where('id', $validated['saved_payment_method_id'])
            ->where('user_id', $user->id)
            ->active()
            ->notExpired()
            ->first();

        if (! $savedMethod) {
            return $this->notFound('طريقة الدفع غير موجودة أو منتهية الصلاحية');
        }

        // SECURITY: Resolve canonical amount from DB — never trust client-supplied amount.
        $academyId = $user->academy_id;
        $canonicalAmountCents = $this->resolveCanonicalAmountCents($validated, $academyId);
        if ($canonicalAmountCents === null) {
            return $this->error('لم يتم العثور على السعر المطلوب', 404);
        }

        try {
            // Charge using the saved payment method
            $result = $this->paymentMethodService->chargePaymentMethod(
                $savedMethod,
                $canonicalAmountCents,
                $validated['currency'],
                [
                    'payment_type' => $validated['payment_type'],
                    'course_id' => $validated['course_id'] ?? null,
                    'subscription_id' => $validated['subscription_id'] ?? null,
                ]
            );

            if ($result->isSuccessful()) {
                return $this->success([
                    'success' => true,
                    'message' => 'تم الدفع بنجاح',
                    'payment_id' => $result->gatewayOrderId,
                    'transaction_id' => $result->transactionId,
                ]);
            }

            return $this->error($result->errorMessage ?? 'فشلت عملية الدفع', 400, [
                'error_code' => $result->errorCode,
            ]);

        } catch (Exception $e) {
            Log::error('Saved card charge failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'saved_method_id' => $validated['saved_payment_method_id'],
            ]);

            return $this->error(__('payments.service.unexpected_processing_error'), 500);
        }
    }

    /**
     * Get user's saved payment methods.
     */
    public function getSavedMethods(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized('يجب تسجيل الدخول أولاً');
        }

        $gateway = $request->get('gateway');

        $methods = $this->paymentMethodService->getUserPaymentMethods($user, $gateway);

        return $this->success([
            'payment_methods' => $methods->map(function ($method) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'brand' => $method->brand,
                    'brand_display' => $method->getBrandDisplayName(),
                    'last_four' => $method->last_four,
                    'masked_number' => $method->getMaskedNumber(),
                    'expiry' => $method->getExpiryDisplay(),
                    'is_default' => $method->is_default,
                    'is_expired' => $method->isExpired(),
                    'display_label' => $method->getDisplayLabel(),
                    'icon' => $method->getBrandIcon(),
                ];
            }),
        ]);
    }

    /**
     * Delete a saved payment method.
     */
    public function deleteSavedMethod(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized('يجب تسجيل الدخول أولاً');
        }

        $method = SavedPaymentMethod::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $method) {
            return $this->notFound('طريقة الدفع غير موجودة');
        }

        try {
            $deleted = $this->paymentMethodService->deletePaymentMethod($method);

            if ($deleted) {
                return $this->success([
                    'success' => true,
                    'message' => 'تم حذف طريقة الدفع بنجاح',
                ]);
            }

            return $this->error('فشل حذف طريقة الدفع', 400);

        } catch (Exception $e) {
            Log::error('Delete saved payment method failed', [
                'error' => $e->getMessage(),
                'method_id' => $id,
            ]);

            return $this->error('فشل حذف طريقة الدفع', 500);
        }
    }

    /**
     * Set a saved payment method as default.
     */
    public function setDefaultMethod(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized('يجب تسجيل الدخول أولاً');
        }

        $method = SavedPaymentMethod::where('id', $id)
            ->where('user_id', $user->id)
            ->active()
            ->first();

        if (! $method) {
            return $this->notFound('طريقة الدفع غير موجودة');
        }

        try {
            $this->paymentMethodService->setDefaultPaymentMethod($user, $method);

            return $this->success([
                'success' => true,
                'message' => 'تم تعيين طريقة الدفع كافتراضية',
            ]);

        } catch (Exception $e) {
            Log::error('Set default payment method failed', [
                'error' => $e->getMessage(),
                'method_id' => $id,
            ]);

            return $this->error('فشل تعيين طريقة الدفع الافتراضية', 500);
        }
    }
}
