<?php

namespace App\Services\Payment\DTOs;

use App\Enums\PaymentResultStatus;
use App\Models\Payment;
use DateTime;
use DateTimeInterface;
use Log;

/**
 * Data Transfer Object for webhook payloads.
 *
 * Standardizes webhook data from different gateways into
 * a common format for processing.
 */
readonly class WebhookPayload
{
    public function __construct(
        public string $eventType,
        public string $transactionId,
        public PaymentResultStatus $status,
        public int $amountInCents,
        public string $currency,
        public string $gateway,
        public ?string $orderId = null,
        public ?string $paymentMethod = null,
        public ?int $paymentId = null,
        public ?int $academyId = null,
        public bool $isSuccess = false,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public ?string $cardBrand = null,
        public ?string $cardLastFour = null,
        public ?DateTimeInterface $processedAt = null,
        public array $rawPayload = [],
        public array $metadata = [],
        // Tokenization fields
        public ?string $cardToken = null,
        public ?string $cardExpiryMonth = null,
        public ?string $cardExpiryYear = null,
        public ?string $cardHolderName = null,
        public bool $isTokenized = false,
        public ?string $gatewayCustomerId = null,
    ) {}

    /**
     * Create from Paymob webhook data.
     */
    public static function fromPaymob(array $data): self
    {
        $obj = $data['obj'] ?? $data;
        $isSuccess = ($obj['success'] ?? false) === true;

        // Determine status from Paymob response
        $status = match (true) {
            $isSuccess => PaymentResultStatus::SUCCESS,
            ($obj['pending'] ?? false) => PaymentResultStatus::PENDING,
            ($obj['is_voided'] ?? false) => PaymentResultStatus::CANCELLED,
            default => PaymentResultStatus::FAILED,
        };

        // Extract payment ID from merchant_order_id or metadata
        $merchantOrderId = $obj['merchant_order_id'] ?? $obj['order']['merchant_order_id'] ?? null;

        // Handle refund flag gracefully (refunds not supported but gateway may send flag)
        $refundDetected = $obj['is_refunded'] ?? false;
        if ($refundDetected) {
            Log::channel('payments')->warning('Paymob refund detected but platform does not support refunds', [
                'transaction_id' => $obj['id'] ?? null,
                'merchant_order_id' => $merchantOrderId,
            ]);
        }
        $paymentId = null;
        $academyId = null;

        if ($merchantOrderId && str_contains($merchantOrderId, '-')) {
            // Format: ACADEMY_ID-PAYMENT_ID-TIMESTAMP
            $parts = explode('-', $merchantOrderId);
            if (count($parts) >= 2) {
                $academyId = (int) $parts[0];
                $paymentId = (int) $parts[1];
            }
        }

        // Extract card info if available
        $sourceData = $obj['source_data'] ?? [];
        $cardBrand = $sourceData['sub_type'] ?? null;
        $cardLastFour = $sourceData['pan'] ?? null;

        // Extract tokenization data if present
        $cardToken = $obj['token'] ?? $sourceData['token'] ?? null;
        $isTokenized = ! empty($cardToken);

        // Extract card expiry if tokenized
        $cardExpiryMonth = null;
        $cardExpiryYear = null;
        $cardHolderName = null;

        if ($isTokenized && isset($sourceData['card_details'])) {
            $cardDetails = $sourceData['card_details'];
            $cardExpiryMonth = $cardDetails['expiry_month'] ?? null;
            $cardExpiryYear = $cardDetails['expiry_year'] ?? null;
            $cardHolderName = $cardDetails['holder_name'] ?? null;
        }

        // Try to get from payment_key_claims if not in source_data
        $paymentKeyClaims = $obj['payment_key_claims'] ?? [];
        if (! $cardExpiryMonth && isset($paymentKeyClaims['billing_data'])) {
            $billingData = $paymentKeyClaims['billing_data'];
            $cardHolderName = $cardHolderName ?? ($billingData['first_name'] ?? '').' '.($billingData['last_name'] ?? '');
        }

        // Extract gateway customer ID if available
        $gatewayCustomerId = $obj['profile_id'] ?? $paymentKeyClaims['profile_id'] ?? null;

        // Build metadata with refund flag if detected
        $metadata = $paymentKeyClaims['extra'] ?? [];
        if ($refundDetected) {
            $metadata['gateway_refund_flag'] = true;
            $metadata['gateway_refund_detected_at'] = now()->toIso8601String();
        }

        return new self(
            eventType: $data['type'] ?? 'TRANSACTION',
            transactionId: (string) ($obj['id'] ?? ''),
            status: $status,
            amountInCents: (int) (($obj['amount_cents'] ?? 0)),
            currency: $obj['currency'] ?? 'EGP',
            gateway: 'paymob',
            orderId: (string) ($obj['order']['id'] ?? $obj['order_id'] ?? ''),
            paymentMethod: $sourceData['type'] ?? 'card',
            paymentId: $paymentId,
            academyId: $academyId,
            isSuccess: $isSuccess,
            errorCode: $isSuccess ? null : ($obj['data']['txn_response_code'] ?? 'UNKNOWN'),
            errorMessage: $isSuccess ? null : ($obj['data']['message'] ?? 'Payment failed'),
            cardBrand: $cardBrand,
            cardLastFour: $cardLastFour,
            processedAt: isset($obj['created_at']) ? new DateTime($obj['created_at']) : now(),
            rawPayload: $data,
            metadata: $metadata,
            cardToken: $cardToken,
            cardExpiryMonth: $cardExpiryMonth,
            cardExpiryYear: $cardExpiryYear,
            cardHolderName: trim($cardHolderName ?? ''),
            isTokenized: $isTokenized,
            gatewayCustomerId: $gatewayCustomerId ? (string) $gatewayCustomerId : null,
        );
    }

    /**
     * Create from EasyKash webhook data.
     */
    public static function fromEasyKash(array $data): self
    {
        $statusString = strtoupper($data['status'] ?? 'UNKNOWN');

        // Handle refund status gracefully (refunds not supported but gateway may send)
        $refundDetected = $statusString === 'REFUNDED';
        if ($refundDetected) {
            Log::channel('payments')->warning('EasyKash refund detected but platform does not support refunds', [
                'easykash_ref' => $data['easykashRef'] ?? null,
                'customer_reference' => $data['customerReference'] ?? null,
            ]);
        }

        // Determine status from EasyKash response
        $status = match ($statusString) {
            'PAID', 'DELIVERED' => PaymentResultStatus::SUCCESS,
            'NEW', 'PENDING' => PaymentResultStatus::PENDING,
            'EXPIRED' => PaymentResultStatus::EXPIRED,
            'REFUNDED' => PaymentResultStatus::SUCCESS,  // Map to success, flag in metadata
            'FAILED', 'CANCELED' => PaymentResultStatus::FAILED,
            default => PaymentResultStatus::PENDING,
        };

        $isSuccess = in_array($statusString, ['PAID', 'DELIVERED', 'REFUNDED']);

        // Extract payment ID from customerReference
        // New format: just the payment_id (integer)
        // Legacy format: {academy_id}-{payment_id}-{timestamp}
        $customerReference = $data['customerReference'] ?? '';
        $paymentId = null;
        $academyId = null;

        if (! empty($customerReference)) {
            if (str_contains($customerReference, '-')) {
                // Legacy format: {academy_id}-{payment_id}-{timestamp}
                $parts = explode('-', $customerReference);
                if (count($parts) >= 2) {
                    $academyId = is_numeric($parts[0]) ? (int) $parts[0] : null;
                    $paymentId = is_numeric($parts[1]) ? (int) $parts[1] : null;
                }
            } elseif (is_numeric($customerReference)) {
                // New format: just the payment_id
                $paymentId = (int) $customerReference;
            }
        }

        // Parse amount (EasyKash sends in major units as string)
        $amount = $data['Amount'] ?? '0';
        $amountInCents = (int) (floatval($amount) * 100);

        // Map EasyKash payment method to internal format
        $paymentMethod = match (true) {
            str_contains($data['PaymentMethod'] ?? '', 'Fawry') => 'fawry',
            str_contains($data['PaymentMethod'] ?? '', 'Aman') => 'aman',
            str_contains($data['PaymentMethod'] ?? '', 'Card') => 'card',
            str_contains($data['PaymentMethod'] ?? '', 'Wallet') => 'wallet',
            str_contains($data['PaymentMethod'] ?? '', 'Meeza') => 'meeza',
            default => 'card',
        };

        // Determine currency from payment record or academy (don't hardcode)
        $currency = 'EGP'; // Default for EasyKash (Egypt-based)
        if ($paymentId) {
            $payment = Payment::find($paymentId);
            if ($payment) {
                $currency = $payment->currency ?? $payment->academy?->currency?->value ?? 'EGP';
                $academyId = $academyId ?? $payment->academy_id;
            }
        }

        // Build metadata with refund flag if detected
        $metadata = [
            'voucher' => $data['voucher'] ?? null,
            'voucher_data' => $data['VoucherData'] ?? null,
            'buyer_name' => $data['BuyerName'] ?? null,
            'buyer_email' => $data['BuyerEmail'] ?? null,
            'buyer_mobile' => $data['BuyerMobile'] ?? null,
            'product_type' => $data['ProductType'] ?? null,
        ];

        if ($refundDetected) {
            $metadata['gateway_refund_flag'] = true;
            $metadata['gateway_refund_detected_at'] = now()->toIso8601String();
        }

        return new self(
            eventType: 'TRANSACTION',  // Refunds treated as transactions with metadata flag
            transactionId: (string) ($data['easykashRef'] ?? ''),
            status: $status,
            amountInCents: $amountInCents,
            currency: $currency,
            gateway: 'easykash',
            orderId: $data['ProductCode'] ?? null,
            paymentMethod: $paymentMethod,
            paymentId: $paymentId,
            academyId: $academyId,
            isSuccess: $isSuccess,
            errorCode: $isSuccess ? null : $statusString,
            errorMessage: $isSuccess ? null : "Payment status: {$statusString}",
            cardBrand: null, // EasyKash doesn't provide card details in webhook
            cardLastFour: null,
            processedAt: isset($data['Timestamp'])
                ? DateTime::createFromFormat('U', $data['Timestamp'])
                : now(),
            rawPayload: $data,
            metadata: $metadata,
        );
    }

    /**
     * Create from Tap webhook data (Charge object).
     */
    public static function fromTap(array $data): self
    {
        $tapStatus = strtoupper($data['status'] ?? 'UNKNOWN');

        // Handle refund gracefully
        $refundDetected = $tapStatus === 'REFUNDED';
        if ($refundDetected) {
            Log::channel('payments')->warning('Tap refund detected but platform does not support refunds', [
                'charge_id' => $data['id'] ?? null,
            ]);
        }

        // Map Tap status to internal status
        $status = match ($tapStatus) {
            'CAPTURED' => PaymentResultStatus::SUCCESS,
            'INITIATED', 'IN_PROGRESS' => PaymentResultStatus::PENDING,
            'REFUNDED' => PaymentResultStatus::SUCCESS,  // Flag in metadata
            'FAILED', 'DECLINED', 'RESTRICTED', 'CANCELLED', 'VOID' => PaymentResultStatus::FAILED,
            default => PaymentResultStatus::PENDING,
        };

        $isSuccess = in_array($tapStatus, ['CAPTURED', 'REFUNDED']);

        // Extract payment_id and academy_id from metadata or reference
        $paymentId = null;
        $academyId = null;

        $metadata = $data['metadata'] ?? [];
        if (! empty($metadata['payment_id'])) {
            $paymentId = (int) $metadata['payment_id'];
        }
        if (! empty($metadata['academy_id'])) {
            $academyId = (int) $metadata['academy_id'];
        }

        // Fallback: parse from reference.transaction (format: "PAYMENT-{id}")
        if (! $paymentId) {
            $refTransaction = $data['reference']['transaction'] ?? '';
            if (str_starts_with($refTransaction, 'PAYMENT-')) {
                $paymentId = (int) substr($refTransaction, strlen('PAYMENT-'));
            }
        }

        // Fetch currency from payment record if needed
        $currency = $data['currency'] ?? 'SAR';
        if ($paymentId && ! $academyId) {
            $payment = Payment::withoutGlobalScopes()->find($paymentId);
            if ($payment) {
                $currency = $payment->currency ?? $payment->academy?->currency?->value ?? $currency;
                $academyId = $academyId ?? $payment->academy_id;
            }
        }

        // Amount: Tap sends in major units (e.g. 100.00 for 100 SAR)
        $amountInCents = (int) round(((float) ($data['amount'] ?? 0)) * 100);

        // Card details from source
        $source = $data['source'] ?? [];
        $cardBrand = $source['card']['scheme'] ?? $source['payment_method'] ?? null;
        $cardLastFour = $source['card']['last_four'] ?? null;

        $webhookMetadata = [
            'charge_status' => $tapStatus,
            'reference_transaction' => $data['reference']['transaction'] ?? null,
            'reference_order' => $data['reference']['order'] ?? null,
            'payment_method' => $source['payment_method'] ?? null,
        ];

        if ($refundDetected) {
            $webhookMetadata['gateway_refund_flag'] = true;
            $webhookMetadata['gateway_refund_detected_at'] = now()->toIso8601String();
        }

        return new self(
            eventType: 'TRANSACTION',
            transactionId: (string) ($data['id'] ?? ''),
            status: $status,
            amountInCents: $amountInCents,
            currency: $currency,
            gateway: 'tap',
            orderId: $data['reference']['order'] ?? null,
            paymentMethod: $source['payment_method'] ?? 'card',
            paymentId: $paymentId,
            academyId: $academyId,
            isSuccess: $isSuccess,
            errorCode: $isSuccess ? null : $tapStatus,
            errorMessage: $isSuccess ? null : "Tap charge status: {$tapStatus}",
            cardBrand: $cardBrand,
            cardLastFour: $cardLastFour,
            processedAt: now(),
            rawPayload: $data,
            metadata: $webhookMetadata,
        );
    }

    /**
     * Get amount in major currency units.
     */
    public function getAmountInMajorUnits(): float
    {
        return $this->amountInCents / 100;
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->getAmountInMajorUnits(), 2).' '.$this->currency;
    }

    /**
     * Check if this is a successful payment.
     */
    public function isSuccessful(): bool
    {
        return $this->isSuccess && $this->status === PaymentResultStatus::SUCCESS;
    }

    /**
     * Check if this is a refund event.
     */
    public function isRefund(): bool
    {
        return in_array($this->eventType, ['REFUND', 'REFUNDED']);
    }

    /**
     * Check if this is a void/cancel event.
     */
    public function isVoid(): bool
    {
        return in_array($this->eventType, ['VOID', 'VOIDED']);
    }

    /**
     * Get unique identifier for idempotency.
     */
    public function getIdempotencyKey(): string
    {
        return sprintf('%s-%s-%s', $this->gateway, $this->transactionId, $this->eventType);
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'event_type' => $this->eventType,
            'transaction_id' => $this->transactionId,
            'status' => $this->status->value,
            'amount_cents' => $this->amountInCents,
            'currency' => $this->currency,
            'gateway' => $this->gateway,
            'order_id' => $this->orderId,
            'payment_method' => $this->paymentMethod,
            'payment_id' => $this->paymentId,
            'academy_id' => $this->academyId,
            'is_success' => $this->isSuccess,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'card_brand' => $this->cardBrand,
            'card_last_four' => $this->cardLastFour,
            'processed_at' => $this->processedAt?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
            'card_token' => $this->cardToken ? '***REDACTED***' : null,
            'is_tokenized' => $this->isTokenized,
            'card_expiry_month' => $this->cardExpiryMonth,
            'card_expiry_year' => $this->cardExpiryYear,
        ];
    }

    /**
     * Check if this webhook contains tokenization data that should be saved.
     */
    public function hasTokenizationData(): bool
    {
        return $this->isTokenized && ! empty($this->cardToken);
    }
}
