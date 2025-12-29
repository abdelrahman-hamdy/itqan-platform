<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a payment is successfully completed.
 *
 * Use cases:
 * - Activate related subscription
 * - Send payment confirmation notification
 * - Generate receipt/invoice
 * - Update analytics/reporting
 */
class PaymentCompletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly ?array $gatewayResponse = null
    ) {}

    /**
     * Get the payment model.
     */
    public function getPayment(): Payment
    {
        return $this->payment;
    }

    /**
     * Get the payment amount.
     */
    public function getAmount(): float
    {
        return (float) $this->payment->amount;
    }

    /**
     * Get the payment gateway response data.
     */
    public function getGatewayResponse(): ?array
    {
        return $this->gatewayResponse;
    }
}
