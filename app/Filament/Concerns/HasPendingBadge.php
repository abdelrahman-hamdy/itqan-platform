<?php

namespace App\Filament\Concerns;

/**
 * Trait for adding navigation badges that show pending activation counts.
 *
 * This trait provides consistent navigation badge behavior across Filament resources
 * that need to display counts of users pending activation (teachers, etc.).
 *
 * Usage:
 * 1. Add `use HasPendingBadge;` to your resource class
 * 2. The model must have a 'user' relationship to check User.active_status
 */
trait HasPendingBadge
{
    /**
     * Get the count of pending (inactive) items.
     * Counts records whose associated user has active_status = false.
     */
    protected static function getPendingCount(): int
    {
        return static::getModel()::whereHas('user', function ($query) {
            $query->where('active_status', false);
        })->count();
    }

    /**
     * Get the navigation badge showing pending count.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getPendingCount();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the navigation badge color (warning when there are pending items).
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getPendingCount() > 0 ? 'warning' : null;
    }

    /**
     * Get the navigation badge tooltip.
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('filament.tabs.pending_approval');
    }
}
