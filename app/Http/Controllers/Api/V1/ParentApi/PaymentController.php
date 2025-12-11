<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\ParentStudentRelationship;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    use ApiResponses;

    /**
     * Get all payments for linked children.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile;

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Get all linked children's user IDs
        $childUserIds = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user')
            ->get()
            ->pluck('student.user.id')
            ->filter()
            ->toArray();

        // Get payments for all children
        $query = Payment::whereIn('user_id', $childUserIds)
            ->with(['user', 'payable']);

        // Filter by child
        if ($request->filled('child_id')) {
            $childUserId = ParentStudentRelationship::where('parent_id', $parentProfile->id)
                ->where('student_id', $request->child_id)
                ->with('student.user')
                ->first()?->student?->user?->id;

            if ($childUserId) {
                $query->where('user_id', $childUserId);
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'payments' => collect($payments->items())->map(fn($payment) => [
                'id' => $payment->id,
                'child_name' => $payment->user?->name,
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'SAR',
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
                'description' => $payment->description,
                'payable_type' => class_basename($payment->payable_type ?? ''),
                'payable_id' => $payment->payable_id,
                'paid_at' => $payment->paid_at?->toISOString(),
                'created_at' => $payment->created_at->toISOString(),
            ])->toArray(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'total_pages' => $payments->lastPage(),
                'has_more' => $payments->hasMorePages(),
            ],
        ], __('Payments retrieved successfully'));
    }

    /**
     * Get a specific payment.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile;

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Get all linked children's user IDs
        $childUserIds = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user')
            ->get()
            ->pluck('student.user.id')
            ->filter()
            ->toArray();

        $payment = Payment::where('id', $id)
            ->whereIn('user_id', $childUserIds)
            ->with(['user', 'payable'])
            ->first();

        if (!$payment) {
            return $this->notFound(__('Payment not found.'));
        }

        return $this->success([
            'payment' => [
                'id' => $payment->id,
                'child_name' => $payment->user?->name,
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'SAR',
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'gateway' => $payment->gateway,
                'transaction_id' => $payment->transaction_id,
                'gateway_reference' => $payment->gateway_reference,
                'description' => $payment->description,
                'payable_type' => class_basename($payment->payable_type ?? ''),
                'payable_id' => $payment->payable_id,
                'payable_details' => $this->getPayableDetails($payment),
                'metadata' => $payment->metadata,
                'paid_at' => $payment->paid_at?->toISOString(),
                'created_at' => $payment->created_at->toISOString(),
            ],
        ], __('Payment retrieved successfully'));
    }

    /**
     * Initiate a payment for a subscription.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initiate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'child_id' => ['required', 'integer'],
            'subscription_type' => ['required', 'in:quran,academic,course'],
            'subscription_id' => ['required', 'integer'],
            'payment_method' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $parentProfile = $user->parentProfile;

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Verify child is linked
        $relationship = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->where('student_id', $request->child_id)
            ->with('student.user')
            ->first();

        if (!$relationship) {
            return $this->error(__('Child not found.'), 404, 'CHILD_NOT_FOUND');
        }

        // Get subscription
        $subscription = $this->getSubscription(
            $request->subscription_type,
            $request->subscription_id,
            $relationship->student->user?->id ?? $relationship->student->id
        );

        if (!$subscription) {
            return $this->notFound(__('Subscription not found.'));
        }

        // Check if payment already exists
        if ($subscription->payment_status === 'paid') {
            return $this->error(__('This subscription is already paid.'), 400, 'ALREADY_PAID');
        }

        // Initiate payment via payment gateway
        // This is a placeholder - actual implementation depends on payment gateway
        $paymentData = [
            'subscription_type' => $request->subscription_type,
            'subscription_id' => $subscription->id,
            'amount' => $subscription->price ?? $subscription->total_price ?? 0,
            'currency' => 'SAR',
            'payment_method' => $request->payment_method,
            'redirect_url' => null, // Will be filled by payment gateway
            'status' => 'pending',
        ];

        return $this->success([
            'payment' => $paymentData,
            'message' => __('Payment initiation would redirect to payment gateway.'),
        ], __('Payment initiated'));
    }

    /**
     * Get payable details.
     */
    protected function getPayableDetails($payment): ?array
    {
        if (!$payment->payable) {
            return null;
        }

        $payable = $payment->payable;

        return [
            'id' => $payable->id,
            'name' => $payable->name ?? $payable->title ?? null,
        ];
    }

    /**
     * Get subscription by type.
     */
    protected function getSubscription(string $type, int $id, int $userId)
    {
        return match ($type) {
            'quran' => \App\Models\QuranSubscription::where('id', $id)
                ->where('student_id', $userId)
                ->first(),
            'academic' => \App\Models\AcademicSubscription::where('id', $id)
                ->where('student_id', $userId)
                ->first(),
            'course' => \App\Models\CourseSubscription::where('id', $id)
                ->where('user_id', $userId)
                ->first(),
            default => null,
        };
    }
}
