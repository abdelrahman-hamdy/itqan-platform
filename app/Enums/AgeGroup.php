<?php

namespace App\Enums;

/**
 * Age Group Enum
 *
 * Defines age group classifications for Quran circles.
 * Used to group students by age for better learning experiences.
 *
 * Age groups help match students with appropriate teaching methods
 * and peer groups for collaborative learning.
 *
 * @see \App\Models\QuranCircle
 */
enum AgeGroup: string
{
    case CHILDREN = 'children';    // أطفال - Children (5-12 years)
    case YOUTH = 'youth';          // شباب - Youth/Teens (13-18 years)
    case ADULTS = 'adults';        // بالغين - Adults (19-60 years)
    case SENIORS = 'seniors';      // كبار السن - Seniors (60+ years)
    case MIXED = 'mixed';          // مختلط - Mixed age groups

    /**
     * Get the localized label for the age group
     */
    public function label(): string
    {
        return __('enums.age_group.'.$this->value);
    }

    /**
     * Get the icon for the age group
     */
    public function icon(): string
    {
        return match ($this) {
            self::CHILDREN => 'ri-bear-smile-line',
            self::YOUTH => 'ri-user-smile-line',
            self::ADULTS => 'ri-user-line',
            self::SENIORS => 'ri-user-star-line',
            self::MIXED => 'ri-team-line',
        };
    }

    /**
     * Get the Filament color class for the age group
     */
    public function color(): string
    {
        return match ($this) {
            self::CHILDREN => 'warning',
            self::YOUTH => 'primary',
            self::ADULTS => 'success',
            self::SENIORS => 'info',
            self::MIXED => 'gray',
        };
    }

    /**
     * Get typical age range for this group
     */
    public function ageRange(): array
    {
        return match ($this) {
            self::CHILDREN => [5, 12],
            self::YOUTH => [13, 18],
            self::ADULTS => [19, 60],
            self::SENIORS => [60, 100],
            self::MIXED => [5, 100],
        };
    }

    /**
     * Get recommended session duration in minutes
     */
    public function recommendedSessionDuration(): int
    {
        return match ($this) {
            self::CHILDREN => 30,    // Shorter attention span
            self::YOUTH => 45,       // Medium duration
            self::ADULTS => 60,      // Full hour sessions
            self::SENIORS => 45,     // Moderate duration
            self::MIXED => 45,       // Moderate for mixed groups
        };
    }

    /**
     * Get recommended maximum students per circle
     */
    public function maxStudents(): int
    {
        return match ($this) {
            self::CHILDREN => 8,     // Smaller groups for children
            self::YOUTH => 12,       // Medium groups for youth
            self::ADULTS => 15,      // Larger groups for adults
            self::SENIORS => 10,     // Moderate groups for seniors
            self::MIXED => 10,       // Moderate for mixed groups
        };
    }

    /**
     * Check if age falls within this group
     */
    public function includes(int $age): bool
    {
        [$min, $max] = $this->ageRange();

        return $age >= $min && $age <= $max;
    }

    /**
     * Get all age group values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get age group options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($group) => $group->label(), self::cases())
        );
    }

    /**
     * Get age group from age
     */
    public static function fromAge(int $age): self
    {
        return match (true) {
            $age >= 60 => self::SENIORS,
            $age >= 19 => self::ADULTS,
            $age >= 13 => self::YOUTH,
            default => self::CHILDREN,
        };
    }
}
