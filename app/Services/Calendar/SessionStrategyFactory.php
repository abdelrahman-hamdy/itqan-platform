<?php

namespace App\Services\Calendar;

use App\Models\User;
use InvalidArgumentException;

/**
 * Factory for creating session strategy instances
 *
 * Responsible for instantiating the appropriate strategy based on teacher type.
 * Uses constructor injection for dependency management.
 */
class SessionStrategyFactory
{
    public function __construct(
        private QuranSessionStrategy $quranSessionStrategy,
        private AcademicSessionStrategy $academicSessionStrategy,
    ) {}

    /**
     * Create a session strategy instance for the given teacher type
     *
     * @param  string  $teacherType  Teacher type ('quran_teacher' or 'academic_teacher')
     * @return SessionStrategyInterface Strategy instance
     *
     * @throws InvalidArgumentException If teacher type is unknown
     */
    public function make(string $teacherType): SessionStrategyInterface
    {
        return match ($teacherType) {
            'quran_teacher' => $this->quranSessionStrategy,
            'academic_teacher' => $this->academicSessionStrategy,
            default => throw new InvalidArgumentException("Unknown teacher type: {$teacherType}"),
        };
    }

    /**
     * Create a session strategy instance for a specific user
     *
     * Used when viewing/managing another user's calendar (e.g., supervisor viewing teacher's calendar).
     *
     * @param  string  $teacherType  Teacher type ('quran_teacher' or 'academic_teacher')
     * @param  User|int  $user  User model or user ID
     * @return SessionStrategyInterface Strategy instance configured for the specified user
     *
     * @throws InvalidArgumentException If teacher type is unknown
     */
    public function makeForUser(string $teacherType, User|int $user): SessionStrategyInterface
    {
        return $this->make($teacherType)->forUser($user);
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
