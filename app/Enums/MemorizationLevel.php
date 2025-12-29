<?php

namespace App\Enums;

/**
 * Memorization Level Enum
 *
 * Defines the Quran memorization proficiency levels for students.
 * Used to classify students based on their current memorization status.
 *
 * Levels progress from beginner (just starting) to complete (hafiz/hafiza).
 * Each level has different requirements and expectations.
 *
 * @see \App\Models\QuranCircle
 * @see \App\Models\QuranIndividualCircle
 * @see \App\Models\StudentProfile
 */
enum MemorizationLevel: string
{
    case BEGINNER = 'beginner';          // مبتدئ - Just starting (0-3 juz)
    case INTERMEDIATE = 'intermediate';  // متوسط - Moderate progress (3-15 juz)
    case ADVANCED = 'advanced';          // متقدم - Advanced student (15-29 juz)
    case COMPLETE = 'complete';          // حافظ - Hafiz/Hafiza (complete Quran)

    /**
     * Get the localized label for the memorization level
     */
    public function label(): string
    {
        return __('enums.memorization_level.' . $this->value);
    }

    /**
     * Get the icon for the memorization level
     */
    public function icon(): string
    {
        return match ($this) {
            self::BEGINNER => 'ri-seedling-line',
            self::INTERMEDIATE => 'ri-plant-line',
            self::ADVANCED => 'ri-leaf-line',
            self::COMPLETE => 'ri-trophy-line',
        };
    }

    /**
     * Get the Filament color class for the memorization level
     */
    public function color(): string
    {
        return match ($this) {
            self::BEGINNER => 'gray',
            self::INTERMEDIATE => 'warning',
            self::ADVANCED => 'primary',
            self::COMPLETE => 'success',
        };
    }

    /**
     * Get the hex color for progress display
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::BEGINNER => '#9ca3af',     // gray-400
            self::INTERMEDIATE => '#f59e0b', // amber-500
            self::ADVANCED => '#3b82f6',     // blue-500
            self::COMPLETE => '#22c55e',     // green-500
        };
    }

    /**
     * Get typical juz range for this level
     */
    public function juzRange(): array
    {
        return match ($this) {
            self::BEGINNER => [0, 3],
            self::INTERMEDIATE => [3, 15],
            self::ADVANCED => [15, 29],
            self::COMPLETE => [30, 30],
        };
    }

    /**
     * Get recommended session frequency per week
     */
    public function recommendedSessionsPerWeek(): int
    {
        return match ($this) {
            self::BEGINNER => 3,        // 3 sessions per week for beginners
            self::INTERMEDIATE => 4,    // 4 sessions per week for intermediate
            self::ADVANCED => 5,        // 5 sessions per week for advanced
            self::COMPLETE => 2,        // 2 sessions per week for review
        };
    }

    /**
     * Check if this is the highest level
     */
    public function isComplete(): bool
    {
        return $this === self::COMPLETE;
    }

    /**
     * Get all memorization level values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get memorization level options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($level) => $level->label(), self::cases())
        );
    }

    /**
     * Get level based on juz count
     */
    public static function fromJuzCount(int $juzCount): self
    {
        return match (true) {
            $juzCount >= 30 => self::COMPLETE,
            $juzCount >= 15 => self::ADVANCED,
            $juzCount >= 3 => self::INTERMEDIATE,
            default => self::BEGINNER,
        };
    }
}
