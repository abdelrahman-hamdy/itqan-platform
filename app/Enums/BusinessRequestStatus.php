<?php

namespace App\Enums;

enum BusinessRequestStatus: string
{
    case PENDING = 'pending';
    case REVIEWED = 'reviewed';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case COMPLETED = 'completed';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('قيد الانتظار'),
            self::REVIEWED => __('تمت المراجعة'),
            self::APPROVED => __('موافق عليه'),
            self::REJECTED => __('مرفوض'),
            self::COMPLETED => __('مكتمل'),
        };
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
}
