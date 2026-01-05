<?php

namespace App\Models\Traits;

use App\Notifications\VerifyEmailNotification;

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
     * Mark the user's email as verified.
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Send the email verification notification.
     * Uses custom Arabic notification with academy branding.
     */
    public function sendEmailVerificationNotification(): void
    {
        if ($this->academy) {
            $this->notify(new VerifyEmailNotification($this->academy));
        }
    }

    /**
     * Get the email address that should be used for verification.
     */
    public function getEmailForVerification(): string
    {
        return $this->email;
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
