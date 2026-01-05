<?php

namespace App\Enums;

/**
 * Payout Status Enum
 *
 * Tracks the approval lifecycle of teacher earnings confirmation.
 *
 * States:
 * - PENDING: Earnings submitted for approval
 * - APPROVED: Earnings confirmed by admin
 * - REJECTED: Earnings rejected
 *
 * @see \App\Models\TeacherPayout
 * @see \App\Services\PayoutService
 */
enum PayoutStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.payout_status.' . $this->value);
    }

    /**
     * Get badge color for Filament
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    /**
     * Get icon for display
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::APPROVED => 'heroicon-o-check-circle',
            self::REJECTED => 'heroicon-o-x-circle',
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

    /**
     * Get color options for Filament badge columns
     */
    public static function colorOptions(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $status) => [$status->value => $status->color()]
        )->all();
    }
}
