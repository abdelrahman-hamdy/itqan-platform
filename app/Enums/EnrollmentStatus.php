<?php

namespace App\Enums;

/**
 * Enrollment Status Enum
 *
 * Tracks student enrollment state in courses and circles.
 *
 * States:
 * - PENDING: Enrollment request submitted
 * - ENROLLED: Successfully enrolled
 * - ACTIVE: Actively participating
 * - COMPLETED: Successfully completed
 * - DROPPED: Student withdrew
 * - SUSPENDED: Enrollment temporarily suspended
 *
 * @see \App\Models\InteractiveCourseEnrollment
 * @see \App\Models\QuranCircleStudent
 */
enum EnrollmentStatus: string
{
    case PENDING = 'pending';
    case ENROLLED = 'enrolled';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case DROPPED = 'dropped';
    case SUSPENDED = 'suspended';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.enrollment_status.' . $this->value);
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

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
