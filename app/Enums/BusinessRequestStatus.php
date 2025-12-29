<?php

namespace App\Enums;

/**
 * Business Request Status Enum
 *
 * Tracks the lifecycle of business/partnership requests.
 * Used by academy partnership and business inquiries.
 *
 * States:
 * - PENDING: Request submitted, awaiting review
 * - REVIEWED: Request reviewed by staff
 * - APPROVED: Request approved
 * - REJECTED: Request rejected
 * - COMPLETED: Request fully processed
 *
 * @see \App\Models\BusinessRequest
 */
enum BusinessRequestStatus: string
{
    case PENDING = 'pending';
    case REVIEWED = 'reviewed';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case COMPLETED = 'completed';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.business_request_status.' . $this->value);
    }

    /**
     * Get badge color for Filament
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::REVIEWED => 'info',
            self::APPROVED => 'primary',
            self::REJECTED => 'danger',
            self::COMPLETED => 'success',
        };
    }

    /**
     * Get icon for display
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::REVIEWED => 'heroicon-o-eye',
            self::APPROVED => 'heroicon-o-check',
            self::REJECTED => 'heroicon-o-x-circle',
            self::COMPLETED => 'heroicon-o-check-circle',
        };
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
