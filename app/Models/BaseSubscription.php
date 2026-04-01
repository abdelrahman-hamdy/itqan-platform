<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\PurchaseSource;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Base Subscription Model
 *
 * This abstract class contains all common functionality shared across:
 * - QuranSubscription
 * - AcademicSubscription
 * - CourseSubscription
 *
 * Purpose: Eliminate code duplication (~600 lines) and provide consistent
 * subscription behavior across all subscription types.
 *
 * Design Pattern: Mirrors BaseSession exactly - static $baseFillable, constructor
 * merge, getCasts() override pattern for child classes.
 *
 * @property int $id
 * @property int $academy_id
 * @property int $student_id
 * @property string $subscription_code
 * @property SessionSubscriptionStatus $status
 * @property string|null $package_name_ar
 * @property string|null $package_name_en
 * @property string|null $package_description_ar
 * @property string|null $package_description_en
 * @property array|null $package_features
 * @property float|null $monthly_price
 * @property float|null $quarterly_price
 * @property float|null $yearly_price
 * @property float|null $discount_amount
 * @property float $final_price
 * @property string $currency
 * @property BillingCycle $billing_cycle
 * @property SubscriptionPaymentStatus $payment_status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $next_billing_date
 * @property Carbon|null $last_payment_date
 * @property bool $auto_renew
 * @property Carbon|null $renewal_reminder_sent_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property float $progress_percentage
 * @property bool $certificate_issued
 * @property Carbon|null $certificate_issued_at
 * @property string|null $notes
 * @property array|null $metadata
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method \Illuminate\Database\Eloquent\Relations\HasMany sessions()
 * @method self linkToEducationUnit($unit)
 * @method void cancelAsDuplicateOrExpired(?string $reason = null)
 * @method void cancelDueToPaymentFailure()
 */
abstract class BaseSubscription extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Common fillable fields across all subscription types
     * Child classes should merge their specific fields with this static property
     * IMPORTANT: Made static to allow child classes to access via parent::$baseFillable
     */
    protected static $baseFillable = [
        // Core subscription fields
        'academy_id',
        'student_id',
        'previous_subscription_id',
        'subscription_code',
        'status',

        // Package snapshot (self-contained - copied from packages at creation)
        'package_name_ar',
        'package_name_en',
        'package_description_ar',
        'package_description_en',
        'package_features',

        // Sessions configuration (from package)
        'session_duration_minutes',

        // Pricing snapshot (all tiers from package)
        'package_price_monthly',
        'package_price_quarterly',
        'package_price_yearly',
        'total_price',
        'discount_amount',
        'final_price',
        'currency',

        // Billing
        'billing_cycle',
        'payment_status',
        'purchase_source',

        // Access tracking
        'last_accessed_at',
        'last_accessed_platform',

        // Lifecycle dates
        'starts_at',
        'ends_at',
        'next_billing_date',
        'last_payment_date',

        // Auto-renewal
        'auto_renew',
        'renewal_reminder_sent_at',

        // Cancellation (no pause feature)
        'cancelled_at',
        'cancellation_reason',

        // Progress tracking
        'progress_percentage',
        'last_session_at',

        // Certificate
        'certificate_issued',
        'certificate_issued_at',

        // Metadata
        'notes',
        'metadata',
    ];

    /**
     * Instance-level fillable (automatically set from $baseFillable)
     * This ensures Laravel's mass assignment protection works correctly
     */
    protected $fillable = [];

    /**
     * Constructor to initialize fillable from static $baseFillable
     * Only sets fillable if not already set by child class
     */
    public function __construct(array $attributes = [])
    {
        // Only initialize fillable if not already set by child class
        // Child classes should merge their fields with parent::$baseFillable first
        if (empty($this->fillable)) {
            $this->fillable = static::$baseFillable;
        }
        parent::__construct($attributes);
    }

    /**
     * Common casts across all subscription types
     * Child classes should use getCasts() override pattern to merge with parent casts
     * IMPORTANT: Do NOT define protected $casts in child classes - it would override parent's casts
     */
    protected $casts = [
        'status' => SessionSubscriptionStatus::class,
        'payment_status' => SubscriptionPaymentStatus::class,
        'billing_cycle' => BillingCycle::class,
        'purchase_source' => PurchaseSource::class,

        // Dates
        'last_accessed_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'next_billing_date' => 'datetime',
        'last_payment_date' => 'datetime',
        'renewal_reminder_sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_session_at' => 'datetime',
        'certificate_issued_at' => 'datetime',

        // Integers
        'session_duration_minutes' => 'integer',

        // Decimals
        'package_price_monthly' => 'decimal:2',
        'package_price_quarterly' => 'decimal:2',
        'package_price_yearly' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'progress_percentage' => 'decimal:2',

        // Booleans
        'auto_renew' => 'boolean',
        'certificate_issued' => 'boolean',

        // Arrays
        'package_features' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Default attribute values
     */
    protected $attributes = [
        'status' => SessionSubscriptionStatus::PENDING->value,
        'payment_status' => SubscriptionPaymentStatus::PENDING->value,
        'currency' => 'SAR',
        'billing_cycle' => BillingCycle::MONTHLY->value,
        'auto_renew' => true,
        'progress_percentage' => 0,
        'certificate_issued' => false,
    ];

    // ========================================
    // RELATIONSHIPS (Common to all subscriptions)
    // ========================================

    /**
     * Get the academy this subscription belongs to
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the student for this subscription
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the previous subscription in the renewal chain.
     */
    public function previousSubscription(): BelongsTo
    {
        return $this->belongsTo(static::class, 'previous_subscription_id');
    }

    /**
     * Get the subscription that renewed this one (if any).
     */
    public function renewedBySubscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(static::class, 'previous_subscription_id');
    }

    /**
     * Check if this subscription has a pending renewal.
     */
    public function hasPendingRenewal(): bool
    {
        return static::where('previous_subscription_id', $this->id)
            ->where('status', SessionSubscriptionStatus::PENDING)
            ->exists();
    }

    /**
     * Get the certificate for this subscription (polymorphic)
     */
    public function certificate(): MorphOne
    {
        return $this->morphOne(Certificate::class, 'certificateable');
    }

    /**
     * Get payment records for this subscription (polymorphic)
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    // ========================================
    // SCOPES (Common to all subscriptions)
    // ========================================

    /**
     * Scope: Get active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', SessionSubscriptionStatus::ACTIVE);
    }

    /**
     * Scope: Get pending subscriptions
     */
    public function scopePending($query)
    {
        return $query->where('status', SessionSubscriptionStatus::PENDING);
    }

    /**
     * Scope: Get paused subscriptions
     */
    public function scopePaused($query)
    {
        return $query->where('status', SessionSubscriptionStatus::PAUSED);
    }

    /**
     * Scope: Get cancelled subscriptions
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', SessionSubscriptionStatus::CANCELLED);
    }

    /**
     * Scope: Get expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('status', SessionSubscriptionStatus::EXPIRED);
    }

    /**
     * Scope: Get subscriptions expiring soon (within N days)
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', SessionSubscriptionStatus::ACTIVE)
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }

    /**
     * Scope: Get subscriptions due for renewal
     */
    public function scopeDueForRenewal($query)
    {
        return $query->where('auto_renew', true)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('next_billing_date', '<=', now()->addDays(3));
    }

    /**
     * Scope: Get subscriptions that need renewal reminders
     */
    public function scopeNeedsRenewalReminder($query, int $daysBeforeRenewal = 7)
    {
        return $query->where('auto_renew', true)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->whereNull('renewal_reminder_sent_at')
            ->whereDate('next_billing_date', now()->addDays($daysBeforeRenewal)->toDateString());
    }

    /**
     * Scope: Get subscriptions with paid status
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', SubscriptionPaymentStatus::PAID);
    }

    /**
     * Scope: Get subscriptions with payment failed
     */
    public function scopePaymentFailed($query)
    {
        return $query->where('payment_status', SubscriptionPaymentStatus::FAILED);
    }

    /**
     * Scope: Get subscriptions for a specific student
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope: Get subscriptions by billing cycle
     */
    public function scopeForBillingCycle($query, BillingCycle $cycle)
    {
        return $query->where('billing_cycle', $cycle);
    }

    // ========================================
    // STATUS HELPER METHODS
    // ========================================

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === SessionSubscriptionStatus::ACTIVE;
    }

    /**
     * Check if subscription is pending
     */
    public function isPending(): bool
    {
        return $this->status === SessionSubscriptionStatus::PENDING;
    }

    /**
     * Check if subscription is paused
     */
    public function isPaused(): bool
    {
        return $this->status === SessionSubscriptionStatus::PAUSED;
    }

    /**
     * Check if subscription is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === SessionSubscriptionStatus::CANCELLED;
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->status === SessionSubscriptionStatus::EXPIRED;
    }

    /**
     * Check if subscription can be renewed
     */
    public function canRenew(): bool
    {
        return $this->status->canRenew();
    }

    /**
     * Check if subscription can be cancelled
     */
    public function canCancel(): bool
    {
        return $this->status->canCancel();
    }

    /**
     * Check if subscription allows content access
     */
    public function canAccess(): bool
    {
        // Allow access during active grace periods regardless of payment_status
        if ($this->isInGracePeriod()) {
            return true;
        }

        return $this->status->canAccess() && $this->payment_status->allowsAccess();
    }

    /**
     * Check if subscription can be paused
     */
    public function canPause(): bool
    {
        return $this->status->canPause();
    }

    /**
     * Check if subscription can be resumed
     */
    public function canResume(): bool
    {
        return $this->status->canResume();
    }

    /**
     * Check if subscription has auto-renewal enabled
     */
    public function hasAutoRenewal(): bool
    {
        return $this->auto_renew && $this->billing_cycle->supportsAutoRenewal();
    }

    // ========================================
    // DATE CALCULATION METHODS
    // ========================================

    /**
     * Calculate end date based on start date and billing cycle
     */
    public function calculateEndDate(?Carbon $startDate = null): Carbon
    {
        $start = $startDate ?? $this->starts_at ?? now();

        return $this->billing_cycle->calculateEndDate($start);
    }

    /**
     * Calculate next billing date
     */
    public function calculateNextBillingDate(): Carbon
    {
        $currentBillingDate = $this->next_billing_date ?? $this->ends_at ?? now();

        return $this->billing_cycle->nextBillingDate($currentBillingDate);
    }

    /**
     * Get days remaining in subscription
     */
    public function getDaysRemainingAttribute(): int
    {
        if (! $this->ends_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->ends_at, false));
    }

    /**
     * Check if all sessions have been used up (but subscription period may still be active).
     */
    public function getIsSessionsExhaustedAttribute(): bool
    {
        return ! empty(($this->metadata ?? [])['sessions_exhausted']);
    }

    /**
     * Check if subscription is expiring soon (within 7 days)
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        if (! $this->ends_at) {
            return false;
        }

        return $this->isActive() && $this->days_remaining <= $days;
    }

    /**
     * Check if subscription is currently in grace period (admin-granted or auto-renewal failure).
     */
    public function isInGracePeriod(): bool
    {
        $metadata = $this->metadata ?? [];

        // Check both keys: standardized key and legacy key (for existing records)
        $key = isset($metadata['grace_period_ends_at']) ? 'grace_period_ends_at'
            : (isset($metadata['grace_period_expires_at']) ? 'grace_period_expires_at' : null);

        if (! $key) {
            return false;
        }

        return Carbon::parse($metadata[$key])->isFuture();
    }

    /**
     * Get grace period end date if set, null otherwise.
     */
    public function getGracePeriodEndsAt(): ?Carbon
    {
        $metadata = $this->metadata ?? [];

        // Check both keys: standardized key and legacy key (for existing records)
        $key = isset($metadata['grace_period_ends_at']) ? 'grace_period_ends_at'
            : (isset($metadata['grace_period_expires_at']) ? 'grace_period_expires_at' : null);

        if (! $key) {
            return null;
        }

        return Carbon::parse($metadata[$key]);
    }

    /**
     * Check if subscription needs renewal (past ends_at but still active or suspended).
     */
    public function needsRenewal(): bool
    {
        if (! $this->ends_at) {
            return false;
        }

        return $this->ends_at->isPast() && $this->status->canRenew();
    }

    // ========================================
    // SUBSCRIPTION LIFECYCLE METHODS
    // ========================================

    /**
     * Activate subscription (after successful payment)
     */
    public function activate(): self
    {
        $this->update([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'starts_at' => $this->starts_at ?? now(),
            'ends_at' => $this->calculateEndDate($this->starts_at ?? now()),
            'next_billing_date' => $this->calculateEndDate($this->starts_at ?? now()),
            'last_payment_date' => now(),
        ]);

        return $this;
    }

    /**
     * Cancel subscription
     */
    public function cancel(?string $reason = null): self
    {
        if (! $this->canCancel()) {
            throw new Exception(__('subscriptions.errors.cannot_cancel'));
        }

        $this->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);

        return $this;
    }

    /**
     * Pause subscription (when sessions run out or user requests pause)
     */
    public function pause(?string $reason = null): self
    {
        if (! $this->canPause()) {
            throw new Exception(__('subscriptions.errors.cannot_pause'));
        }

        $this->update([
            'status' => SessionSubscriptionStatus::PAUSED,
            'paused_at' => now(),
            'pause_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Resume a paused subscription
     */
    public function resume(): self
    {
        if (! $this->canResume()) {
            throw new Exception(__('subscriptions.errors.cannot_resume'));
        }

        $this->update([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'paused_at' => null,
            'pause_reason' => null,
        ]);

        return $this;
    }

    /**
     * Enable auto-renewal
     */
    public function enableAutoRenewal(): self
    {
        if (! $this->billing_cycle->supportsAutoRenewal()) {
            throw new Exception(__('subscriptions.errors.no_auto_renewal_support'));
        }

        $this->update(['auto_renew' => true]);

        return $this;
    }

    /**
     * Disable auto-renewal
     */
    public function disableAutoRenewal(): self
    {
        $this->update(['auto_renew' => false]);

        return $this;
    }

    // ========================================
    // PRICING METHODS
    // ========================================

    /**
     * Get price for current billing cycle
     */
    public function getPriceForBillingCycle(): float
    {
        return match ($this->billing_cycle) {
            BillingCycle::MONTHLY => $this->package_price_monthly ?? 0,
            BillingCycle::QUARTERLY => $this->package_price_quarterly ?? (($this->package_price_monthly ?? 0) * 3),
            BillingCycle::YEARLY => $this->package_price_yearly ?? (($this->package_price_monthly ?? 0) * 12),
            BillingCycle::LIFETIME => $this->final_price ?? 0,
        };
    }

    /**
     * Get formatted price for display
     */
    public function getFormattedPriceAttribute(): string
    {
        $price = $this->final_price ?? $this->getPriceForBillingCycle();

        return number_format($price, 2).' '.$this->currency;
    }

    // ========================================
    // CERTIFICATE METHODS
    // ========================================

    /**
     * Check if subscription is eligible for certificate
     */
    public function isCertificateEligible(): bool
    {
        // Default: Certificate eligible when progress >= 90% and subscription is active
        return $this->isActive() && $this->progress_percentage >= 90;
    }

    /**
     * Issue certificate for this subscription
     */
    public function issueCertificate(): self
    {
        if ($this->certificate_issued) {
            throw new Exception(__('subscriptions.errors.certificate_already_issued'));
        }

        if (! $this->isCertificateEligible()) {
            throw new Exception(__('subscriptions.errors.certificate_not_eligible'));
        }

        $this->update([
            'certificate_issued' => true,
            'certificate_issued_at' => now(),
        ]);

        return $this;
    }

    // ========================================
    // SUBSCRIPTION CODE GENERATION
    // ========================================

    /**
     * Generate unique subscription code
     */
    public static function generateSubscriptionCode(int $academyId, string $prefix = 'SUB'): string
    {
        $timestamp = now()->format('ymd');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$academyId}-{$timestamp}-{$random}";
    }

    // ========================================
    // DISPLAY HELPERS
    // ========================================

    /**
     * Get subscription status display data
     */
    public function getStatusDisplayData(): array
    {
        $inGracePeriod = $this->isInGracePeriod();
        $needsRenewal = $this->needsRenewal();
        $sessionsExhausted = $this->is_sessions_exhausted;

        // Determine display state priority: grace period > sessions exhausted > normal status
        if ($inGracePeriod) {
            $label = __('subscriptions.grace_period_label', [], 'ar');
            $labelEn = __('subscriptions.grace_period_label', [], 'en');
            $icon = 'heroicon-o-exclamation-triangle';
            $color = 'warning';
            $badgeClasses = 'bg-orange-100 text-orange-800';
        } elseif ($sessionsExhausted && $this->isActive()) {
            $label = __('subscriptions.sessions_exhausted');
            $labelEn = __('subscriptions.sessions_exhausted', [], 'en');
            $icon = 'heroicon-o-check-badge';
            $color = 'warning';
            $badgeClasses = 'bg-amber-100 text-amber-800';
        } else {
            $label = $this->status->label();
            $labelEn = $this->status->labelEn();
            $icon = $this->status->icon();
            $color = $this->status->color();
            $badgeClasses = $this->status->badgeClasses();
        }

        return [
            'status' => $this->status->value,
            'label' => $label,
            'label_en' => $labelEn,
            'icon' => $icon,
            'color' => $color,
            'badge_classes' => $badgeClasses,
            'can_access' => $this->canAccess(),
            'can_renew' => $this->canRenew() || $sessionsExhausted,
            'can_cancel' => $this->canCancel(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'days_remaining' => $this->days_remaining,
            'in_grace_period' => $inGracePeriod,
            'needs_renewal' => $needsRenewal,
            'sessions_exhausted' => $sessionsExhausted,
            'renewal_message' => $sessionsExhausted ? __('subscriptions.sessions_exhausted_message') : null,
            'grace_period_ends_at' => $this->getGracePeriodEndsAt()?->format('Y-m-d'),
            'paid_until' => $this->ends_at?->format('Y-m-d'),
        ];
    }

    /**
     * Get subscription summary for unified view
     */
    public function getSubscriptionSummary(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->getSubscriptionType(),
            'type_label' => $this->getSubscriptionTypeLabel(),
            'code' => $this->subscription_code,
            'title' => $this->package_name_ar ?? $this->getSubscriptionTitle(),
            'status' => $this->getStatusDisplayData(),
            'price' => $this->formatted_price,
            'billing_cycle' => $this->billing_cycle->label(),
            'starts_at' => $this->starts_at?->format('Y-m-d'),
            'ends_at' => $this->ends_at?->format('Y-m-d'),
            'days_remaining' => $this->days_remaining,
            'progress' => $this->progress_percentage,
            'auto_renew' => $this->auto_renew,
            'teacher' => $this->getTeacher()?->name ?? null,
            'created_at' => $this->created_at?->format('Y-m-d'),
            'in_grace_period' => $this->isInGracePeriod(),
            'needs_renewal' => $this->needsRenewal(),
            'grace_period_ends_at' => $this->getGracePeriodEndsAt()?->format('Y-m-d'),
        ];
    }

    // ========================================
    // ABSTRACT METHODS (Must be implemented by child classes)
    // ========================================

    /**
     * Get subscription type identifier (e.g., 'quran', 'academic', 'course')
     * Must be implemented by each child class
     */
    abstract public function getSubscriptionType(): string;

    /**
     * Get human-readable subscription type label
     * Must be implemented by each child class
     */
    abstract public function getSubscriptionTypeLabel(): string;

    /**
     * Get subscription title for display
     * Must be implemented by each child class
     */
    abstract public function getSubscriptionTitle(): string;

    /**
     * Get the teacher for this subscription (if applicable)
     * Returns null for course subscriptions
     */
    abstract public function getTeacher(): ?User;

    /**
     * Calculate renewal price for this subscription
     * Must be implemented by each child class
     */
    abstract public function calculateRenewalPrice(): float;

    /**
     * Copy package data to subscription (self-containment)
     * Called on subscription creation to snapshot package data
     * Must be implemented by each child class
     */
    abstract public function snapshotPackageData(): array;

    /**
     * Get subscription-specific sessions/content relationship
     * Must be implemented by each child class
     */
    abstract public function getSessions();

    // ========================================
    // SESSION USAGE METHODS
    // ========================================

    /**
     * Consume one session from the subscription.
     * Consolidates counters + exhaustion metadata into a single UPDATE.
     */
    public function useSession(): self
    {
        return DB::transaction(function () {
            $subscription = static::lockForUpdate()->find($this->id);

            if (! $subscription) {
                throw new Exception(__('subscriptions.subscription_not_found'));
            }

            if ($subscription->sessions_remaining <= 0) {
                Log::warning(class_basename(static::class)." {$subscription->id} has no remaining sessions, allowing over-usage", [
                    'subscription_id' => $subscription->id,
                    'sessions_remaining' => $subscription->sessions_remaining,
                ]);
            }

            $newRemaining = max(0, $subscription->sessions_remaining - 1);

            $updateData = [
                'sessions_used' => $subscription->sessions_used + 1,
                'sessions_remaining' => $newRemaining,
                'total_sessions_completed' => $subscription->total_sessions_completed + 1,
                'last_session_at' => now(),
            ];

            if ($newRemaining <= 0) {
                $metadata = $subscription->metadata ?? [];
                $metadata['sessions_exhausted'] = true;
                $metadata['sessions_exhausted_at'] = now()->toDateTimeString();
                $updateData['progress_percentage'] = 100;
                $updateData['metadata'] = $metadata;
            }

            $subscription->update($updateData);

            $this->refresh();

            return $this;
        });
    }

    /**
     * Return a session to the subscription (reverse of useSession).
     * Called when a session is cancelled after being counted.
     */
    public function returnSession(): self
    {
        return DB::transaction(function () {
            $subscription = static::lockForUpdate()->find($this->id);

            if (! $subscription) {
                throw new Exception(__('subscriptions.subscription_not_found'));
            }

            $newRemaining = $subscription->sessions_remaining + 1;

            $updateData = [
                'sessions_used' => max(0, $subscription->sessions_used - 1),
                'sessions_remaining' => $newRemaining,
                'total_sessions_completed' => max(0, $subscription->total_sessions_completed - 1),
            ];

            // Clear sessions_exhausted flag if sessions are available again
            $metadata = $subscription->metadata ?? [];
            if (! empty($metadata['sessions_exhausted']) && $newRemaining > 0) {
                unset($metadata['sessions_exhausted'], $metadata['sessions_exhausted_at']);
                $updateData['metadata'] = $metadata ?: null;
            }

            // Legacy: If subscription was paused due to old exhaustion logic, reactivate in same UPDATE
            if ($subscription->status === SessionSubscriptionStatus::PAUSED
                && $subscription->pause_reason === config('subscriptions.legacy_sessions_exhausted_pause_reason')) {
                $updateData['status'] = SessionSubscriptionStatus::ACTIVE;
                $updateData['paused_at'] = null;
                $updateData['pause_reason'] = null;
            }

            $subscription->update($updateData);

            Log::info('Session returned to '.class_basename(static::class)." {$subscription->id}");

            $this->refresh();

            return $this;
        });
    }

    // ========================================
    // SESSION TRACKING ABSTRACT METHODS
    // ========================================

    /**
     * Get total number of sessions in subscription
     * Must be implemented by each child class based on their session tracking approach
     */
    abstract public function getTotalSessions(): int;

    /**
     * Get number of sessions used/completed
     * Must be implemented by each child class based on their session tracking approach
     */
    abstract public function getSessionsUsed(): int;

    /**
     * Get number of sessions remaining
     * Must be implemented by each child class based on their session tracking approach
     */
    abstract public function getSessionsRemaining(): int;
}
