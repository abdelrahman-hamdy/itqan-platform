<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * SavedPaymentMethod Model
 *
 * Represents a tokenized payment method (card, wallet, etc.) that can be
 * used for recurring payments and quick checkout.
 *
 * @property int $id
 * @property int $academy_id
 * @property int $user_id
 * @property string $gateway
 * @property string $token
 * @property string|null $gateway_customer_id
 * @property string $type
 * @property string|null $brand
 * @property string|null $last_four
 * @property string|null $expiry_month
 * @property string|null $expiry_year
 * @property string|null $holder_name
 * @property string|null $display_name
 * @property bool $is_default
 * @property bool $is_active
 * @property array|null $metadata
 * @property array|null $billing_address
 * @property Carbon|null $last_used_at
 * @property Carbon|null $verified_at
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Academy $academy
 * @property-read User $user
 * @property-read Collection|Payment[] $payments
 */
class SavedPaymentMethod extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    /**
     * Payment method types
     */
    public const TYPE_CARD = 'card';

    public const TYPE_WALLET = 'wallet';

    public const TYPE_APPLE_PAY = 'apple_pay';

    public const TYPE_BANK_ACCOUNT = 'bank_account';

    /**
     * Card brands
     */
    public const BRAND_VISA = 'visa';

    public const BRAND_MASTERCARD = 'mastercard';

    public const BRAND_MEEZA = 'meeza';

    public const BRAND_AMEX = 'amex';

    protected $fillable = [
        'academy_id',
        'user_id',
        'gateway',
        'token',
        'gateway_customer_id',
        'type',
        'brand',
        'last_four',
        'expiry_month',
        'expiry_year',
        'holder_name',
        'display_name',
        'is_default',
        'is_active',
        'metadata',
        'billing_address',
        'last_used_at',
        'verified_at',
        'expires_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'billing_address' => 'array',
        'last_used_at' => 'datetime',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Sensitive attributes that should be hidden from arrays/JSON
     */
    protected $hidden = [
        'token',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'saved_payment_method_id');
    }

    // ========================================
    // Scopes
    // ========================================

    /**
     * Scope to only active payment methods
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only default payment methods
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to filter by gateway
     */
    public function scopeForGateway(Builder $query, string $gateway): Builder
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope to filter by payment method type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to only non-expired payment methods
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to filter by card brand
     */
    public function scopeOfBrand(Builder $query, string $brand): Builder
    {
        return $query->where('brand', $brand);
    }

    // ========================================
    // Methods
    // ========================================

    /**
     * Mark this payment method as the default for the user
     */
    public function markAsDefault(): bool
    {
        // First, unset any existing default for this user and gateway
        static::where('user_id', $this->user_id)
            ->where('gateway', $this->gateway)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        return $this->update(['is_default' => true]);
    }

    /**
     * Deactivate this payment method
     */
    public function deactivate(): bool
    {
        return $this->update([
            'is_active' => false,
            'is_default' => false,
        ]);
    }

    /**
     * Activate this payment method
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Check if the payment method is expired
     */
    public function isExpired(): bool
    {
        // Check token expiry
        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        // Check card expiry for card type
        if ($this->type === self::TYPE_CARD && $this->expiry_month && $this->expiry_year) {
            $expiryDate = Carbon::createFromFormat(
                'Y-m',
                $this->expiry_year.'-'.$this->expiry_month
            )->endOfMonth();

            return $expiryDate->isPast();
        }

        return false;
    }

    /**
     * Check if this payment method is usable
     */
    public function isUsable(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    /**
     * Get the masked card number for display
     */
    public function getMaskedNumber(): string
    {
        if ($this->last_four) {
            return '**** **** **** '.$this->last_four;
        }

        return '****';
    }

    /**
     * Get a display label for this payment method
     */
    public function getDisplayLabel(): string
    {
        if ($this->display_name) {
            return $this->display_name;
        }

        $parts = [];

        if ($this->brand) {
            $parts[] = $this->getBrandDisplayName();
        }

        if ($this->last_four) {
            $parts[] = '****'.$this->last_four;
        }

        return implode(' ', $parts) ?: 'طريقة دفع محفوظة';
    }

    /**
     * Get the brand display name in Arabic
     */
    public function getBrandDisplayName(): string
    {
        return match ($this->brand) {
            self::BRAND_VISA => 'فيزا',
            self::BRAND_MASTERCARD => 'ماستركارد',
            self::BRAND_MEEZA => 'ميزة',
            self::BRAND_AMEX => 'أمريكان إكسبريس',
            default => $this->brand ?? '',
        };
    }

    /**
     * All supported payment method types with their Arabic labels.
     *
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_CARD         => 'بطاقة',
            self::TYPE_WALLET       => 'محفظة',
            self::TYPE_APPLE_PAY    => 'Apple Pay',
            self::TYPE_BANK_ACCOUNT => 'حساب بنكي',
        ];
    }

    /**
     * Get the type display name in Arabic
     */
    public function getTypeDisplayName(): string
    {
        return static::typeOptions()[$this->type] ?? $this->type;
    }

    /**
     * Get expiry date formatted for display
     */
    public function getExpiryDisplay(): ?string
    {
        if ($this->expiry_month && $this->expiry_year) {
            return sprintf('%s/%s', $this->expiry_month, substr($this->expiry_year, -2));
        }

        return null;
    }

    /**
     * Update the last used timestamp
     */
    public function touchLastUsed(): bool
    {
        return $this->update(['last_used_at' => now()]);
    }

    /**
     * Get the icon class for this payment method brand
     */
    public function getBrandIcon(): string
    {
        return match ($this->brand) {
            self::BRAND_VISA => 'ri-visa-line',
            self::BRAND_MASTERCARD => 'ri-mastercard-line',
            self::BRAND_MEEZA => 'ri-bank-card-line',
            self::BRAND_AMEX => 'ri-bank-card-line',
            default => match ($this->type) {
                self::TYPE_WALLET => 'ri-wallet-3-line',
                self::TYPE_APPLE_PAY => 'ri-apple-fill',
                self::TYPE_BANK_ACCOUNT => 'ri-bank-line',
                default => 'ri-bank-card-line',
            },
        };
    }
}
