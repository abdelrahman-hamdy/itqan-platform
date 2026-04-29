<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * SubscriptionCycle
 *
 * One billing period of a subscription thread. Multiple cycles per subscription;
 * at most one `active` at a time, at most one `queued`, any number `archived`.
 *
 * The parent subscription row always mirrors the `active` cycle's columns
 * (starts_at, ends_at, total_sessions, sessions_used, pricing, package info).
 *
 * Early renewal creates a `queued` cycle to be auto-promoted when the current
 * cycle ends. Exhausted-sessions renewal creates an `active` cycle immediately
 * and archives the previous one.
 */
class SubscriptionCycle extends Model
{
    use HasFactory, ScopedToAcademy;

    public const STATE_QUEUED = 'queued';

    public const STATE_ACTIVE = 'active';

    public const STATE_ARCHIVED = 'archived';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_FAILED = 'failed';

    public const PAYMENT_WAIVED = 'waived';

    protected $fillable = [
        'subscribable_type',
        'subscribable_id',
        'academy_id',
        'cycle_number',
        'cycle_state',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'total_sessions',
        'sessions_used',
        'sessions_completed',
        'sessions_missed',
        'carryover_sessions',
        'total_price',
        'discount_amount',
        'final_price',
        'currency',
        'package_id',
        'package_snapshot',
        'payment_id',
        'payment_status',
        'grace_period_ends_at',
        'archived_at',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'archived_at' => 'datetime',
        'total_sessions' => 'integer',
        'sessions_used' => 'integer',
        'sessions_completed' => 'integer',
        'sessions_missed' => 'integer',
        'carryover_sessions' => 'integer',
        'cycle_number' => 'integer',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'package_snapshot' => 'array',
        'metadata' => 'array',
    ];

    // ========================================================================
    // Relations
    // ========================================================================

    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    // ========================================================================
    // Scopes
    // ========================================================================

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('cycle_state', self::STATE_ACTIVE);
    }

    public function scopeQueued(Builder $q): Builder
    {
        return $q->where('cycle_state', self::STATE_QUEUED);
    }

    public function scopeArchived(Builder $q): Builder
    {
        return $q->where('cycle_state', self::STATE_ARCHIVED);
    }

    /**
     * Cycles that should advance next: active with ends_at in the past.
     */
    public function scopeDueForAdvance(Builder $q): Builder
    {
        return $q->where('cycle_state', self::STATE_ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now());
    }

    /**
     * Cycles in an active grace period (grace_period_ends_at is in the future).
     */
    public function scopeInGracePeriod(Builder $q): Builder
    {
        return $q->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '>', now());
    }

    // ========================================================================
    // Derived helpers
    // ========================================================================

    public function isQueued(): bool
    {
        return $this->cycle_state === self::STATE_QUEUED;
    }

    public function isActive(): bool
    {
        return $this->cycle_state === self::STATE_ACTIVE;
    }

    public function isArchived(): bool
    {
        return $this->cycle_state === self::STATE_ARCHIVED;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function isPaymentPending(): bool
    {
        return $this->payment_status === self::PAYMENT_PENDING;
    }

    public function isInGracePeriod(): bool
    {
        return $this->grace_period_ends_at !== null
            && $this->grace_period_ends_at->isFuture();
    }

    /**
     * Sessions remaining in this specific cycle.
     */
    public function getSessionsRemainingAttribute(): int
    {
        return max(0, ((int) $this->total_sessions) - ((int) $this->sessions_used));
    }

    // ========================================================================
    // Materialization helper (shared between renewal and consolidation)
    // ========================================================================

    /**
     * Snapshot a subscription's current column values as a SubscriptionCycle.
     *
     * Single source of truth for "materialize a cycle from a subscription row".
     * Used by `SubscriptionRenewalService::ensureCurrentCycle()` — lazy backfill
     * for legacy subscriptions that predate the cycle refactor.
     *
     * The `$owner` argument is currently always equal to `$source` (subscription
     * isolation rule: cycles never belong to a different subscription). Kept as a
     * separate parameter to preserve the API surface.
     *
     * @param  BaseSubscription  $source  Row whose columns are snapshotted
     * @param  BaseSubscription  $owner  Thread the cycle belongs to (usually same as source)
     * @param  string  $state  SubscriptionCycle::STATE_* value
     * @param  array  $overrides  Optional field overrides (e.g. metadata, cycle_number)
     */
    public static function materializeFromSubscription(
        BaseSubscription $source,
        BaseSubscription $owner,
        string $state = self::STATE_ACTIVE,
        array $overrides = [],
    ): self {
        $nextNumber = $overrides['cycle_number']
            ?? (((int) static::query()
                ->where('subscribable_type', $owner->getMorphClass())
                ->where('subscribable_id', $owner->id)
                ->max('cycle_number')) + 1);

        $paymentStatus = $source->payment_status?->value === SubscriptionPaymentStatus::PAID->value
            ? self::PAYMENT_PAID
            : self::PAYMENT_PENDING;

        $defaults = [
            'subscribable_type' => $owner->getMorphClass(),
            'subscribable_id' => $owner->id,
            'academy_id' => $source->academy_id,
            'cycle_number' => max(1, (int) $nextNumber),
            'cycle_state' => $state,
            'billing_cycle' => $source->billing_cycle?->value ?? 'monthly',
            'starts_at' => $source->starts_at,
            'ends_at' => $source->ends_at,
            'total_sessions' => (int) ($source->total_sessions ?? 0),
            'sessions_used' => (int) ($source->sessions_used ?? 0),
            'sessions_completed' => (int) ($source->total_sessions_completed ?? 0),
            'sessions_missed' => (int) ($source->total_sessions_missed ?? 0),
            'carryover_sessions' => 0,
            'total_price' => (float) ($source->total_price ?? 0),
            'discount_amount' => (float) ($source->discount_amount ?? 0),
            'final_price' => (float) ($source->final_price ?? 0),
            'currency' => $source->currency ?? 'SAR',
            'payment_status' => $paymentStatus,
            'grace_period_ends_at' => $source->getGracePeriodEndsAt(),
            'archived_at' => $state === self::STATE_ARCHIVED ? ($source->ends_at ?? now()) : null,
        ];

        return self::create(array_merge($defaults, $overrides));
    }

    /**
     * Drop this cycle and its linked payment, but only if both are still
     * unpaid/pending. Refuses to touch a cycle whose payment has already
     * completed — those represent real money and must never be discarded
     * by an "abandoned attempt" cleanup path.
     *
     * Caller is responsible for opening a transaction.
     */
    public function deleteIfAbandoned(): bool
    {
        if ($this->payment_status !== self::PAYMENT_PENDING) {
            return false;
        }

        if ($this->payment_id) {
            Payment::where('id', $this->payment_id)
                ->where('status', PaymentStatus::PENDING)
                ->delete();
        }

        return (bool) $this->delete();
    }
}
