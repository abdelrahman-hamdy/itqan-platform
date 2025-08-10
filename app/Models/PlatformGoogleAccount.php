<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PlatformGoogleAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'account_name',
        'google_email',
        'google_id',
        'account_type',
        'access_token',
        'refresh_token',
        'expires_at',
        'scope',
        'sessions_created',
        'last_used_at',
        'is_active',
        'daily_usage',
        'usage_reset_date',
        'daily_limit',
        'last_error',
        'last_error_at',
        'consecutive_errors',
        'meeting_defaults',
        'auto_record',
        'default_duration',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'last_error_at' => 'datetime',
        'usage_reset_date' => 'date',
        'scope' => 'array',
        'meeting_defaults' => 'array',
        'is_active' => 'boolean',
        'auto_record' => 'boolean',
        'sessions_created' => 'integer',
        'daily_usage' => 'integer',
        'daily_limit' => 'integer',
        'consecutive_errors' => 'integer',
        'default_duration' => 'integer',
    ];

    // Constants
    const TYPE_FALLBACK = 'fallback';
    const TYPE_PRIMARY = 'primary';
    const TYPE_BACKUP = 'backup';

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFallback($query)
    {
        return $query->where('account_type', self::TYPE_FALLBACK);
    }

    public function scopeHealthy($query)
    {
        return $query->active()
                    ->where('consecutive_errors', '<', 3);
    }

    public function scopeAvailable($query)
    {
        return $query->healthy()
                    ->whereRaw('daily_usage < daily_limit');
    }

    public function scopeForAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsHealthyAttribute(): bool
    {
        return $this->is_active && $this->consecutive_errors < 3;
    }

    public function getUsagePercentageAttribute(): float
    {
        return $this->daily_limit > 0 ? 
            round(($this->daily_usage / $this->daily_limit) * 100, 2) : 0;
    }

    public function getRemainingUsageAttribute(): int
    {
        return max(0, $this->daily_limit - $this->daily_usage);
    }

    public function getStatusTextAttribute(): string
    {
        if (!$this->is_active) {
            return 'غير نشط';
        }

        if ($this->consecutive_errors >= 3) {
            return 'خطأ متكرر';
        }

        if ($this->is_expired) {
            return 'منتهي الصلاحية';
        }

        if ($this->daily_usage >= $this->daily_limit) {
            return 'حد الاستخدام اليومي مكتمل';
        }

        return 'نشط';
    }

    public function getAccountTypeTextAttribute(): string
    {
        return match($this->account_type) {
            self::TYPE_FALLBACK => 'احتياطي',
            self::TYPE_PRIMARY => 'أساسي',
            self::TYPE_BACKUP => 'نسخ احتياطي',
            default => $this->account_type
        };
    }

    // Methods
    public function canCreateMeeting(): bool
    {
        return $this->is_active && 
               $this->consecutive_errors < 3 && 
               $this->daily_usage < $this->daily_limit &&
               !$this->is_expired;
    }

    public function recordUsage(): self
    {
        // Reset daily usage if needed
        if ($this->usage_reset_date->isYesterday()) {
            $this->resetDailyUsage();
        }

        $this->increment('sessions_created');
        $this->increment('daily_usage');
        $this->update(['last_used_at' => now()]);

        return $this;
    }

    public function resetDailyUsage(): self
    {
        $this->update([
            'daily_usage' => 0,
            'usage_reset_date' => today(),
        ]);

        return $this;
    }

    public function recordError(string $error): self
    {
        $this->update([
            'last_error' => $error,
            'last_error_at' => now(),
            'consecutive_errors' => $this->consecutive_errors + 1,
        ]);

        // Deactivate after 3 consecutive errors
        if ($this->consecutive_errors >= 3) {
            $this->update(['is_active' => false]);
        }

        return $this;
    }

    public function clearErrors(): self
    {
        $this->update([
            'consecutive_errors' => 0,
            'last_error' => null,
            'last_error_at' => null,
            'is_active' => true,
        ]);

        return $this;
    }

    public function updateToken(array $tokenData): self
    {
        $updateData = [
            'consecutive_errors' => 0,
            'last_error' => null,
            'last_error_at' => null,
        ];

        if (isset($tokenData['access_token'])) {
            $updateData['access_token'] = encrypt($tokenData['access_token']);
        }

        if (isset($tokenData['refresh_token'])) {
            $updateData['refresh_token'] = encrypt($tokenData['refresh_token']);
        }

        if (isset($tokenData['expires_in'])) {
            $updateData['expires_at'] = now()->addSeconds($tokenData['expires_in']);
        }

        if (isset($tokenData['scope'])) {
            $updateData['scope'] = is_string($tokenData['scope']) ? 
                explode(' ', $tokenData['scope']) : $tokenData['scope'];
        }

        $this->update($updateData);

        return $this;
    }

    /**
     * Get decrypted access token
     */
    public function getDecryptedAccessToken(): ?string
    {
        return $this->access_token ? decrypt($this->access_token) : null;
    }

    /**
     * Get decrypted refresh token
     */
    public function getDecryptedRefreshToken(): ?string
    {
        return $this->refresh_token ? decrypt($this->refresh_token) : null;
    }

    /**
     * Get token array for Google Client
     */
    public function getTokenArray(): array
    {
        return [
            'access_token' => $this->getDecryptedAccessToken(),
            'refresh_token' => $this->getDecryptedRefreshToken(),
            'expires_in' => $this->expires_at ? $this->expires_at->diffInSeconds(now()) : 3600,
            'token_type' => 'Bearer',
            'scope' => is_array($this->scope) ? implode(' ', $this->scope) : $this->scope,
        ];
    }

    /**
     * Check if account needs daily reset
     */
    public function checkDailyReset(): void
    {
        if ($this->usage_reset_date->isYesterday()) {
            $this->resetDailyUsage();
        }
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats(): array
    {
        return [
            'daily_usage' => $this->daily_usage,
            'daily_limit' => $this->daily_limit,
            'remaining' => $this->remaining_usage,
            'percentage' => $this->usage_percentage,
            'total_sessions' => $this->sessions_created,
            'last_used' => $this->last_used_at?->diffForHumans(),
            'reset_date' => $this->usage_reset_date->toDateString(),
        ];
    }

    /**
     * Check if token needs refresh
     */
    public function needsTokenRefresh(): bool
    {
        return $this->expires_at && 
               $this->expires_at->isBefore(now()->addMinutes(10));
    }
}