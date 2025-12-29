<?php

namespace App\Http\Resources\Api\V1\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Payment Collection Resource
 *
 * Collection wrapper for payment resources with metadata and financial statistics
 */
class PaymentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'statuses' => $this->getStatusBreakdown(),
                'financial' => [
                    'total_amount' => $this->getTotalAmount(),
                    'paid_amount' => $this->getPaidAmount(),
                    'pending_amount' => $this->getPendingAmount(),
                    'refunded_amount' => $this->getRefundedAmount(),
                ],
                'payment_methods' => $this->getPaymentMethodBreakdown(),
            ],
        ];
    }

    /**
     * Get breakdown of payments by status
     *
     * @return array
     */
    protected function getStatusBreakdown(): array
    {
        return $this->collection->groupBy(fn($payment) => $payment->status->value)
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    /**
     * Get total amount across all payments
     *
     * @return float
     */
    protected function getTotalAmount(): float
    {
        return round($this->collection->sum(fn($payment) => (float) $payment->amount), 2);
    }

    /**
     * Get total paid amount
     *
     * @return float
     */
    protected function getPaidAmount(): float
    {
        return round($this->collection
            ->where('status.value', 'completed')
            ->sum(fn($payment) => (float) $payment->amount), 2);
    }

    /**
     * Get total pending amount
     *
     * @return float
     */
    protected function getPendingAmount(): float
    {
        return round($this->collection
            ->where('status.value', 'pending')
            ->sum(fn($payment) => (float) $payment->amount), 2);
    }

    /**
     * Get total refunded amount
     *
     * @return float
     */
    protected function getRefundedAmount(): float
    {
        return round($this->collection->sum(fn($payment) => (float) ($payment->refund_amount ?? 0)), 2);
    }

    /**
     * Get breakdown of payments by payment method
     *
     * @return array
     */
    protected function getPaymentMethodBreakdown(): array
    {
        return $this->collection->groupBy(fn($payment) => $payment->payment_method ?? 'N/A')
            ->map(fn($group) => $group->count())
            ->toArray();
    }
}
