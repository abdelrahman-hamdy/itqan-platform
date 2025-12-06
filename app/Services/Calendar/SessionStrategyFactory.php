<?php

namespace App\Services\Calendar;

use InvalidArgumentException;

/**
 * Factory for creating session strategy instances
 *
 * Responsible for instantiating the appropriate strategy based on teacher type.
 * Uses Laravel's service container for dependency injection.
 */
class SessionStrategyFactory
{
    /**
     * Create a session strategy instance for the given teacher type
     *
     * @param string $teacherType Teacher type ('quran_teacher' or 'academic_teacher')
     * @return SessionStrategyInterface Strategy instance
     * @throws InvalidArgumentException If teacher type is unknown
     */
    public static function make(string $teacherType): SessionStrategyInterface
    {
        return match ($teacherType) {
            'quran_teacher' => app(QuranSessionStrategy::class),
            'academic_teacher' => app(AcademicSessionStrategy::class),
            default => throw new InvalidArgumentException("Unknown teacher type: {$teacherType}"),
        };
    }

    /**
     * Get all available teacher types
     *
     * @return array Array of valid teacher type identifiers
     */
    public static function getAvailableTypes(): array
    {
        return [
            'quran_teacher',
            'academic_teacher',
        ];
    }
}
