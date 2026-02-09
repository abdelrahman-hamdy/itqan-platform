<?php

namespace App\Constants;

/**
 * Centralized color scheme for the Itqan platform.
 *
 * All hex colors used in charts, calendars, widgets, and UI elements
 * should reference these constants for consistency.
 *
 * Colors follow Tailwind CSS naming conventions.
 */
class ColorScheme
{
    // ========================================
    // SESSION TYPE COLORS
    // ========================================

    /** Quran individual sessions */
    const SESSION_QURAN_INDIVIDUAL = '#6366f1'; // indigo-500

    /** Quran group/circle sessions */
    const SESSION_QURAN_GROUP = '#22c55e'; // green-500

    /** Quran trial sessions */
    const SESSION_TRIAL = '#eab308'; // yellow-500

    /** Academic individual/private lessons */
    const SESSION_ACADEMIC = '#3B82F6'; // blue-500

    /** Interactive course sessions */
    const SESSION_INTERACTIVE_COURSE = '#8B5CF6'; // violet-500

    // ========================================
    // USER TYPE COLORS
    // ========================================

    /** Students */
    const USER_STUDENT = '#10B981'; // emerald-500

    /** Quran teachers */
    const USER_QURAN_TEACHER = '#3B82F6'; // blue-500

    /** Academic teachers */
    const USER_ACADEMIC_TEACHER = '#8B5CF6'; // violet-500

    /** Parents */
    const USER_PARENT = '#F59E0B'; // amber-500

    // ========================================
    // CHART COLORS (Grouped for datasets)
    // ========================================

    /** Primary chart color (blue) */
    const CHART_PRIMARY = '#3B82F6'; // blue-500

    /** Secondary chart color (emerald) */
    const CHART_SECONDARY = '#10B981'; // emerald-500

    /** Tertiary chart color (violet) */
    const CHART_TERTIARY = '#8B5CF6'; // violet-500

    /** Quaternary chart color (amber) */
    const CHART_QUATERNARY = '#F59E0B'; // amber-500

    /** Danger/error chart color (red) */
    const CHART_DANGER = '#EF4444'; // red-500

    // ========================================
    // UI COLORS
    // ========================================

    /** Default/inactive items */
    const DEFAULT = '#6B7280'; // gray-500

    /** Light text on colored backgrounds */
    const TEXT_ON_COLOR = '#ffffff'; // white

    /** Dark text */
    const TEXT_DARK = '#1f2937'; // gray-800

    /** Muted/secondary text */
    const TEXT_MUTED = '#9CA3AF'; // gray-400

    // ========================================
    // SESSION TYPE COLOR MAP
    // ========================================

    /**
     * Get the session type color map.
     * Keys match session type identifiers used throughout the app.
     */
    public static function sessionTypeColors(): array
    {
        return [
            'trial' => self::SESSION_TRIAL,
            'group' => self::SESSION_QURAN_GROUP,
            'quran_individual' => self::SESSION_QURAN_INDIVIDUAL,
            'academic_individual' => self::SESSION_ACADEMIC,
            'interactive_course' => self::SESSION_INTERACTIVE_COURSE,
        ];
    }

    /**
     * Get color for a session type, with fallback.
     */
    public static function forSessionType(string $type): string
    {
        return self::sessionTypeColors()[$type] ?? self::DEFAULT;
    }

    /**
     * Get the user type color map.
     */
    public static function userTypeColors(): array
    {
        return [
            'student' => self::USER_STUDENT,
            'quran_teacher' => self::USER_QURAN_TEACHER,
            'academic_teacher' => self::USER_ACADEMIC_TEACHER,
            'parent' => self::USER_PARENT,
        ];
    }

    /**
     * Get color for a user type, with fallback.
     */
    public static function forUserType(string $type): string
    {
        return self::userTypeColors()[$type] ?? self::DEFAULT;
    }
}
