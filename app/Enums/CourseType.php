<?php

namespace App\Enums;

/**
 * Course Type Enum
 *
 * Defines the types of courses available.
 */
enum CourseType: string
{
    case RECORDED = 'recorded';
    case INTERACTIVE = 'interactive';

    /**
     * Get the localized label.
     */
    public function label(): string
    {
        return match ($this) {
            self::RECORDED => __('enums.course_type.recorded'),
            self::INTERACTIVE => __('enums.course_type.interactive'),
        };
    }
}
