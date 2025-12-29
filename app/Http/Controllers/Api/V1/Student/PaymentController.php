<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\SessionStatus;

class PaymentController extends Controller
{
    use ApiResponses;

    /**
     * Get all payments for the student.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->get('status'); // pending, completed, failed, refunded

        $query = Payment::where('user_id', $user->id)
            ->with(['subscription']);

        if ($status) {
            $query->where('status', $status);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'payments' => collect($payments->items())->map(fn($payment) => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'formatted_amount' => $payment->formatted_amount ?? number_format($payment->amount, 2) . ' ' . $payment->currency,
                'status' => $payment->status,
                'status_label' => $this->getStatusLabel($payment->status),
                'payment_method' => $payment->payment_method,
                'description' => $payment->description,
                'subscription_type' => $payment->subscription_type,
                'paid_at' => $payment->paid_at?->toISOString(),
                'created_at' => $payment->created_at->toISOString(),
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($payments),
            'summary' => [
                'total_paid' => Payment::where('user_id', $user->id)
                    ->where('status', SessionStatus::COMPLETED->value)
                    ->sum('amount'),
                'total_pending' => Payment::where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->sum('amount'),
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

        $payment = Payment::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['subscription'])
            ->first();

        if (!$payment) {
            return $this->notFound(__('Payment not found.'));
        }

        return $this->success([
            'payment' => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'formatted_amount' => $payment->formatted_amount ?? number_format($payment->amount, 2) . ' ' . $payment->currency,
                'status' => $payment->status,
                'status_label' => $this->getStatusLabel($payment->status),
                'payment_method' => $payment->payment_method,
                'payment_method_details' => $payment->payment_method_details,
                'description' => $payment->description,
                'subscription_type' => $payment->subscription_type,
                'subscription' => $payment->subscription ? [
                    'id' => $payment->subscription->id,
                    'code' => $payment->subscription->subscription_code,
                    'title' => $payment->subscription->getSubscriptionTitle(),
                ] : null,
                'transaction_id' => $payment->transaction_id,
                'gateway' => $payment->gateway,
                'gateway_response' => $payment->gateway_response,
                'paid_at' => $payment->paid_at?->toISOString(),
                'created_at' => $payment->created_at->toISOString(),
                'receipt_url' => $payment->status === SessionStatus::COMPLETED
                    ? route('api.v1.student.payments.receipt', ['id' => $payment->id])
                    : null,
            ],
        ], __('Payment retrieved successfully'));
    }

    /**
     * Get payment receipt.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function receipt(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $payment = Payment::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', SessionStatus::COMPLETED->value)
            ->first();

        if (!$payment) {
            return $this->notFound(__('Receipt not found.'));
        }

        $academy = $request->attributes->get('academy') ?? current_academy();

        return $this->success([
            'receipt' => [
                'receipt_number' => 'RCP-' . $payment->payment_number,
                'payment_number' => $payment->payment_number,
                'date' => $payment->paid_at?->format('Y-m-d'),
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'formatted_amount' => number_format($payment->amount, 2) . ' ' . $payment->currency,
                'payment_method' => $payment->payment_method,
                'description' => $payment->description,
                'customer' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'issuer' => [
                    'name' => $academy->name,
                    'address' => $academy->address,
                    'phone' => $academy->phone,
                    'email' => $academy->email,
                    'logo' => $academy->logo_url ? asset('storage/' . $academy->logo_url) : null,
                ],
            ],
        ], __('Receipt retrieved successfully'));
    }

    /**
     * Get status label.
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => __('Pending'),
            'processing' => __('Processing'),
            'completed' => __('Completed'),
            'failed' => __('Failed'),
            'refunded' => __('Refunded'),
            'cancelled' => __('Cancelled'),
            default => $status,
        };
    }
}
