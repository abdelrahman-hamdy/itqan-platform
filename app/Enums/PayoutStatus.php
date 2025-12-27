<?php

namespace App\Enums;

enum PayoutStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case REJECTED = 'rejected';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('قيد الانتظار'),
            self::APPROVED => __('موافق عليها'),
            self::PAID => __('مدفوعة'),
            self::REJECTED => __('مرفوضة'),
        };
    }

    /**
     * Get badge color for Filament
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'info',
            self::PAID => 'success',
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
            self::APPROVED => 'heroicon-o-check',
            self::PAID => 'heroicon-o-banknotes',
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
}
