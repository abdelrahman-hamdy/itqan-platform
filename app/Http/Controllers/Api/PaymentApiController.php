<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Payment;
use App\Models\SavedPaymentMethod;
use App\Services\Payment\PaymentMethodService;
use App\Services\PaymentService;
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
            'amount' => 'required|numeric|min:1',
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

            // Create payment record first
            $payment = Payment::createPayment([
                'academy_id' => $academyId,
                'user_id' => $user->id,
                'amount' => $validated['amount'] / 100, // Convert from cents
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
                'amount' => $validated['amount'],
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
                    'gateway_payment_id' => $result->getPaymentId(),
                ]);

                return $this->success([
                    'success' => true,
                    'iframe_url' => $result->getRedirectUrl(),
                    'payment_id' => $payment->id,
                ]);
            }

            // Mark payment as failed
            $payment->markAsFailed($result->getErrorMessage());

            return $this->error($result->getErrorMessage() ?? 'فشل إنشاء عملية الدفع', 400);

        } catch (Exception $e) {
            Log::error('Payment intent creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'data' => $validated,
            ]);

            return $this->error('فشل إنشاء عملية الدفع: '.$e->getMessage(), 500);
        }
    }

    /**
     * Charge a saved payment method.
     */
    public function chargeSaved(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'saved_payment_method_id' => 'required|integer|exists:saved_payment_methods,id',
            'amount' => 'required|numeric|min:1',
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

        try {
            // Charge using the saved payment method
            $result = $this->paymentMethodService->chargePaymentMethod(
                $savedMethod,
                $validated['amount'],
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
                    'payment_id' => $result->getPaymentId(),
                    'transaction_id' => $result->getTransactionId(),
                ]);
            }

            return $this->error($result->getErrorMessage() ?? 'فشلت عملية الدفع', 400, [
                'error_code' => $result->getErrorCode(),
            ]);

        } catch (Exception $e) {
            Log::error('Saved card charge failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'saved_method_id' => $validated['saved_payment_method_id'],
            ]);

            return $this->error('فشلت عملية الدفع: '.$e->getMessage(), 500);
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
