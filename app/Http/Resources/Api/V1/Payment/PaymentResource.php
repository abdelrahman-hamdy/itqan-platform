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
            'id' => $this->id,
            'payment_code' => $this->payment_code,

            // Payable (polymorphic)
            'payable' => [
                'type' => $this->payable_type,
                'id' => $this->payable_id,
            ],

            // Amount
            'amount' => (float) $this->amount,
            'currency' => $this->currency,

            // Status
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],

            // Payment method
            'payment_method' => $this->payment_method,
            'payment_gateway' => $this->payment_gateway,

            // Transaction details
            'transaction_id' => $this->transaction_id,
            'gateway_response' => $this->when(
                $request->user()?->isAdmin() ?? false,
                $this->gateway_response
            ),

            // User
            'user' => $this->whenLoaded('user', [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),

            // Dates
            'paid_at' => $this->paid_at?->toISOString(),
            'refunded_at' => $this->refunded_at?->toISOString(),

            // Refund
            'refund_amount' => $this->refund_amount ? (float) $this->refund_amount : null,
            'refund_reason' => $this->when($this->refunded_at, $this->refund_reason),

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
