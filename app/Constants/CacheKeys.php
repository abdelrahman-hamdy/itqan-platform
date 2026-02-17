<?php

namespace App\Constants;

use Cache;

/**
 * Centralized cache key constants for consistent caching across the application.
 * Provides helper methods for generating dynamic cache keys.
 */
final class CacheKeys
{
    // ============================================
    // Cache TTL Constants (in seconds)
    // ============================================

    /** Default cache TTL: 1 hour */
    public const TTL_DEFAULT = 3600;

    /** Short cache TTL: 5 minutes */
    public const TTL_SHORT = 300;

    /** Medium cache TTL: 30 minutes */
    public const TTL_MEDIUM = 1800;

    /** Long cache TTL: 24 hours */
    public const TTL_LONG = 86400;

    // ============================================
    // Platform Settings
    // ============================================

    public const PLATFORM_SETTINGS = 'platform_settings';

    // ============================================
    // Attendance Keys
    // ============================================

    /** Attendance status key prefix: {session_id}_{user_id} */
    private const ATTENDANCE_STATUS_PREFIX = 'attendance_status';

    /** Meeting attendance key prefix: {session_id}_{user_id} */
    private const MEETING_ATTENDANCE_PREFIX = 'meeting_attendance';

    // ============================================
    // Calendar Keys
    // ============================================

    /** User calendar key prefix */
    private const USER_CALENDAR_PREFIX = 'user_calendar';

    // ============================================
    // Helper Methods for Dynamic Keys
    // ============================================

    /**
     * Generate attendance status cache key.
     */
    public static function attendanceStatus(int|string $sessionId, int|string $userId): string
    {
        return self::ATTENDANCE_STATUS_PREFIX."_{$sessionId}_{$userId}";
    }

    /**
     * Generate meeting attendance cache key.
     */
    public static function meetingAttendance(int|string $sessionId, int|string $userId): string
    {
        return self::MEETING_ATTENDANCE_PREFIX."_{$sessionId}_{$userId}";
    }

    /**
     * Generate user calendar cache key.
     */
    public static function userCalendar(int|string $userId, string $startDate, string $endDate): string
    {
        return self::USER_CALENDAR_PREFIX."_{$userId}_{$startDate}_{$endDate}";
    }

    /**
     * Clear all attendance-related cache for a session/user combination.
     */
    public static function forgetAttendance(int|string $sessionId, int|string $userId): void
    {
        Cache::forget(self::attendanceStatus($sessionId, $userId));
        Cache::forget(self::meetingAttendance($sessionId, $userId));
    }
}
