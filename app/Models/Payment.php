<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Traits\ScopedToAcademy;
use App\Services\Payment\DTOs\InvoiceData;
use App\Services\Payment\InvoiceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
        // SECURITY: client_secret excluded — gateway credential, set only by PaymentService
        'redirect_url',
        'iframe_url',
        'paid_at',
        'payment_notification_sent_at',
        'subscription_notification_sent_at',
        // Note: payable_type and payable_id are NOT fillable for security (SEC-003)
        // Set polymorphic relationship explicitly in service layer
    ];

    /**
     * Sensitive attributes excluded from array/JSON serialization.
     * gateway_response may contain raw gateway payloads with card details.
     * client_secret is a gateway credential never intended for output.
     */
    protected $hidden = [
        'gateway_response',
        'client_secret',
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
        'payment_notification_sent_at' => 'datetime',
        'subscription_notification_sent_at' => 'datetime',
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

    public function markAsCompleted(array $gatewayData = []): self
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'payment_status' => 'paid',
            'payment_date' => now(),
            'paid_at' => now(),
            'confirmed_at' => now(),
            'gateway_response' => array_merge($this->gateway_response ?? [], $gatewayData),
            'gateway_transaction_id' => $gatewayData['transaction_id'] ?? $this->gateway_transaction_id,
            'receipt_number' => $gatewayData['receipt_number'] ?? $this->generateReceiptNumber(),
        ]);

        // Activate related payable (subscription, enrollment, etc.)
        if ($this->payable && method_exists($this->payable, 'activateFromPayment')) {
            $this->payable->activateFromPayment($this);
        }

        // Update subscription's purchase_source from payment metadata
        if ($this->payable instanceof BaseSubscription) {
            $metadata = is_string($this->metadata) ? json_decode($this->metadata, true) : ($this->metadata ?? []);
            $rawSource = $metadata['purchase_source'] ?? 'web';
            // Mobile purchases are completed via web browser — map to 'web'
            $purchaseSource = ($rawSource === 'mobile') ? 'web' : ($rawSource ?: 'web');

            $this->payable->update([
                'purchase_source' => $purchaseSource,
            ]);
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
    public function generateInvoiceData(): InvoiceData
    {
        $invoiceService = app(InvoiceService::class);

        return $invoiceService->generateInvoice($this);
    }

    /**
     * Get existing invoice data for this payment without generating a new one.
     */
    public function getInvoiceData(): ?InvoiceData
    {
        $invoiceService = app(InvoiceService::class);

        return $invoiceService->getInvoice($this);
    }

    public function calculateFees(): float
    {
        $method = PaymentMethod::tryFrom($this->payment_method);

        if ($method) {
            $percentage = $method->feePercentage();
        } else {
            // Fall back to config for methods not in the enum
            $percentage = config('payments.fees.'.$this->payment_method, 0) * 100;
        }

        return ($this->amount * $percentage) / 100;
    }

    public function calculateTax(): float
    {
        $taxableAmount = $this->amount - ($this->discount_amount ?? 0);
        $taxRate = $this->tax_percentage ?? (config('payments.tax.saudi_arabia', 0.15) * 100);

        return ($taxableAmount * $taxRate) / 100;
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
        // Detect purchase source from session if not provided
        $purchaseSource = $data['purchase_source'] ?? session('purchase_source', 'web');
        unset($data['purchase_source']); // Remove from data since it goes in metadata

        // Add purchase source to metadata
        $existingMetadata = isset($data['metadata']) ? (is_string($data['metadata']) ? json_decode($data['metadata'], true) : $data['metadata']) : [];
        $metadata = array_merge($existingMetadata, [
            'purchase_source' => $purchaseSource,
            'user_agent' => request()?->userAgent(),
            'ip_address' => request()?->ip(),
        ]);
        $data['metadata'] = json_encode($metadata);

        // Generate payment code before insert to satisfy NOT NULL constraint
        if (! isset($data['payment_code'])) {
            $academyId = $data['academy_id'] ?? 0;
            $data['payment_code'] = 'PAY-'.$academyId.'-'.now()->format('ymdHis').'-'.\Illuminate\Support\Str::random(4);
        }

        // Set default amounts before insert (updateAmounts() will recalculate after)
        $amount = $data['amount'] ?? 0;
        $data['net_amount'] = $data['net_amount'] ?? $amount;
        $data['fees'] = $data['fees'] ?? 0;
        $data['tax_amount'] = $data['tax_amount'] ?? 0;

        $payment = self::create($data);

        // Recalculate fees and taxes
        $payment->updateAmounts();

        return $payment;
    }

    /**
     * Generate a unique payment code.
     *
     * Uses timestamp (with seconds) + random string to avoid race condition collisions.
     * Format: PREFIX-ACADEMY_ID-TIMESTAMP-RANDOM
     * Example: ASP-01-20260216143052-AB3X
     *
     * @param  int  $academyId  Academy ID
     * @param  string  $prefix  Payment code prefix (ASP, QSP, ICP, etc.)
     * @return string Unique payment code
     */
    public static function generatePaymentCode(int $academyId, string $prefix = 'PAY'): string
    {
        // Use full timestamp including seconds for better uniqueness
        $timestamp = now()->format('ymdHis'); // 20260216143052

        // Use alphanumeric random string (62^4 = 14.7M combinations vs mt_rand's 10K)
        $random = strtoupper(Str::random(4));

        // Format: PREFIX-ACADEMY-TIMESTAMP-RANDOM
        return "{$prefix}-".str_pad($academyId, 2, '0', STR_PAD_LEFT)."-{$timestamp}-{$random}";
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
