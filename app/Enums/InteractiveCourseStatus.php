<?php

namespace App\Enums;

/**
 * Interactive Course Status Enum
 *
 * Tracks the lifecycle of interactive courses.
 *
 * States:
 * - PUBLISHED: Visible and accepting enrollments
 * - ACTIVE: Course currently running
 * - COMPLETED: Course finished
 *
 * Note: Visibility is controlled by the `is_published` boolean field.
 *
 * @see \App\Models\InteractiveCourse
 */
enum InteractiveCourseStatus: string
{
    case PUBLISHED = 'published';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.interactive_course_status.'.$this->value);
    }

    /**
     * Get English label for the status
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::PUBLISHED => 'Published',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
        };
    }

    /**
     * Get icon for the status
     */
    public function icon(): string
    {
        return match ($this) {
            self::PUBLISHED => 'ri-check-line',
            self::ACTIVE => 'ri-play-circle-line',
            self::COMPLETED => 'ri-checkbox-circle-line',
        };
    }

    /**
     * Get Filament color for the status
     */
    public function color(): string
    {
        return match ($this) {
            self::PUBLISHED => 'success',
            self::ACTIVE => 'info',
            self::COMPLETED => 'purple',
        };
    }

    /**
     * Get hex color for calendar display
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::PUBLISHED => '#22c55e',   // green-500
            self::ACTIVE => '#3B82F6',      // blue-500
            self::COMPLETED => '#8b5cf6',   // purple-500
        };
    }

    /**
     * Check if status allows enrollment
     */
    public function allowsEnrollment(): bool
    {
        return in_array($this, [self::PUBLISHED, self::ACTIVE]);
    }

    /**
     * Check if status is visible to public
     */
    public function isVisibleToPublic(): bool
    {
        return in_array($this, [self::PUBLISHED, self::ACTIVE]);
    }

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status options for forms (value => Arabic label)
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($status) => $status->label(), self::cases())
        );
    }

    /**
     * Get color options for badge columns (color => value)
     */
    public static function colorOptions(): array
    {
        $colors = [];
        foreach (self::cases() as $status) {
            $colors[$status->color()] = $status->value;
        }

        return $colors;
    }
}
