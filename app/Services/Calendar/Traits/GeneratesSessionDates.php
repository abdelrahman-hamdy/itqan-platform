<?php

namespace App\Services\Calendar\Traits;

use App\Services\AcademyContextService;
use Carbon\Carbon;

/**
 * Trait for generating session dates from weekly schedule configurations.
 *
 * Provides unified session date generation logic for both:
 * - SessionManagementService (Quran session scheduling)
 * - AcademicSessionStrategy (Academic session scheduling)
 *
 * All times are interpreted in academy timezone. Storage timezone
 * depends on APP_TIMEZONE configuration.
 */
trait GeneratesSessionDates
{
    /**
     * Generate session dates based on schedule configuration.
     *
     * @param  array  $days  Array of day names (English or Arabic)
     * @param  string  $time  Time in HH:MM format
     * @param  string  $startDate  Start date for generation
     * @param  int  $count  Number of sessions to generate
     * @param  bool  $skipPastDates  Whether to skip dates in the past
     * @return array Array of Carbon dates in academy timezone
     */
    protected function generateSessionDates(
        array $days,
        string $time,
        string $startDate,
        int $count,
        bool $skipPastDates = true
    ): array {
        $dates = [];

        // Get academy timezone for proper time interpretation
        $academyTimezone = AcademyContextService::getTimezone();

        // Parse start date in academy timezone
        $currentDate = Carbon::parse($startDate, $academyTimezone)->startOfDay();

        // Get day mappings from config (supports both English and Arabic)
        $dayMapping = config('calendar.day_mappings', $this->getDefaultDayMapping());

        // Convert day names to day numbers
        $selectedDayNumbers = array_map(
            fn ($day) => $dayMapping[strtolower($day)] ?? $dayMapping[$day] ?? null,
            $days
        );

        // Remove any null values (invalid day names)
        $selectedDayNumbers = array_filter($selectedDayNumbers, fn ($num) => $num !== null);

        if (empty($selectedDayNumbers)) {
            return [];
        }

        // Get safety limit from config
        $maxAheadDays = config('calendar.generation.max_ahead_days', 365);
        $maxSessionsPerBatch = config('calendar.generation.max_sessions_per_batch', 100);

        // Limit count to prevent runaway generation
        $count = min($count, $maxSessionsPerBatch);

        while (count($dates) < $count) {
            $dayOfWeek = $currentDate->dayOfWeek;

            if (in_array($dayOfWeek, $selectedDayNumbers)) {
                // Create datetime in academy timezone
                // NOTE: Callers must convert to UTC before saving to database!
                // Laravel's Eloquent does NOT auto-convert - use AcademyContextService::toUtcForStorage()
                $sessionDateTime = Carbon::parse(
                    $currentDate->format('Y-m-d').' '.$time,
                    $academyTimezone
                );

                // Skip past dates if requested (compare in same timezone)
                $now = AcademyContextService::nowInAcademyTimezone();
                if (! $skipPastDates || ! $sessionDateTime->isBefore($now)) {
                    $dates[] = $sessionDateTime->copy();
                }
            }

            $currentDate->addDay();

            // Safety: don't generate beyond the configured limit
            if ($currentDate->diffInDays(Carbon::parse($startDate, $academyTimezone)) > $maxAheadDays) {
                break;
            }
        }

        return $dates;
    }

    /**
     * Get the day number for a day name.
     *
     * @param  string  $dayName  Day name (English or Arabic)
     * @return int|null Carbon day constant or null if invalid
     */
    protected function getDayNumber(string $dayName): ?int
    {
        $dayMapping = config('calendar.day_mappings', $this->getDefaultDayMapping());

        return $dayMapping[strtolower($dayName)] ?? $dayMapping[$dayName] ?? null;
    }

    /**
     * Get default day mapping if config is not available.
     */
    private function getDefaultDayMapping(): array
    {
        return [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'الأحد' => 0,
            'الاثنين' => 1,
            'الثلاثاء' => 2,
            'الأربعاء' => 3,
            'الخميس' => 4,
            'الجمعة' => 5,
            'السبت' => 6,
        ];
    }
}
