<?php

namespace App\Enums;

/**
 * Quran Specialization Enum
 *
 * Defines the different types of Quran learning specializations
 * offered in Quran circles and individual subscriptions.
 *
 * Each specialization has different teaching methodologies and goals:
 * - Memorization (حفظ): Focus on memorizing Quran
 * - Recitation (تلاوة): Focus on proper recitation
 * - Tajweed (تجويد): Focus on tajweed rules
 * - Review (مراجعة): Focus on reviewing previously memorized portions
 *
 * @see \App\Models\QuranCircle
 * @see \App\Models\QuranIndividualCircle
 */
enum QuranSpecialization: string
{
    case MEMORIZATION = 'memorization';    // حفظ - Quran memorization
    case RECITATION = 'recitation';        // تلاوة - Quran recitation
    case TAJWEED = 'tajweed';              // تجويد - Tajweed rules
    case REVIEW = 'review';                // مراجعة - Review and revision

    /**
     * Get the localized label for the specialization
     */
    public function label(): string
    {
        return __('enums.quran_specialization.'.$this->value);
    }

    /**
     * Get the icon for the specialization
     */
    public function icon(): string
    {
        return match ($this) {
            self::MEMORIZATION => 'ri-book-mark-line',
            self::RECITATION => 'ri-music-line',
            self::TAJWEED => 'ri-book-read-line',
            self::REVIEW => 'ri-loop-left-line',
        };
    }

    /**
     * Get the Filament color class for the specialization
     */
    public function color(): string
    {
        return match ($this) {
            self::MEMORIZATION => 'success',
            self::RECITATION => 'primary',
            self::TAJWEED => 'warning',
            self::REVIEW => 'info',
        };
    }

    /**
     * Get typical session duration in minutes for this specialization
     */
    public function typicalDuration(): int
    {
        return match ($this) {
            self::MEMORIZATION => 60,      // Memorization needs longer sessions
            self::RECITATION => 45,        // Recitation sessions are medium
            self::TAJWEED => 60,           // Tajweed requires detailed study
            self::REVIEW => 30,            // Review can be shorter
        };
    }

    /**
     * Get recommended sessions per week
     */
    public function recommendedSessionsPerWeek(): int
    {
        return match ($this) {
            self::MEMORIZATION => 5,       // Daily for best results
            self::RECITATION => 3,         // 3 times per week
            self::TAJWEED => 2,            // 2 times per week
            self::REVIEW => 2,             // 2 times per week
        };
    }

    /**
     * Get all specialization values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get specialization options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($spec) => $spec->label(), self::cases())
        );
    }
}
