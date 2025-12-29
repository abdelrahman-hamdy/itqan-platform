<?php

namespace App\Enums;

/**
 * Trial Request Status Enum
 *
 * Tracks the lifecycle of free trial session requests.
 *
 * States:
 * - PENDING: Request submitted, awaiting review
 * - APPROVED: Request approved, can be scheduled
 * - REJECTED: Request was rejected
 * - SCHEDULED: Trial session has been scheduled
 * - COMPLETED: Trial session completed
 * - CANCELLED: Trial was cancelled
 * - NO_SHOW: Student didn't attend the trial
 *
 * @see \App\Models\TrialRequest
 */
enum TrialRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.trial_request_status.' . $this->value);
    }

    /**
     * Get badge color for Filament
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'info',
            self::REJECTED => 'danger',
            self::SCHEDULED => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'gray',
            self::NO_SHOW => 'danger',
        };
    }

    /**
     * Get icon for display
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::APPROVED => 'heroicon-o-check',
            self::REJECTED => 'heroicon-o-x-circle',
            self::SCHEDULED => 'heroicon-o-calendar',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::CANCELLED => 'heroicon-o-x-mark',
            self::NO_SHOW => 'heroicon-o-user-minus',
        };
    }

    /**
     * Check if the request is active (can still result in a session)
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::APPROVED, self::SCHEDULED]);
    }

    /**
     * Check if the request is in terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::REJECTED, self::COMPLETED, self::CANCELLED, self::NO_SHOW]);
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
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
