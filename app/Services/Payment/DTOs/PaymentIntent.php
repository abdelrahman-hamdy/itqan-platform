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
            $payerEmail = $payment->payer->email ?? $payment->user?->email;
            if (! $payerEmail) {
                throw new \InvalidArgumentException('Payment requires a valid customer email address');
            }

            $billingData = [
                'first_name' => $payment->payer->first_name ?? $payment->payer->name ?? 'Customer',
                'last_name' => $payment->payer->last_name ?? '',
                'email' => $payerEmail,
                'phone_number' => $payment->payer->phone ?? $payment->user?->phone ?? '',
                'country' => $payment->payer->country ?? 'SA',
                'city' => $payment->payer->city ?? '',
                'street' => '',
                'building' => '',
                'floor' => '',
                'apartment' => '',
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

    /**
     * Convert to array with sensitive data redacted for safe logging.
     * Use this method instead of toArray() when logging to prevent PII exposure.
     */
    public function toSafeLogArray(): array
    {
        return [
            'amount' => $this->amountInCents,
            'currency' => $this->currency,
            'payment_method' => $this->paymentMethod,
            'academy_id' => $this->academyId,
            'payment_id' => $this->paymentId,
            'customer_email' => $this->redactEmail($this->customerEmail),
            'customer_phone' => $this->redactPhone($this->customerPhone),
            'customer_name' => $this->customerName ? '[REDACTED]' : null,
            'description' => $this->description,
            'items_count' => count($this->items),
            'has_billing_data' => ! empty($this->billingData),
            'metadata' => $this->metadata, // Safe - only contains IDs
            'has_success_url' => ! empty($this->successUrl),
            'has_cancel_url' => ! empty($this->cancelUrl),
            'has_webhook_url' => ! empty($this->webhookUrl),
        ];
    }

    /**
     * Redact email for logging (show first 2 chars and domain).
     */
    private function redactEmail(?string $email): ?string
    {
        if (empty($email)) {
            return null;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '[INVALID_EMAIL]';
        }

        $local = $parts[0];
        $domain = $parts[1];
        $redactedLocal = substr($local, 0, 2).str_repeat('*', max(0, strlen($local) - 2));

        return $redactedLocal.'@'.$domain;
    }

    /**
     * Redact phone number for logging (show last 4 digits).
     */
    private function redactPhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($digits) < 4) {
            return '[REDACTED]';
        }

        return str_repeat('*', strlen($digits) - 4).substr($digits, -4);
    }
}
