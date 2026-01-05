<?php

namespace App\Filament\Shared\Traits;

use Carbon\Carbon;

/**
 * Trait HandlesScheduling
 *
 * Provides common scheduling utilities for calendar pages.
 * Used by both Quran and Academic teacher calendars.
 *
 * @property string $activeTab
 * @property int|null $selectedCircleId
 * @property string|null $selectedCircleType
 * @property int|null $selectedTrialRequestId
 * @property int|null $selectedItemId
 * @property string|null $selectedItemType
 */
trait HandlesScheduling
{
    /**
     * Day name mapping constants
     */
    public const DAY_MAPPING = [
        'saturday' => Carbon::SATURDAY,
        'sunday' => Carbon::SUNDAY,
        'monday' => Carbon::MONDAY,
        'tuesday' => Carbon::TUESDAY,
        'wednesday' => Carbon::WEDNESDAY,
        'thursday' => Carbon::THURSDAY,
        'friday' => Carbon::FRIDAY,
    ];

    /**
     * Arabic day names
     */
    public const ARABIC_DAY_NAMES = [
        'saturday' => 'السبت',
        'sunday' => 'الأحد',
        'monday' => 'الاثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة',
    ];

    /**
     * Get the next date for a specific day of the week
     *
     * @param Carbon $startDate Starting date for calculation
     * @param string $day Day of week (e.g., 'monday', 'tuesday')
     * @return Carbon Next occurrence of the specified day
     */
    protected function getNextDateForDay(Carbon $startDate, string $day): Carbon
    {
        $targetDay = self::DAY_MAPPING[$day] ?? Carbon::MONDAY;

        // If today is the target day, return today
        if ($startDate->dayOfWeek === $targetDay) {
            return $startDate->copy();
        }

        // Otherwise, get the next occurrence
        return $startDate->next($targetDay);
    }

    /**
     * Get Arabic day name
     *
     * @param string $day Day of week in English
     * @return string Arabic day name
     */
    protected function getDayNameInArabic(string $day): string
    {
        return self::ARABIC_DAY_NAMES[$day] ?? $day;
    }

    /**
     * Generate time options for select field
     *
     * @param int $startHour Starting hour (default: 6)
     * @param int $endHour Ending hour (default: 23)
     * @return array Array of time options [value => display]
     */
    protected function getTimeOptions(int $startHour = 6, int $endHour = 23): array
    {
        $options = [];

        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            $time = sprintf('%02d:00', $hour);
            // Convert to 12-hour format for display
            $hour12 = $hour > 12 ? $hour - 12 : ($hour == 0 ? 12 : $hour);
            $period = $hour >= 12 ? 'م' : 'ص';
            $display = sprintf('%02d:00', $hour) . ' (' . $hour12 . ' ' . $period . ')';
            $options[$time] = $display;
        }

        return $options;
    }

    /**
     * Calculate weeks needed for scheduling
     *
     * @param int $sessionCount Total sessions to schedule
     * @param int $daysPerWeek Number of days per week
     * @return int Weeks needed
     */
    protected function calculateWeeksNeeded(int $sessionCount, int $daysPerWeek): int
    {
        return (int) ceil($sessionCount / max(1, $daysPerWeek));
    }

    /**
     * Change active tab and clear selections
     *
     * @param string $tab Tab name
     * @return void
     */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;

        // Clear selections when switching tabs
        $this->selectedCircleId = null;
        $this->selectedCircleType = null;
        $this->selectedItemId = null;
        $this->selectedItemType = null;
        $this->selectedTrialRequestId = null;
    }
}
