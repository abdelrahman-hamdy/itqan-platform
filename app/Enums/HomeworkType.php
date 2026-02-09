<?php

namespace App\Enums;

/**
 * Homework Type Enum
 *
 * Defines the homework types in the system.
 * Used for filtering and routing homework operations.
 */
enum HomeworkType: string
{
    case ACADEMIC = 'academic';
    case INTERACTIVE = 'interactive';
    case QURAN = 'quran';

    /**
     * Get the localized label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACADEMIC => __('enums.homework_type.academic'),
            self::INTERACTIVE => __('enums.homework_type.interactive'),
            self::QURAN => __('enums.homework_type.quran'),
        };
    }

    /**
     * Check if this homework type supports student submissions.
     */
    public function supportsSubmissions(): bool
    {
        return $this !== self::QURAN;
    }
}
