<?php

namespace App\Enums;

/**
 * Difficulty Level Enum
 *
 * Defines skill/content difficulty levels for courses and sessions.
 *
 * Levels:
 * - BEGINNER: For new learners
 * - INTERMEDIATE: For learners with basic knowledge
 * - ADVANCED: For experienced learners
 *
 * @see \App\Models\Course
 * @see \App\Models\QuranCircle
 */
enum DifficultyLevel: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.difficulty_level.'.$this->value);
    }

    /**
     * Get label in English
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::BEGINNER => 'Beginner',
            self::INTERMEDIATE => 'Intermediate',
            self::ADVANCED => 'Advanced',
        };
    }

    /**
     * Get all options for select fields (Arabic labels)
     */
    public static function options(): array
    {
        return [
            self::BEGINNER->value => self::BEGINNER->label(),
            self::INTERMEDIATE->value => self::INTERMEDIATE->label(),
            self::ADVANCED->value => self::ADVANCED->label(),
        ];
    }

    /**
     * Get all options for select fields (English labels)
     */
    public static function optionsEn(): array
    {
        return [
            self::BEGINNER->value => self::BEGINNER->labelEn(),
            self::INTERMEDIATE->value => self::INTERMEDIATE->labelEn(),
            self::ADVANCED->value => self::ADVANCED->labelEn(),
        ];
    }

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
