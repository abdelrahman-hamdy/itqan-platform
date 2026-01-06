<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Performance threshold constants for evaluations and metrics.
 */
final class PerformanceThresholds
{
    // ========================================
    // STUDENT PERFORMANCE RATINGS
    // ========================================

    public const EXCELLENT_MIN = 90;

    public const VERY_GOOD_MIN = 80;

    public const GOOD_MIN = 70;

    public const ACCEPTABLE_MIN = 60;

    public const POOR_MAX = 59;

    // ========================================
    // ATTENDANCE THRESHOLDS
    // ========================================

    public const ATTENDANCE_EXCELLENT = 95;

    public const ATTENDANCE_GOOD = 80;

    public const ATTENDANCE_WARNING = 70;

    public const ATTENDANCE_CRITICAL = 60;

    // ========================================
    // QURAN MEMORIZATION THRESHOLDS
    // ========================================

    public const MEMORIZATION_EXCELLENT = 95;

    public const MEMORIZATION_GOOD = 85;

    public const MEMORIZATION_ACCEPTABLE = 70;

    public const MEMORIZATION_NEEDS_IMPROVEMENT = 60;

    // ========================================
    // TAJWEED EVALUATION THRESHOLDS
    // ========================================

    public const TAJWEED_EXCELLENT = 90;

    public const TAJWEED_GOOD = 75;

    public const TAJWEED_ACCEPTABLE = 60;

    // ========================================
    // HOMEWORK THRESHOLDS
    // ========================================

    public const HOMEWORK_EXCELLENT = 90;

    public const HOMEWORK_GOOD = 75;

    public const HOMEWORK_PASSING = 60;

    public const HOMEWORK_FAILING = 59;

    // ========================================
    // SESSION METRICS
    // ========================================

    public const MIN_SESSION_DURATION_MINUTES = 15;

    public const MAX_SESSION_DURATION_MINUTES = 180;

    public const DEFAULT_SESSION_DURATION_MINUTES = 60;

    // ========================================
    // TIMING THRESHOLDS (in minutes)
    // ========================================

    public const LATE_THRESHOLD_MINUTES = 10;

    public const VERY_LATE_THRESHOLD_MINUTES = 20;

    public const EARLY_JOIN_ALLOWED_MINUTES = 15;

    public const SESSION_REMINDER_BEFORE_MINUTES = 30;

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get performance label based on score.
     */
    public static function getPerformanceLabel(float $score): string
    {
        return match (true) {
            $score >= self::EXCELLENT_MIN => 'ممتاز',
            $score >= self::VERY_GOOD_MIN => 'جيد جداً',
            $score >= self::GOOD_MIN => 'جيد',
            $score >= self::ACCEPTABLE_MIN => 'مقبول',
            default => 'يحتاج تحسين',
        };
    }

    /**
     * Get performance color class based on score.
     */
    public static function getPerformanceColor(float $score): string
    {
        return match (true) {
            $score >= self::EXCELLENT_MIN => 'success',
            $score >= self::VERY_GOOD_MIN => 'info',
            $score >= self::GOOD_MIN => 'primary',
            $score >= self::ACCEPTABLE_MIN => 'warning',
            default => 'danger',
        };
    }

    /**
     * Check if score indicates poor performance.
     */
    public static function isPoorPerformance(float $score): bool
    {
        return $score < self::ACCEPTABLE_MIN;
    }

    /**
     * Check if attendance is below warning threshold.
     */
    public static function isAttendanceWarning(float $percentage): bool
    {
        return $percentage < self::ATTENDANCE_WARNING;
    }
}
