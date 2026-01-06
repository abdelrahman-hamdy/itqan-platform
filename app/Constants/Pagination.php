<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Pagination constants for consistent page sizes across the application.
 */
final class Pagination
{
    // ========================================
    // DEFAULT PAGE SIZES
    // ========================================

    public const DEFAULT = 15;

    public const SMALL = 10;

    public const MEDIUM = 20;

    public const LARGE = 50;

    public const EXTRA_LARGE = 100;

    // ========================================
    // CONTEXT-SPECIFIC PAGE SIZES
    // ========================================

    public const FILAMENT_TABLE = 25;

    public const API_DEFAULT = 15;

    public const API_MAX = 100;

    public const NOTIFICATIONS = 10;

    public const SESSIONS = 12;

    public const STUDENTS = 20;

    public const TEACHERS = 15;

    public const PAYMENTS = 20;

    public const REPORTS = 15;

    public const SEARCH_RESULTS = 10;

    public const CALENDAR_EVENTS = 50;

    public const CHAT_MESSAGES = 30;

    // ========================================
    // INFINITE SCROLL
    // ========================================

    public const INFINITE_SCROLL_CHUNK = 20;

    public const LOAD_MORE_CHUNK = 10;

    // ========================================
    // VALIDATION
    // ========================================

    public const MIN_PER_PAGE = 1;

    public const MAX_PER_PAGE = 100;

    /**
     * Get validated per-page value within allowed bounds.
     */
    public static function validated(int $perPage, int $default = self::DEFAULT): int
    {
        if ($perPage < self::MIN_PER_PAGE) {
            return $default;
        }

        if ($perPage > self::MAX_PER_PAGE) {
            return self::MAX_PER_PAGE;
        }

        return $perPage;
    }
}
