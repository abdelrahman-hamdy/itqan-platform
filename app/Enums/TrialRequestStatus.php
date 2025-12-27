<?php

namespace App\Enums;

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
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('قيد الانتظار'),
            self::APPROVED => __('موافق عليها'),
            self::REJECTED => __('مرفوضة'),
            self::SCHEDULED => __('مجدولة'),
            self::COMPLETED => __('مكتملة'),
            self::CANCELLED => __('ملغاة'),
            self::NO_SHOW => __('لم يحضر'),
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
}
