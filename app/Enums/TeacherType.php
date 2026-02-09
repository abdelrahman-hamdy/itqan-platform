<?php

namespace App\Enums;

/**
 * Teacher Type Enum
 *
 * Defines the two types of teachers in the system.
 * Used for routing, earnings calculations, and session management.
 */
enum TeacherType: string
{
    case QURAN = 'quran';
    case ACADEMIC = 'academic';

    /**
     * Get the localized label for the teacher type.
     */
    public function label(): string
    {
        return match ($this) {
            self::QURAN => __('enums.teacher_type.quran'),
            self::ACADEMIC => __('enums.teacher_type.academic'),
        };
    }

    /**
     * Get the corresponding UserType for this teacher type.
     */
    public function toUserType(): UserType
    {
        return match ($this) {
            self::QURAN => UserType::QURAN_TEACHER,
            self::ACADEMIC => UserType::ACADEMIC_TEACHER,
        };
    }
}
