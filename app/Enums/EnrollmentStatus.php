<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case PENDING = 'pending';
    case ENROLLED = 'enrolled';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case DROPPED = 'dropped';
    case SUSPENDED = 'suspended';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('قيد الانتظار'),
            self::ENROLLED => __('مسجل'),
            self::ACTIVE => __('نشط'),
            self::COMPLETED => __('مكتمل'),
            self::DROPPED => __('منسحب'),
            self::SUSPENDED => __('موقوف'),
        };
    }

    /**
     * Get badge color for Filament
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ENROLLED => 'primary',
            self::ACTIVE => 'success',
            self::COMPLETED => 'success',
            self::DROPPED => 'gray',
            self::SUSPENDED => 'danger',
        };
    }

    /**
     * Get icon for display
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::ENROLLED => 'heroicon-o-academic-cap',
            self::ACTIVE => 'heroicon-o-check-circle',
            self::COMPLETED => 'heroicon-o-trophy',
            self::DROPPED => 'heroicon-o-arrow-right-start-on-rectangle',
            self::SUSPENDED => 'heroicon-o-pause-circle',
        };
    }

    /**
     * Check if enrollment is currently active
     */
    public function isActive(): bool
    {
        return in_array($this, [self::ENROLLED, self::ACTIVE]);
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
