<?php

namespace App\Enums;

/**
 * Teaching Language Enum
 *
 * Defines the main languages available for teachers to use in their sessions.
 * Limited to 4 primary languages commonly used in academic education.
 *
 * @see \App\Models\AcademicTeacherProfile
 * @see \App\Models\QuranTeacherProfile
 */
enum TeachingLanguage: string
{
    case ARABIC = 'arabic';
    case ENGLISH = 'english';
    case FRENCH = 'french';
    case GERMAN = 'german';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.teaching_language.'.$this->value);
    }

    /**
     * Get array for form selects/checkboxes
     */
    public static function toArray(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->value, self::cases()),
            array_map(fn ($case) => $case->label(), self::cases())
        );
    }

    /**
     * Get default languages
     */
    public static function defaults(): array
    {
        return [
            self::ARABIC->value,
            self::ENGLISH->value,
        ];
    }

    /**
     * Check if a value is valid
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'));
    }

    /**
     * Get cases from array of values
     */
    public static function fromValues(array $values): array
    {
        return array_filter(
            array_map(
                fn ($value) => self::tryFrom($value),
                $values
            )
        );
    }
}
