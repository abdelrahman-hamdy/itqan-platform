<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class GoogleToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'academy_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'token_type',
        'scope',
        'token_status',
        'refresh_count',
        'last_refreshed_at',
        'last_used_at',
        'last_error',
        'last_error_at',
        'consecutive_errors',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_refreshed_at' => 'datetime',
        'last_used_at' => 'datetime',
        'last_error_at' => 'datetime',
        'scope' => 'array',
        'refresh_count' => 'integer',
        'consecutive_errors' => 'integer',
    ];

    // Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REVOKED = 'revoked';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('token_status', self::STATUS_ACTIVE);
    }

    public function scopeExpired($query)
    {
        return $query->where('token_status', self::STATUS_EXPIRED)
                    ->orWhere('expires_at', '<', now());
    }

    public function scopeNeedsRefresh($query)
    {
        return $query->where('token_status', self::STATUS_ACTIVE)
                    ->where('expires_at', '<', now()->addMinutes(10)); // Refresh 10 minutes before expiry
    }

    public function scopeHealthy($query)
    {
        return $query->active()
                    ->where('consecutive_errors', '<', 3);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsHealthyAttribute(): bool
    {
        return $this->token_status === self::STATUS_ACTIVE && 
               $this->consecutive_errors < 3;
    }

    public function getNeedsRefreshAttribute(): bool
    {
        return $this->expires_at && 
               $this->expires_at->isBefore(now()->addMinutes(10));
    }

    public function getTimeUntilExpiryAttribute(): ?string
    {
        if (!$this->expires_at) {
            return null;
        }

        $diff = $this->expires_at->diffForHumans();
        return $this->expires_at->isPast() ? "منتهية الصلاحية منذ {$diff}" : "تنتهي خلال {$diff}";
    }

    // Methods
    public function markAsExpired(): self
    {
        $this->update([
            'token_status' => self::STATUS_EXPIRED,
            'last_error' => 'Token expired',
            'last_error_at' => now(),
        ]);

        return $this;
    }

    public function markAsRevoked(): self
    {
        $this->update([
            'token_status' => self::STATUS_REVOKED,
            'last_error' => 'Token revoked',
            'last_error_at' => now(),
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

        // Mark as expired after 3 consecutive errors
        if ($this->consecutive_errors >= 3) {
            $this->markAsExpired();
        }

        return $this;
    }

    public function clearErrors(): self
    {
        $this->update([
            'consecutive_errors' => 0,
            'last_error' => null,
            'last_error_at' => null,
        ]);

        return $this;
    }

    public function recordUsage(): self
    {
        $this->update(['last_used_at' => now()]);
        return $this;
    }

    public function recordRefresh(array $tokenData): self
    {
        $updateData = [
            'refresh_count' => $this->refresh_count + 1,
            'last_refreshed_at' => now(),
            'consecutive_errors' => 0,
            'last_error' => null,
            'last_error_at' => null,
        ];

        // Update token data if provided
        if (isset($tokenData['access_token'])) {
            $updateData['access_token'] = encrypt($tokenData['access_token']);
        }

        if (isset($tokenData['expires_in'])) {
            $updateData['expires_at'] = now()->addSeconds($tokenData['expires_in']);
        }

        if (isset($tokenData['refresh_token'])) {
            $updateData['refresh_token'] = encrypt($tokenData['refresh_token']);
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
            'token_type' => $this->token_type,
            'scope' => is_array($this->scope) ? implode(' ', $this->scope) : $this->scope,
        ];
    }
}