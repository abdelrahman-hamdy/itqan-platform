<?php

namespace App\Services\Payment\DTOs;

use App\Models\Payment;
use Carbon\Carbon;

/**
 * Data Transfer Object representing a generated invoice.
 *
 * Contains all structured invoice data for a completed payment,
 * including invoice number, line items, tax breakdown, and party details.
 */
readonly class InvoiceData
{
    public function __construct(
        public string $invoiceNumber,
        public int $paymentId,
        public int $academyId,
        public string $academyName,
        public int $userId,
        public string $customerName,
        public ?string $customerEmail,
        public ?string $customerPhone,
        public float $amount,
        public float $taxAmount,
        public float $taxPercentage,
        public float $discountAmount,
        public float $fees,
        public float $netAmount,
        public string $currency,
        public string $paymentMethod,
        public string $paymentStatus,
        public ?string $subscriptionType,
        public ?string $subscriptionName,
        public ?string $subscriptionPeriod,
        public ?string $description,
        public Carbon $issuedAt,
        public ?Carbon $paidAt,
        public array $lineItems = [],
        public array $metadata = [],
    ) {}

    /**
     * Create InvoiceData from a Payment model.
     */
    public static function fromPayment(Payment $payment, string $invoiceNumber): self
    {
        $payment->loadMissing(['academy', 'user', 'payable']);

        $subscriptionType = null;
        $subscriptionName = null;
        $subscriptionPeriod = null;

        if ($payment->payable) {
            $payable = $payment->payable;

            if ($payable instanceof \App\Models\QuranSubscription) {
                $subscriptionType = 'quran';
                $subscriptionName = $payable->package_name_ar
                    ?? $payable->package?->name
                    ?? __('payments.quran_subscription');
                $subscriptionPeriod = $payable->start_date && $payable->end_date
                    ? $payable->start_date->format('Y-m-d').' - '.$payable->end_date->format('Y-m-d')
                    : null;
            } elseif ($payable instanceof \App\Models\AcademicSubscription) {
                $subscriptionType = 'academic';
                $subscriptionName = $payable->package_name_ar
                    ?? $payable->package?->name
                    ?? $payable->subject_name
                    ?? __('payments.academic_subscription');
                $subscriptionPeriod = $payable->start_date && $payable->end_date
                    ? $payable->start_date->format('Y-m-d').' - '.$payable->end_date->format('Y-m-d')
                    : null;
            } elseif ($payable instanceof \App\Models\CourseSubscription) {
                $subscriptionType = 'course';
                $subscriptionName = $payable->course?->title
                    ?? __('payments.course_subscription');
            }
        }

        // Build line items
        $lineItems = [];
        if ($subscriptionName) {
            $baseAmount = $payment->amount - ($payment->tax_amount ?? 0) + ($payment->discount_amount ?? 0);
            $lineItems[] = [
                'description' => $subscriptionName,
                'quantity' => 1,
                'unit_price' => $baseAmount,
                'total' => $baseAmount,
            ];
        }

        return new self(
            invoiceNumber: $invoiceNumber,
            paymentId: $payment->id,
            academyId: $payment->academy_id,
            academyName: $payment->academy?->name ?? __('payments.invoice.unknown_academy'),
            userId: $payment->user_id,
            customerName: $payment->user?->name ?? __('payments.invoice.unknown_customer'),
            customerEmail: $payment->user?->email,
            customerPhone: $payment->user?->phone,
            amount: (float) $payment->amount,
            taxAmount: (float) ($payment->tax_amount ?? 0),
            taxPercentage: (float) ($payment->tax_percentage ?? 15),
            discountAmount: (float) ($payment->discount_amount ?? 0),
            fees: (float) ($payment->fees ?? 0),
            netAmount: (float) ($payment->net_amount ?? $payment->amount),
            currency: $payment->currency ?? getCurrencyCode(null, $payment->academy),
            paymentMethod: $payment->payment_method_text ?? $payment->payment_method,
            paymentStatus: $payment->status instanceof \App\Enums\PaymentStatus
                ? $payment->status->label()
                : (string) $payment->status,
            subscriptionType: $subscriptionType,
            subscriptionName: $subscriptionName,
            subscriptionPeriod: $subscriptionPeriod,
            description: $payment->description ?? $subscriptionName,
            issuedAt: $payment->paid_at ?? $payment->confirmed_at ?? now(),
            paidAt: $payment->paid_at,
            lineItems: $lineItems,
            metadata: [
                'payment_code' => $payment->payment_code,
                'receipt_number' => $payment->receipt_number,
                'gateway' => $payment->payment_gateway,
                'transaction_id' => $payment->gateway_transaction_id ?? $payment->transaction_id,
            ],
        );
    }

    /**
     * Get formatted amount with currency.
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2).' '.$this->currency;
    }

    /**
     * Get formatted tax amount with currency.
     */
    public function getFormattedTaxAmount(): string
    {
        return number_format($this->taxAmount, 2).' '.$this->currency;
    }

    /**
     * Get formatted net amount with currency.
     */
    public function getFormattedNetAmount(): string
    {
        return number_format($this->netAmount, 2).' '.$this->currency;
    }

    /**
     * Get formatted discount amount with currency.
     */
    public function getFormattedDiscountAmount(): string
    {
        return number_format($this->discountAmount, 2).' '.$this->currency;
    }

    /**
     * Get the subtotal (amount before tax and discount).
     */
    public function getSubtotal(): float
    {
        return $this->amount - $this->taxAmount + $this->discountAmount;
    }

    /**
     * Get formatted subtotal with currency.
     */
    public function getFormattedSubtotal(): string
    {
        return number_format($this->getSubtotal(), 2).' '.$this->currency;
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'invoice_number' => $this->invoiceNumber,
            'payment_id' => $this->paymentId,
            'academy_id' => $this->academyId,
            'academy_name' => $this->academyName,
            'user_id' => $this->userId,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'amount' => $this->amount,
            'tax_amount' => $this->taxAmount,
            'tax_percentage' => $this->taxPercentage,
            'discount_amount' => $this->discountAmount,
            'fees' => $this->fees,
            'net_amount' => $this->netAmount,
            'currency' => $this->currency,
            'payment_method' => $this->paymentMethod,
            'payment_status' => $this->paymentStatus,
            'subscription_type' => $this->subscriptionType,
            'subscription_name' => $this->subscriptionName,
            'subscription_period' => $this->subscriptionPeriod,
            'description' => $this->description,
            'issued_at' => $this->issuedAt->toIso8601String(),
            'paid_at' => $this->paidAt?->toIso8601String(),
            'line_items' => $this->lineItems,
            'metadata' => $this->metadata,
        ];
    }
}
