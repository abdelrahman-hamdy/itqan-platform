<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponses;

    /**
     * Get all payments for the student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->get('status'); // pending, completed, failed, refunded

        $query = Payment::where('user_id', $user->id)
            ->with(['payable']);

        if ($status) {
            $query->where('status', $status);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->get('per_page', 15), 100));

        return $this->success([
            'payments' => collect($payments->items())->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'formatted_amount' => $payment->formatted_amount ?? number_format($payment->amount, 2).' '.$payment->currency,
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'payment_method' => $payment->payment_method,
                'description' => $payment->description,
                'subscription_type' => $payment->subscription_type,
                'paid_at' => $payment->paid_at?->toISOString(),
                'created_at' => $payment->created_at->toISOString(),
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($payments),
            // API-002: Single aggregate query instead of two separate queries
            'summary' => (function () use ($user) {
                $summary = Payment::where('user_id', $user->id)
                    ->selectRaw('
                        SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as total_paid,
                        SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as total_pending
                    ', [PaymentStatus::COMPLETED->value, PaymentStatus::PENDING->value])
                    ->first();

                return [
                    'total_paid' => (float) ($summary->total_paid ?? 0),
                    'total_pending' => (float) ($summary->total_pending ?? 0),
                ];
            })(),
        ], __('Payments retrieved successfully'));
    }

    /**
     * Get a specific payment.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $payment = Payment::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['payable'])
            ->first();

        if (! $payment) {
            return $this->notFound(__('Payment not found.'));
        }

        return $this->success([
            'payment' => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'formatted_amount' => $payment->formatted_amount ?? number_format($payment->amount, 2).' '.$payment->currency,
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'payment_method' => $payment->payment_method,
                'payment_method_details' => $payment->payment_method_details,
                'description' => $payment->description,
                'subscription_type' => $payment->subscription_type,
                'subscription' => $payment->payable ? [
                    'id' => $payment->payable->id,
                    'code' => $payment->payable->subscription_code,
                    'title' => $payment->payable->getSubscriptionTitle(),
                ] : null,
                'transaction_id' => $payment->transaction_id,
                'gateway' => $payment->gateway,
                'paid_at' => $payment->paid_at?->toISOString(),
                'created_at' => $payment->created_at->toISOString(),
                'receipt_url' => $payment->status === PaymentStatus::COMPLETED
                    ? route('api.v1.student.payments.receipt', ['id' => $payment->id])
                    : null,
            ],
        ], __('Payment retrieved successfully'));
    }

    /**
     * Get payment receipt.
     */
    public function receipt(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $payment = Payment::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', PaymentStatus::COMPLETED->value)
            ->first();

        if (! $payment) {
            return $this->notFound(__('Receipt not found.'));
        }

        $academy = $request->attributes->get('academy') ?? current_academy();

        return $this->success([
            'receipt' => [
                'receipt_number' => 'RCP-'.$payment->payment_number,
                'payment_number' => $payment->payment_number,
                'date' => $payment->paid_at?->format('Y-m-d'),
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'formatted_amount' => number_format($payment->amount, 2).' '.$payment->currency,
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
                    'logo' => $academy->logo_url,
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
