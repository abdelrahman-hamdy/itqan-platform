<?php

namespace App\Services;

use App\Models\AcademySettings;
use App\Models\BaseSession;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;

/**
 * Session Settings Service
 *
 * Provides centralized access to session timing settings from AcademySettings.
 * All timing values are configurable per-academy with sensible defaults.
 */
class SessionSettingsService
{
    /**
     * Cache for academy settings to avoid repeated database queries
     */
    protected array $settingsCache = [];

    /**
     * Get academy settings for a session
     */
    public function getAcademySettings(BaseSession $session): ?AcademySettings
    {
        $academyId = $session->academy_id;
        if (!$academyId) {
            return null;
        }

        // Check cache first
        if (isset($this->settingsCache[$academyId])) {
            return $this->settingsCache[$academyId];
        }

        $settings = AcademySettings::where('academy_id', $academyId)->first();
        $this->settingsCache[$academyId] = $settings;

        return $settings;
    }

    /**
     * Get preparation minutes from academy settings
     * This is how early before session start we transition to READY
     */
    public function getPreparationMinutes(BaseSession $session): int
    {
        $settings = $this->getAcademySettings($session);
        return $settings?->default_preparation_minutes ?? 10;
    }

    /**
     * Get grace period minutes from academy settings
     * This is how long we wait before marking student as ABSENT
     */
    public function getGracePeriodMinutes(BaseSession $session): int
    {
        $settings = $this->getAcademySettings($session);
        return $settings?->default_late_tolerance_minutes ?? 15;
    }

    /**
     * Get buffer minutes from academy settings
     * This is extra time after scheduled end before auto-completing
     */
    public function getBufferMinutes(BaseSession $session): int
    {
        $settings = $this->getAcademySettings($session);
        return $settings?->default_buffer_minutes ?? 5;
    }

    /**
     * Get early join minutes from academy settings
     * This is how early participants can join before scheduled start
     */
    public function getEarlyJoinMinutes(BaseSession $session): int
    {
        $settings = $this->getAcademySettings($session);
        return $settings?->default_early_join_minutes ?? 15;
    }

    /**
     * Get max future hours for ongoing transition
     * Prevents ONGOING status for sessions too far in the future
     */
    public function getMaxFutureHoursOngoing(): int
    {
        return 2;
    }

    /**
     * Get max future hours for ready transition
     * Limits how far ahead we process sessions
     */
    public function getMaxFutureHours(): int
    {
        return 24;
    }

    /**
     * Get session type identifier for logging/events
     */
    public function getSessionType(BaseSession $session): string
    {
        if ($session instanceof QuranSession) {
            return 'quran';
        }
        if ($session instanceof AcademicSession) {
            return 'academic';
        }
        if ($session instanceof InteractiveCourseSession) {
            return 'interactive';
        }
        return 'unknown';
    }

    /**
     * Check if session is an individual (1-on-1) session
     */
    public function isIndividualSession(BaseSession $session): bool
    {
        if ($session instanceof QuranSession || $session instanceof AcademicSession) {
            return $session->session_type === 'individual';
        }
        // Interactive course sessions are always group
        return false;
    }

    /**
     * Get session title for notifications
     */
    public function getSessionTitle(BaseSession $session): string
    {
        if ($session->title) {
            return $session->title;
        }

        $type = $this->getSessionType($session);
        return match($type) {
            'quran' => 'جلسة قرآنية',
            'academic' => 'جلسة أكاديمية',
            'interactive' => $session instanceof InteractiveCourseSession
                ? ($session->course?->title ?? 'جلسة تفاعلية')
                : 'جلسة تفاعلية',
            default => 'جلسة',
        };
    }

    /**
     * Clear settings cache (useful for testing)
     */
    public function clearCache(): void
    {
        $this->settingsCache = [];
    }
}
