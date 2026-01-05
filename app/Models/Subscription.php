<?php

namespace App\Models;

use App\Enums\SessionSubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'student_id',
        'subscription_type',
        'subscription_code',
        'plan_name',
        'plan_description',
        'subscription_category',
        'price',
        'currency',
        'billing_cycle',
        'status',
        'payment_status',
        'trial_days',
        'trial_ends_at',
        'starts_at',
        'expires_at',
        'last_payment_at',
        'next_payment_at',
        'auto_renew',
        'cancellation_reason',
        'cancelled_at',
        'suspended_at',
        'suspended_reason',
        'metadata',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'status' => SessionSubscriptionStatus::class,
        'price' => 'decimal:2',
        'auto_renew' => 'boolean',
        'trial_days' => 'integer',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'next_payment_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'suspended_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', SessionSubscriptionStatus::ACTIVE)
                    ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', SessionSubscriptionStatus::ACTIVE)
                    ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', SessionSubscriptionStatus::CANCELLED);
    }

    public function scopePaused($query)
    {
        return $query->where('status', SessionSubscriptionStatus::PAUSED);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('subscription_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('subscription_category', $category);
    }

    public function scopeInTrial($query)
    {
        return $query->where('status', 'trial')
                    ->where('trial_ends_at', '>', now());
    }

    public function scopeTrialExpired($query)
    {
        return $query->where('status', 'trial')
                    ->where('trial_ends_at', '<=', now());
    }

    // Accessors
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' ' . $this->currency;
    }

    public function getBillingCycleTextAttribute(): string
    {
        $cycles = [
            'daily' => 'يومي',
            'weekly' => 'أسبوعي',
            'monthly' => 'شهري',
            'quarterly' => 'ربع سنوي',
            'yearly' => 'سنوي',
            'lifetime' => 'مدى الحياة'
        ];

        return $cycles[$this->billing_cycle] ?? $this->billing_cycle;
    }

    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'trial' => 'فترة تجريبية',
            'active' => 'نشط',
            'expired' => 'منتهي الصلاحية',
            'cancelled' => 'ملغي',
            'suspended' => 'معلق',
            'pending' => 'في الانتظار'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusBadgeColorAttribute(): string
    {
        $colors = [
            'trial' => 'info',
            'active' => 'success',
            'expired' => 'danger',
            'cancelled' => 'secondary',
            'suspended' => 'warning',
            'pending' => 'primary'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->expires_at) {
            return 0;
        }

        $days = now()->diffInDays($this->expires_at, false);
        return max(0, $days);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === SessionSubscriptionStatus::ACTIVE &&
               $this->expires_at &&
               $this->expires_at->isFuture();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsInTrialAttribute(): bool
    {
        // Trial is now treated as ACTIVE with trial_ends_at set
        return $this->status === SessionSubscriptionStatus::ACTIVE &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return $this->days_remaining <= 7;
    }

    // Methods
    public function activate(): self
    {
        $this->update([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'starts_at' => now(),
            'payment_status' => 'paid'
        ]);

        return $this;
    }

    public function cancel(?string $reason = null): self
    {
        $this->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false
        ]);

        return $this;
    }

    public function pause(?string $reason = null): self
    {
        $this->update([
            'status' => SessionSubscriptionStatus::PAUSED,
            'suspended_at' => now(),
            'suspended_reason' => $reason
        ]);

        return $this;
    }

    public function resume(): self
    {
        $this->update([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'suspended_at' => null,
            'suspended_reason' => null
        ]);

        return $this;
    }

    public function renew(int $months = 1): self
    {
        $newExpiryDate = $this->expires_at && $this->expires_at->isFuture()
            ? $this->expires_at->addMonths($months)
            : now()->addMonths($months);

        $this->update([
            'status' => SessionSubscriptionStatus::ACTIVE,
            'expires_at' => $newExpiryDate,
            'last_payment_at' => now(),
            'next_payment_at' => $this->calculateNextPaymentDate()
        ]);

        return $this;
    }

    public function extend(int $days): self
    {
        $newExpiryDate = $this->expires_at && $this->expires_at->isFuture()
            ? $this->expires_at->addDays($days)
            : now()->addDays($days);

        $this->update([
            'expires_at' => $newExpiryDate
        ]);

        return $this;
    }

    public function startTrial(int $days = 7): self
    {
        $this->update([
            'status' => SessionSubscriptionStatus::ACTIVE, // Trials are now just active subscriptions with trial_ends_at
            'trial_days' => $days,
            'trial_ends_at' => now()->addDays($days),
            'starts_at' => now()
        ]);

        return $this;
    }

    public function endTrial(): self
    {
        // If trial ends without conversion, cancel the subscription
        if ($this->trial_ends_at && $this->trial_ends_at->isPast()) {
            $this->update([
                'status' => SessionSubscriptionStatus::CANCELLED,
                'trial_ends_at' => now()
            ]);
        }

        return $this;
    }

    public function updateMetadata(array $data): self
    {
        $currentMetadata = $this->metadata ?? [];
        $this->update([
            'metadata' => array_merge($currentMetadata, $data)
        ]);

        return $this;
    }

    public function canAccess(): bool
    {
        return $this->is_active || $this->is_in_trial;
    }

    private function calculateNextPaymentDate(): ?\Carbon\Carbon
    {
        if (!$this->auto_renew) {
            return null;
        }

        $nextPayment = now();

        switch ($this->billing_cycle) {
            case 'daily':
                return $nextPayment->addDay();
            case 'weekly':
                return $nextPayment->addWeek();
            case 'monthly':
                return $nextPayment->addMonth();
            case 'quarterly':
                return $nextPayment->addMonths(3);
            case 'yearly':
                return $nextPayment->addYear();
            default:
                return null;
        }
    }

    // Static methods
    public static function createSubscription(array $data): self
    {
        $subscription = self::create($data);
        
        // Auto-start trial if specified
        if (isset($data['trial_days']) && $data['trial_days'] > 0) {
            $subscription->startTrial($data['trial_days']);
        }

        return $subscription;
    }
}
