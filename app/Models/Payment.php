<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

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
        'refunded_at',
        'refund_amount',
        'refund_reason',
        'refund_reference',
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
        'refunded_amount',
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
        'refund_amount' => 'decimal:2',
        'refunded_amount' => 'integer', // Stored in cents
        'payment_date' => 'datetime',
        'processed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'paid_at' => 'datetime',
        'gateway_response' => 'array',
        'metadata' => 'array'
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

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

    public function scopeRefunded($query)
    {
        return $query->where('status', PaymentStatus::REFUNDED->value);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
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
            'urpay' => 'UrPay'
        ];

        return $methods[$this->payment_method] ?? $this->payment_method;
    }

    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'pending' => 'في الانتظار',
            'processing' => 'قيد المعالجة',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            'cancelled' => 'ملغي',
            'refunded' => 'مسترد',
            'partially_refunded' => 'مسترد جزئياً'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusBadgeColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            'refunded' => 'info',
            'partially_refunded' => 'warning'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount, 2) . ' ' . $this->currency;
    }

    public function getFormattedFeesAttribute(): string
    {
        return number_format($this->fees, 2) . ' ' . $this->currency;
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

    public function getIsRefundedAttribute(): bool
    {
        return in_array($this->status, [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED]);
    }

    public function getCanRefundAttribute(): bool
    {
        return $this->is_successful && 
               !$this->is_refunded && 
               $this->payment_date->diffInDays(now()) <= 30; // 30 days refund policy
    }

    public function getRefundableAmountAttribute(): float
    {
        if (!$this->can_refund) {
            return 0;
        }

        return $this->amount - ($this->refund_amount ?? 0);
    }

    // Methods
    public function markAsPending(): self
    {
        $this->update([
            'status' => PaymentStatus::PENDING,
            'payment_status' => 'pending'
        ]);

        return $this;
    }

    public function markAsProcessing(): self
    {
        $this->update([
            'status' => PaymentStatus::PROCESSING,
            'payment_status' => 'processing',
            'processed_at' => now()
        ]);

        return $this;
    }

    public function markAsCompleted(array $gatewayData = []): self
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'payment_status' => 'paid',
            'confirmed_at' => now(),
            'gateway_response' => $gatewayData,
            'gateway_transaction_id' => $gatewayData['transaction_id'] ?? $this->gateway_transaction_id,
            'receipt_number' => $gatewayData['receipt_number'] ?? $this->generateReceiptNumber()
        ]);

        // Activate related subscription
        if ($this->subscription) {
            $this->subscription->activate();
        }

        return $this;
    }

    public function markAsFailed(?string $reason = null, array $gatewayData = []): self
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'payment_status' => 'failed',
            'failure_reason' => $reason,
            'gateway_response' => $gatewayData
        ]);

        return $this;
    }

    public function cancel(?string $reason = null): self
    {
        $this->update([
            'status' => PaymentStatus::CANCELLED,
            'payment_status' => 'cancelled',
            'failure_reason' => $reason
        ]);

        return $this;
    }

    public function processRefund(float $amount, ?string $reason = null): self
    {
        if (!$this->can_refund) {
            throw new \Exception('هذه الدفعة غير قابلة للاسترداد');
        }

        if ($amount > $this->refundable_amount) {
            throw new \Exception('المبلغ المطلوب استرداده أكبر من المبلغ القابل للاسترداد');
        }

        $totalRefunded = ($this->refund_amount ?? 0) + $amount;
        $status = $totalRefunded >= $this->amount ? PaymentStatus::REFUNDED : PaymentStatus::PARTIALLY_REFUNDED;

        $this->update([
            'status' => $status,
            'refund_amount' => $totalRefunded,
            'refund_reason' => $reason,
            'refunded_at' => now(),
            'refund_reference' => $this->generateRefundReference()
        ]);

        return $this;
    }

    public function updateGatewayResponse(array $response): self
    {
        $currentResponse = $this->gateway_response ?? [];
        $this->update([
            'gateway_response' => array_merge($currentResponse, $response)
        ]);

        return $this;
    }

    public function generateReceipt(): string
    {
        // This would integrate with a receipt generation service
        $receiptData = [
            'payment_id' => $this->id,
            'academy' => $this->academy->name,
            'user' => $this->user->name,
            'amount' => $this->formatted_amount,
            'payment_method' => $this->payment_method_text,
            'date' => $this->payment_date->format('Y-m-d H:i:s'),
            'receipt_number' => $this->receipt_number
        ];

        // Generate PDF receipt
        $receiptUrl = config('app.url') . '/receipts/' . $this->receipt_number . '.pdf';
        
        $this->update(['receipt_url' => $receiptUrl]);
        
        return $receiptUrl;
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
            'cash' => 0
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
            'net_amount' => $netAmount
        ]);

        return $this;
    }

    private function generateReceiptNumber(): string
    {
        return 'REC-' . $this->academy_id . '-' . $this->id . '-' . time();
    }

    private function generateRefundReference(): string
    {
        return 'REF-' . $this->academy_id . '-' . $this->id . '-' . time();
    }

    // Static methods
    public static function createPayment(array $data): self
    {
        $payment = self::create($data);
        
        // Calculate fees and taxes
        $payment->updateAmounts();
        
        // Generate payment code if not provided
        if (!$payment->payment_code) {
            $payment->update([
                'payment_code' => 'PAY-' . $payment->academy_id . '-' . $payment->id
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
            'total_refunded' => self::where('academy_id', $academyId)->whereIn('status', [PaymentStatus::REFUNDED->value, PaymentStatus::PARTIALLY_REFUNDED->value])->sum('refund_amount'),
            'this_month_revenue' => self::getTotalRevenue($academyId, 'this_month'),
            'this_week_revenue' => self::getTotalRevenue($academyId, 'this_week'),
            'today_revenue' => self::getTotalRevenue($academyId, 'today')
        ];
    }
} 