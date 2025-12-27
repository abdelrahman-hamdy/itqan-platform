<?php

namespace App\Enums;

enum SessionRequestStatus: string
{
    case PENDING = 'pending';
    case AGREED = 'agreed';
    case PAID = 'paid';
    case SCHEDULED = 'scheduled';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('قيد الانتظار'),
            self::AGREED => __('تم الموافقة'),
            self::PAID => __('مدفوع'),
            self::SCHEDULED => __('مجدول'),
            self::EXPIRED => __('منتهي الصلاحية'),
            self::CANCELLED => __('ملغي'),
        };
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
}
