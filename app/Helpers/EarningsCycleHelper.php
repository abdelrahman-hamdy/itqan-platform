<?php

namespace App\Helpers;

use Carbon\Carbon;

class EarningsCycleHelper
{
    /**
     * Returns ['start' => Carbon, 'end' => Carbon] for the cycle named ($year, $month).
     *
     * Strict 29→28 rule:
     *   - Cycle for ($year, $month) starts on day 29 of the previous month and ends on day 28 of the named month.
     *   - In non-leap years, Carbon's day-overflow makes (Feb 29) → (Mar 1), which is exactly the desired
     *     start for the March cycle in non-leap years. In leap years, Feb has a real day 29, so the March
     *     cycle starts Feb 29.
     *   - For January, the start wraps to Dec 29 of the previous year.
     */
    public static function cycleRange(int $year, int $month): array
    {
        $tz = getAcademyTimezone();
        $end = Carbon::create($year, $month, 28, 23, 59, 59, $tz);

        if ($month === 1) {
            $start = Carbon::create($year - 1, 12, 29, 0, 0, 0, $tz);
        } else {
            $start = Carbon::create($year, $month - 1, 29, 0, 0, 0, $tz);
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Given any datetime, return the cycle that contains it, in academy timezone.
     *
     * Rule: day >= 29 → next month's cycle (e.g., Mar 29 → April; Feb 29 leap → March).
     *
     * @return array{year: int, month: int, value: string}
     */
    public static function cycleValueForDate(Carbon $date): array
    {
        $local = $date->copy()->setTimezone(getAcademyTimezone());
        $year = $local->year;
        $month = $local->month;

        if ($local->day >= 29) {
            if ($month === 12) {
                $year++;
                $month = 1;
            } else {
                $month++;
            }
        }

        return [
            'year' => $year,
            'month' => $month,
            'value' => sprintf('%04d-%02d', $year, $month),
        ];
    }

    /**
     * Returns the cycle that "now" belongs to, with a localized label.
     *
     * @return array{year: int, month: int, value: string, label: string}
     */
    public static function currentCycle(): array
    {
        $cycle = self::cycleValueForDate(nowInAcademyTimezone());
        $cycle['label'] = self::cycleLabel($cycle['year'], $cycle['month']);

        return $cycle;
    }

    /**
     * Localized "Month Year" label (Arabic by default), matching the existing convention
     * used elsewhere in the supervisor earnings UI.
     */
    public static function cycleLabel(int $year, int $month): string
    {
        return Carbon::create($year, $month, 1)->locale('ar')->translatedFormat('F Y');
    }

    /**
     * Storage form for the `teacher_earnings.earning_month` date column —
     * the first day of the cycle's named month (Y-m-01).
     */
    public static function cycleStorageDate(Carbon $date): string
    {
        $cycle = self::cycleValueForDate($date);

        return sprintf('%04d-%02d-01', $cycle['year'], $cycle['month']);
    }
}
