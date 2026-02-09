<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment webhook event for idempotency and audit.
 *
 * Stores all incoming webhook events to prevent duplicate processing
 * and provide an audit trail.
 */
class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'academy_id',
        'payment_id',
        'gateway',
        'event_type',
        'event_id',
        'transaction_id',
        'status',
        'amount_cents',
        'currency',
        'is_processed',
        'processed_at',
        'payload',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_processed' => 'boolean',
        'processed_at' => 'datetime',
        'amount_cents' => 'integer',
    ];

    /**
     * Get the academy that owns this event.
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the payment associated with this event.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Check if this event has already been processed.
     */
    public function isProcessed(): bool
    {
        return $this->is_processed;
    }

    /**
     * Mark the event as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'is_processed' => true,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark the event as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'is_processed' => true,
            'processed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Check if an event with this ID already exists.
     *
     * Renamed from exists() to avoid overriding Eloquent's exists() method.
     */
    public static function eventExists(string $eventId): bool
    {
        return static::where('event_id', $eventId)->exists();
    }

    /**
     * Create from webhook payload.
     */
    public static function createFromPayload(
        string $gateway,
        string $eventType,
        string $eventId,
        array $payload,
        ?int $paymentId = null,
        ?int $academyId = null
    ): self {
        $obj = $payload['obj'] ?? $payload;

        return static::create([
            'gateway' => $gateway,
            'event_type' => $eventType,
            'event_id' => $eventId,
            'payment_id' => $paymentId,
            'academy_id' => $academyId,
            'transaction_id' => (string) ($obj['id'] ?? ''),
            'status' => ($obj['success'] ?? false) ? 'success' : 'failed',
            'amount_cents' => (int) ($obj['amount_cents'] ?? 0),
            'currency' => $obj['currency'] ?? config('currencies.default', 'SAR'),
            'payload' => $payload,
            'is_processed' => false,
        ]);
    }

    /**
     * Scope: Unprocessed events.
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    /**
     * Scope: For a specific gateway.
     */
    public function scopeForGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Get amount in major currency units.
     */
    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }
}
