<?php

namespace App\Filament\Concerns;

/**
 * Trait for adding navigation badges that show pending approval counts.
 *
 * This trait provides consistent navigation badge behavior across Filament resources
 * that need to display pending approval counts (teachers, subscriptions, etc.).
 *
 * Usage:
 * 1. Add `use HasPendingBadge;` to your resource class
 * 2. Optionally override getPendingStatusColumn() and getPendingStatusValue() if your
 *    model uses different column/value than 'approval_status'/'pending'
 */
trait HasPendingBadge
{
    /**
     * Get the column name used for status filtering.
     * Override this if your model uses a different column name.
     */
    protected static function getPendingStatusColumn(): string
    {
        return 'approval_status';
    }

    /**
     * Get the value that represents pending status.
     * Override this if your model uses a different value.
     */
    protected static function getPendingStatusValue(): string
    {
        return 'pending';
    }

    /**
     * Get the count of pending items.
     */
    protected static function getPendingCount(): int
    {
        return static::getModel()::where(
            static::getPendingStatusColumn(),
            static::getPendingStatusValue()
        )->count();
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
