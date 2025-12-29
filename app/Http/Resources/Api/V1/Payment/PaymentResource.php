<?php

namespace App\Http\Resources\Api\V1\Payment;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payment Resource
 *
 * Payment transaction data for all payment types.
 *
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'payment_code' => $this->resource->payment_code,

            // Payable (polymorphic)
            'payable' => [
                'type' => $this->resource->payable_type,
                'id' => $this->resource->payable_id,
            ],

            // Amount
            'amount' => (float) $this->resource->amount,
            'currency' => $this->resource->currency,

            // Status
            'status' => [
                'value' => $this->resource->status->value,
                'label' => $this->resource->status->label(),
                'color' => $this->resource->status->color(),
            ],

            // Payment method
            'payment_method' => $this->resource->payment_method,
            'payment_gateway' => $this->resource->payment_gateway,

            // Transaction details
            'transaction_id' => $this->resource->transaction_id,
            'gateway_response' => $this->when(
                $request->user()?->isAdmin() ?? false,
                $this->resource->gateway_response
            ),

            // User
            'user' => $this->whenLoaded('user', [
                'id' => $this->resource->user?->id,
                'name' => $this->resource->user?->name,
                'email' => $this->resource->user?->email,
            ]),

            // Dates
            'paid_at' => $this->resource->paid_at?->toISOString(),
            'refunded_at' => $this->resource->refunded_at?->toISOString(),

            // Refund
            'refund_amount' => $this->resource->refund_amount ? (float) $this->resource->refund_amount : null,
            'refund_reason' => $this->when($this->resource->refunded_at, $this->resource->refund_reason),

            // Timestamps
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
    }
}
