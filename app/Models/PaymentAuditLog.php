<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment audit log for tracking all payment-related actions.
 *
 * Provides a comprehensive audit trail for compliance and debugging.
 */
class PaymentAuditLog extends Model
{
    protected $fillable = [
        'academy_id',
        'payment_id',
        'user_id',
        'action',
        'gateway',
        'status_from',
        'status_to',
        'amount_cents',
        'currency',
        'transaction_id',
        'ip_address',
        'user_agent',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount_cents' => 'integer',
    ];

    /**
     * Get the academy that owns this log.
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the payment associated with this log.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a payment creation.
     */
    public static function logCreation(Payment $payment, ?int $userId = null): self
    {
        return static::log($payment, 'created', null, $payment->status, $userId);
    }

    /**
     * Log a status change.
     */
    public static function logStatusChange(
        Payment $payment,
        string $fromStatus,
        string $toStatus,
        ?int $userId = null,
        ?string $notes = null
    ): self {
        return static::log($payment, 'status_changed', $fromStatus, $toStatus, $userId, [
            'notes' => $notes,
        ]);
    }

    /**
     * Log a webhook received.
     */
    public static function logWebhook(
        Payment $payment,
        string $gateway,
        string $transactionId,
        array $payload
    ): self {
        return static::create([
            'academy_id' => $payment->academy_id,
            'payment_id' => $payment->id,
            'action' => 'webhook_received',
            'gateway' => $gateway,
            'status_from' => $payment->status,
            'transaction_id' => $transactionId,
            'amount_cents' => (int) ($payment->amount * 100),
            'currency' => $payment->currency ?? 'SAR',
            'metadata' => [
                'payload_summary' => [
                    'success' => $payload['obj']['success'] ?? false,
                    'transaction_id' => $payload['obj']['id'] ?? null,
                ],
            ],
        ]);
    }

    /**
     * Log a refund.
     */
    public static function logRefund(
        Payment $payment,
        int $refundAmountCents,
        string $transactionId,
        ?int $userId = null
    ): self {
        return static::create([
            'academy_id' => $payment->academy_id,
            'payment_id' => $payment->id,
            'user_id' => $userId,
            'action' => 'refunded',
            'gateway' => $payment->payment_gateway,
            'status_from' => $payment->status,
            'status_to' => 'refunded',
            'amount_cents' => $refundAmountCents,
            'currency' => $payment->currency ?? 'SAR',
            'transaction_id' => $transactionId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a payment attempt.
     */
    public static function logAttempt(
        Payment $payment,
        string $gateway,
        ?string $errorMessage = null
    ): self {
        return static::create([
            'academy_id' => $payment->academy_id,
            'payment_id' => $payment->id,
            'action' => $errorMessage ? 'attempt_failed' : 'attempt_initiated',
            'gateway' => $gateway,
            'status_from' => $payment->status,
            'amount_cents' => (int) ($payment->amount * 100),
            'currency' => $payment->currency ?? 'SAR',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'notes' => $errorMessage,
        ]);
    }

    /**
     * Generic log helper.
     */
    protected static function log(
        Payment $payment,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?int $userId = null,
        array $extra = []
    ): self {
        return static::create(array_merge([
            'academy_id' => $payment->academy_id,
            'payment_id' => $payment->id,
            'user_id' => $userId,
            'action' => $action,
            'gateway' => $payment->payment_gateway,
            'status_from' => $fromStatus,
            'status_to' => $toStatus,
            'amount_cents' => (int) ($payment->amount * 100),
            'currency' => $payment->currency ?? 'SAR',
            'transaction_id' => $payment->transaction_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $extra));
    }

    /**
     * Scope: For a specific payment.
     */
    public function scopeForPayment($query, int $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    /**
     * Scope: For a specific action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Get amount in major currency units.
     */
    public function getAmountAttribute(): float
    {
        return ($this->amount_cents ?? 0) / 100;
    }

    /**
     * Get Arabic label for action.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'إنشاء دفعة',
            'status_changed' => 'تغيير الحالة',
            'webhook_received' => 'استلام إشعار',
            'refunded' => 'استرداد',
            'attempt_initiated' => 'بدء محاولة دفع',
            'attempt_failed' => 'فشل محاولة دفع',
            default => $this->action,
        };
    }
}
