<?php

namespace App\Constants;

/**
 * Centralized session key constants for consistent session management.
 * Avoids magic strings and enables IDE autocomplete.
 */
final class SessionKeys
{
    // ============================================
    // Parent/Child Selection Keys
    // ============================================

    /**
     * Currently active child ID for parent views.
     * Used in parent dashboard and related features.
     */
    public const ACTIVE_CHILD_ID = 'active_child_id';

    /**
     * Parent-selected child ID for filtering.
     * Can be 'all' or a specific child ID.
     */
    public const PARENT_SELECTED_CHILD_ID = 'parent_selected_child_id';

    /**
     * Verified students during parent registration flow.
     */
    public const VERIFIED_STUDENTS = 'verified_students';

    // ============================================
    // Academy/Tenant Keys
    // ============================================

    /**
     * Current academy context.
     */
    public const CURRENT_ACADEMY = 'current_academy';

    /**
     * Current academy ID.
     */
    public const CURRENT_ACADEMY_ID = 'current_academy_id';

    // ============================================
    // Authentication Keys
    // ============================================

    /**
     * Password confirmation timestamp.
     */
    public const PASSWORD_CONFIRMED_AT = 'auth.password_confirmed_at';

    /**
     * Teacher type during registration/login flow.
     */
    public const TEACHER_TYPE = 'teacher_type';

    /**
     * Intended URL after login.
     */
    public const URL_INTENDED = 'url.intended';

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Get the parent-selected child ID from session.
     * Returns 'all' if not set.
     */
    public static function getSelectedChildId(): string
    {
        return session(self::PARENT_SELECTED_CHILD_ID, 'all');
    }

    /**
     * Set the parent-selected child ID in session.
     */
    public static function setSelectedChildId(string|int $childId): void
    {
        session([self::PARENT_SELECTED_CHILD_ID => $childId]);
    }

    /**
     * Get the active child ID from session.
     */
    public static function getActiveChildId(): ?int
    {
        return session(self::ACTIVE_CHILD_ID);
    }

    /**
     * Set the active child ID in session.
     */
    public static function setActiveChildId(int $childId): void
    {
        session([self::ACTIVE_CHILD_ID => $childId]);
    }

    /**
     * Clear child selection from session.
     */
    public static function clearChildSelection(): void
    {
        session()->forget([
            self::ACTIVE_CHILD_ID,
            self::PARENT_SELECTED_CHILD_ID,
        ]);
    }
}
