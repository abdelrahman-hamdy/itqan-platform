<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'user_id',
        'subscription_id',
        'invoice_id',
        'payment_code',
        'payment_method',
        'payment_gateway',
        'gateway_transaction_id',
        'gateway_payment_id',
        'payment_type',
        'amount',
        'currency',
        'exchange_rate',
        'amount_in_base_currency',
        'fees',
        'net_amount',
        'tax_amount',
        'tax_percentage',
        'discount_amount',
        'discount_code',
        'status',
        'payment_status',
        'gateway_status',
        'failure_reason',
        'gateway_response',
        'payment_date',
        'processed_at',
        'confirmed_at',
        'receipt_url',
        'receipt_number',
        'notes',
        'metadata',
        'created_by',
        'updated_by',
        // Payment gateway integration fields
        'transaction_id',
        'gateway_intent_id',
        'gateway_order_id',
        'client_secret',
        'redirect_url',
        'iframe_url',
        'paid_at',
        // Polymorphic payable relationship
        'payable_type',
        'payable_id',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_in_base_currency' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'processed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'paid_at' => 'datetime',
        'gateway_response' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payable entity (subscription, enrollment, etc.)
     * This is the primary relationship for linking payments to their source.
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Alias for payable() - for backwards compatibility.
     * Use payable() for new code.
     *
     * @deprecated Use payable() instead
     */
    public function subscription(): MorphTo
    {
        return $this->payable();
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', PaymentStatus::COMPLETED->value)
            ->where('payment_status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING->value);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::FAILED->value);
    }

    public function scopeForMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeForGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    /**
     * Scope to filter by academy (tenant isolation)
     */
    public function scopeForAcademy($query, int $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('payment_date', today());
    }

    // Accessors
    public function getPaymentMethodTextAttribute(): string
    {
        $methods = [
            'credit_card' => 'بطاقة ائتمان',
            'debit_card' => 'بطاقة خصم',
            'bank_transfer' => 'تحويل بنكي',
            'wallet' => 'محفظة إلكترونية',
            'cash' => 'نقداً',
            'mada' => 'مدى',
            'visa' => 'فيزا',
            'mastercard' => 'ماستركارد',
            'apple_pay' => 'Apple Pay',
            'stc_pay' => 'STC Pay',
            'urpay' => 'UrPay',
        ];

        return $methods[$this->payment_method] ?? $this->payment_method;
    }

    public function getStatusTextAttribute(): string
    {
        // If status is a PaymentStatus enum, use its label method
        if ($this->status instanceof PaymentStatus) {
            return $this->status->label();
        }

        $statuses = [
            'pending' => 'في الانتظار',
            'processing' => 'قيد المعالجة',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            'cancelled' => 'ملغي',
        ];

        return $statuses[$this->status] ?? (string) $this->status;
    }

    public function getStatusBadgeColorAttribute(): string
    {
        // If status is a PaymentStatus enum, use its color method
        if ($this->status instanceof PaymentStatus) {
            return $this->status->color();
        }

        $colors = [
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
        ];

        $statusKey = is_object($this->status) ? $this->status->value : $this->status;

        return $colors[$statusKey] ?? 'secondary';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2).' '.$this->currency;
    }

    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount, 2).' '.$this->currency;
    }

    public function getFormattedFeesAttribute(): string
    {
        return number_format($this->fees, 2).' '.$this->currency;
    }

    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status === PaymentStatus::COMPLETED && $this->payment_status === 'paid';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    // Methods
    public function markAsPending(): self
    {
        $this->update([
            'status' => PaymentStatus::PENDING,
            'payment_status' => 'pending',
        ]);

        return $this;
    }

    public function markAsProcessing(): self
    {
        $this->update([
            'status' => PaymentStatus::PROCESSING,
            'payment_status' => 'processing',
            'processed_at' => now(),
        ]);

        return $this;
    }

    public function markAsCompleted(array $gatewayData = []): self
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'payment_status' => 'paid',
            'confirmed_at' => now(),
            'gateway_response' => array_merge($this->gateway_response ?? [], $gatewayData),
            'gateway_transaction_id' => $gatewayData['transaction_id'] ?? $this->gateway_transaction_id,
            'receipt_number' => $gatewayData['receipt_number'] ?? $this->generateReceiptNumber(),
        ]);

        // Activate related payable (subscription, enrollment, etc.)
        if ($this->payable && method_exists($this->payable, 'activateFromPayment')) {
            $this->payable->activateFromPayment($this);
        }

        return $this;
    }

    public function markAsFailed(?string $reason = null, array $gatewayData = []): self
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'payment_status' => 'failed',
            'failure_reason' => $reason,
            'gateway_response' => $gatewayData,
        ]);

        return $this;
    }

    public function cancel(?string $reason = null): self
    {
        $this->update([
            'status' => PaymentStatus::CANCELLED,
            'payment_status' => 'cancelled',
            'failure_reason' => $reason,
        ]);

        return $this;
    }

    public function updateGatewayResponse(array $response): self
    {
        $currentResponse = $this->gateway_response ?? [];
        $this->update([
            'gateway_response' => array_merge($currentResponse, $response),
        ]);

        return $this;
    }

    /**
     * Generate invoice data for this payment.
     *
     * Returns an InvoiceData DTO with structured invoice information
     * including invoice number, line items, and financial breakdown.
     */
    public function generateInvoiceData(): \App\Services\Payment\DTOs\InvoiceData
    {
        $invoiceService = app(\App\Services\Payment\InvoiceService::class);

        return $invoiceService->generateInvoice($this);
    }

    /**
     * Get existing invoice data for this payment without generating a new one.
     */
    public function getInvoiceData(): ?\App\Services\Payment\DTOs\InvoiceData
    {
        $invoiceService = app(\App\Services\Payment\InvoiceService::class);

        return $invoiceService->getInvoice($this);
    }

    public function calculateFees(): float
    {
        // Different fee structures based on payment method
        $feePercentages = [
            'credit_card' => 2.9,
            'debit_card' => 2.5,
            'mada' => 1.75,
            'stc_pay' => 2.0,
            'bank_transfer' => 0.5,
            'cash' => 0,
        ];

        $percentage = $feePercentages[$this->payment_method] ?? 2.9;

        return ($this->amount * $percentage) / 100;
    }

    public function calculateTax(): float
    {
        // VAT calculation (15% in Saudi Arabia)
        $taxableAmount = $this->amount - ($this->discount_amount ?? 0);

        return ($taxableAmount * ($this->tax_percentage ?? 15)) / 100;
    }

    public function updateAmounts(): self
    {
        $fees = $this->calculateFees();
        $tax = $this->calculateTax();
        $netAmount = $this->amount - $fees;

        $this->update([
            'fees' => $fees,
            'tax_amount' => $tax,
            'net_amount' => $netAmount,
        ]);

        return $this;
    }

    private function generateReceiptNumber(): string
    {
        return 'REC-'.$this->academy_id.'-'.$this->id.'-'.time();
    }

    // Static methods
    public static function createPayment(array $data): self
    {
        $payment = self::create($data);

        // Calculate fees and taxes
        $payment->updateAmounts();

        // Generate payment code if not provided
        if (! $payment->payment_code) {
            $payment->update([
                'payment_code' => 'PAY-'.$payment->academy_id.'-'.$payment->id,
            ]);
        }

        return $payment;
    }

    public static function getTotalRevenue(int $academyId, string $period = 'all'): float
    {
        $query = self::where('academy_id', $academyId)
            ->where('status', PaymentStatus::COMPLETED->value);

        switch ($period) {
            case 'today':
                $query->whereDate('payment_date', today());
                break;
            case 'this_week':
                $query->whereBetween('payment_date', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year);
                break;
            case 'this_year':
                $query->whereYear('payment_date', now()->year);
                break;
        }

        return $query->sum('amount');
    }

    public static function getPaymentStats(int $academyId): array
    {
        return [
            'total_payments' => self::where('academy_id', $academyId)->count(),
            'successful_payments' => self::where('academy_id', $academyId)->where('status', PaymentStatus::COMPLETED->value)->count(),
            'pending_payments' => self::where('academy_id', $academyId)->where('status', PaymentStatus::PENDING->value)->count(),
            'failed_payments' => self::where('academy_id', $academyId)->where('status', PaymentStatus::FAILED->value)->count(),
            'total_revenue' => self::where('academy_id', $academyId)->where('status', PaymentStatus::COMPLETED->value)->sum('amount'),
            'this_month_revenue' => self::getTotalRevenue($academyId, 'this_month'),
            'this_week_revenue' => self::getTotalRevenue($academyId, 'this_week'),
            'today_revenue' => self::getTotalRevenue($academyId, 'today'),
        ];
    }
}
