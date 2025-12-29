<?php

namespace App\Enums;

/**
 * Session Request Status Enum
 *
 * Tracks the lifecycle of session scheduling requests.
 *
 * States:
 * - PENDING: Request submitted, awaiting teacher response
 * - AGREED: Teacher agreed to the request
 * - PAID: Payment received for the session
 * - SCHEDULED: Session has been scheduled
 * - EXPIRED: Request expired without response
 * - CANCELLED: Request was cancelled
 *
 * @see \App\Models\SessionRequest
 */
enum SessionRequestStatus: string
{
    case PENDING = 'pending';
    case AGREED = 'agreed';
    case PAID = 'paid';
    case SCHEDULED = 'scheduled';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.session_request_status.' . $this->value);
    }

    /**
     * Get badge color for Filament
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::AGREED => 'info',
            self::PAID => 'success',
            self::SCHEDULED => 'primary',
            self::EXPIRED => 'gray',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * Get icon for display
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::AGREED => 'heroicon-o-hand-thumb-up',
            self::PAID => 'heroicon-o-banknotes',
            self::SCHEDULED => 'heroicon-o-calendar',
            self::EXPIRED => 'heroicon-o-clock',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }

    /**
     * Check if request is active
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::AGREED, self::PAID, self::SCHEDULED]);
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
