<?php

namespace App\Enums;

/**
 * Trial Request Status Enum
 *
 * Tracks the lifecycle of free trial session requests.
 *
 * States:
 * - PENDING: Request submitted, awaiting scheduling
 * - SCHEDULED: Trial session has been scheduled
 * - COMPLETED: Trial session completed successfully
 * - CANCELLED: Trial was cancelled, rejected, or student didn't attend
 *
 * @see \App\Models\QuranTrialRequest
 */
enum TrialRequestStatus: string
{
    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.trial_request_status.'.$this->value);
    }

    /**
     * Get badge color for Filament
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::SCHEDULED => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'gray',
        };
    }

    /**
     * Get icon for display
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::SCHEDULED => 'heroicon-o-calendar',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::CANCELLED => 'heroicon-o-x-mark',
        };
    }

    /**
     * Check if the request is active (can still result in a session)
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::SCHEDULED]);
    }

    /**
     * Check if the request is in terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    /**
     * Check if the request can be scheduled
     */
    public function canSchedule(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Get all statuses as options for select inputs
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $status) => [$status->value => $status->label()]
        )->all();
    }

    /**
     * Get color options for Filament BadgeColumn
     */
    public static function colorOptions(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $status) => [$status->value => $status->color()]
        )->all();
    }

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
