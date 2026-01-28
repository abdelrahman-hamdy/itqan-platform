<?php

namespace App\Services\Payment\DTOs;

use App\Models\Payment;

/**
 * Data Transfer Object for creating a payment intent.
 *
 * Contains all the information needed to initiate a payment
 * with any gateway.
 */
readonly class PaymentIntent
{
    public function __construct(
        public int $amountInCents,
        public string $currency,
        public string $paymentMethod,
        public int $academyId,
        public ?int $paymentId = null,
        public ?string $customerEmail = null,
        public ?string $customerPhone = null,
        public ?string $customerName = null,
        public ?string $description = null,
        public array $items = [],
        public array $billingData = [],
        public array $metadata = [],
        public ?string $successUrl = null,
        public ?string $cancelUrl = null,
        public ?string $webhookUrl = null,
    ) {}

    /**
     * Create from a Payment model.
     */
    public static function fromPayment(Payment $payment, array $additionalData = []): self
    {
        $billingData = [];

        // Extract billing data from payment or user
        if ($payment->payer) {
            $billingData = [
                'first_name' => $payment->payer->first_name ?? 'N/A',
                'last_name' => $payment->payer->last_name ?? 'N/A',
                'email' => $payment->payer->email ?? 'na@na.com',
                'phone_number' => $payment->payer->phone ?? 'NA',
                'country' => $payment->payer->country ?? 'SA',
                'city' => $payment->payer->city ?? 'NA',
                'street' => 'NA',
                'building' => 'NA',
                'floor' => 'NA',
                'apartment' => 'NA',
            ];
        }

        // Build items array from payment
        $items = [];
        if ($payment->payable) {
            $items[] = [
                'name' => $payment->description ?? 'اشتراك',
                'amount' => $payment->amount * 100, // Convert to cents
                'quantity' => 1,
            ];
        }

        return new self(
            amountInCents: (int) ($payment->amount * 100),
            currency: getCurrencyCode(null, $payment->academy), // Always use academy's configured currency
            paymentMethod: $additionalData['payment_method'] ?? 'card',
            academyId: $payment->academy_id,
            paymentId: $payment->id,
            customerEmail: $additionalData['customer_email'] ?? $payment->payer?->email ?? $payment->user?->email,
            customerPhone: $additionalData['customer_phone'] ?? $payment->payer?->phone ?? $payment->user?->phone,
            customerName: $additionalData['customer_name'] ?? $payment->payer?->name ?? $payment->user?->name,
            description: $payment->description,
            items: $items,
            billingData: $billingData,
            metadata: [
                'payment_id' => $payment->id,
                'academy_id' => $payment->academy_id,
                'payable_type' => $payment->payable_type,
                'payable_id' => $payment->payable_id,
            ],
            successUrl: $additionalData['success_url'] ?? null,
            cancelUrl: $additionalData['cancel_url'] ?? null,
            webhookUrl: $additionalData['webhook_url'] ?? null,
        );
    }

    /**
     * Get amount in major currency units (e.g., SAR, not halalas).
     */
    public function getAmountInMajorUnits(): float
    {
        return $this->amountInCents / 100;
    }

    /**
     * Get formatted amount for display.
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->getAmountInMajorUnits(), 2).' '.$this->currency;
    }

    /**
     * Convert to array for API requests.
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amountInCents,
            'currency' => $this->currency,
            'payment_method' => $this->paymentMethod,
            'academy_id' => $this->academyId,
            'payment_id' => $this->paymentId,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'customer_name' => $this->customerName,
            'description' => $this->description,
            'items' => $this->items,
            'billing_data' => $this->billingData,
            'metadata' => $this->metadata,
            'success_url' => $this->successUrl,
            'cancel_url' => $this->cancelUrl,
            'webhook_url' => $this->webhookUrl,
        ];
    }
}
