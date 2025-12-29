<?php

namespace App\Models\Traits;

trait HasNotificationPreferences
{
    /**
     * Check if user email is verified
     */
    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Check if user phone is verified
     */
    public function hasVerifiedPhone(): bool
    {
        return ! is_null($this->phone_verified_at);
    }

    /**
     * Check if user is active - Simplified
     */
    public function isActive(): bool
    {
        return $this->active_status;
    }

    /**
     * Scope to get active users - Simplified
     */
    public function scopeActive($query)
    {
        return $query->where('active_status', true);
    }

    /**
     * Scope to get users with completed profiles
     */
    public function scopeProfileCompleted($query)
    {
        return $query->whereNotNull('profile_completed_at');
    }

    /**
     * Scope to get users with verified email
     */
    public function scopeEmailVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }
}
