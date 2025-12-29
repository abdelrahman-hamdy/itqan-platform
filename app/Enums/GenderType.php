<?php

namespace App\Enums;

/**
 * Gender Type Enum
 *
 * Defines gender classifications for Quran circles.
 * Used to organize circles by gender for appropriate learning environments.
 *
 * Islamic teaching often separates male and female students for
 * modesty and focused learning, though mixed groups may be allowed
 * for children or family settings.
 *
 * @see \App\Models\QuranCircle
 */
enum GenderType: string
{
    case MALE = 'male';        // ذكور - Male students only
    case FEMALE = 'female';    // إناث - Female students only
    case MIXED = 'mixed';      // مختلط - Mixed gender (typically children)

    /**
     * Get the localized label for the gender type
     */
    public function label(): string
    {
        return __('enums.gender_type.' . $this->value);
    }

    /**
     * Get the icon for the gender type
     */
    public function icon(): string
    {
        return match ($this) {
            self::MALE => 'ri-men-line',
            self::FEMALE => 'ri-women-line',
            self::MIXED => 'ri-group-line',
        };
    }

    /**
     * Get the Filament color class for the gender type
     */
    public function color(): string
    {
        return match ($this) {
            self::MALE => 'primary',
            self::FEMALE => 'pink',
            self::MIXED => 'gray',
        };
    }

    /**
     * Get the hex color for display
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::MALE => '#3b82f6',     // blue-500
            self::FEMALE => '#ec4899',   // pink-500
            self::MIXED => '#6b7280',    // gray-500
        };
    }

    /**
     * Check if this gender type can accept students of given gender
     */
    public function accepts(string $studentGender): bool
    {
        if ($this === self::MIXED) {
            return true;
        }

        return $this->value === strtolower($studentGender);
    }

    /**
     * Get recommended teacher gender for this circle type
     */
    public function recommendedTeacherGender(): ?string
    {
        return match ($this) {
            self::MALE => 'male',
            self::FEMALE => 'female',
            self::MIXED => null,    // Either gender acceptable
        };
    }

    /**
     * Check if mixed gender is appropriate for age group
     */
    public static function mixedAppropriateForAge(int $age): bool
    {
        // Mixed groups typically only appropriate for children under 10
        return $age < 10;
    }

    /**
     * Get all gender type values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get gender type options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($type) => $type->label(), self::cases())
        );
    }
}
