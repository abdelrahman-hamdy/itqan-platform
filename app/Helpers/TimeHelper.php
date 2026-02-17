<?php

use App\Services\AcademyContextService;
use Carbon\Carbon;

if (! function_exists('getAcademyTimezone')) {
    /**
     * Get the current academy's timezone
     * Falls back to UTC if no academy context is available
     */
    function getAcademyTimezone(): string
    {
        // Try to get from authenticated user's academy
        $user = auth()->user();
        if ($user && $user->academy && $user->academy->timezone) {
            $timezone = $user->academy->timezone;

            // Handle both enum and string
            return is_object($timezone) ? $timezone->value : $timezone;
        }

        // Try to get from AcademyContextService
        if (class_exists(AcademyContextService::class)) {
            return AcademyContextService::getTimezone();
        }

        // Fallback to app timezone or UTC
        return config('app.timezone', 'UTC');
    }
}

if (! function_exists('nowInAcademyTimezone')) {
    /**
     * Get the current time in the academy's timezone
     */
    function nowInAcademyTimezone(): Carbon
    {
        return now()->setTimezone(getAcademyTimezone());
    }
}

if (! function_exists('toAcademyTimezone')) {
    /**
     * Convert a Carbon instance to the academy's timezone
     *
     * @param Carbon|DateTime|string|null $time
     */
    function toAcademyTimezone($time): ?Carbon
    {
        if (! $time) {
            return null;
        }

        $carbon = $time instanceof Carbon ? $time->copy() : Carbon::parse($time);

        return $carbon->setTimezone(getAcademyTimezone());
    }
}

if (! function_exists('formatTimeRemaining')) {
    /**
     * Format remaining time in a human-readable Arabic format
     *
     * @param Carbon|DateTime|string $targetTime The target date/time
     * @param  bool  $showSeconds  Whether to show seconds (default: false)
     * @return array Array containing formatted string and components
     */
    function formatTimeRemaining($targetTime, bool $showSeconds = false): array
    {
        if (! $targetTime) {
            return [
                'formatted' => 'غير محدد',
                'is_past' => true,
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0,
                'total_minutes' => 0,
            ];
        }

        // Parse target time and ensure it's in academy timezone for comparison
        $target = Carbon::parse($targetTime);
        // Use academy timezone for "now" to ensure consistent comparisons
        $now = nowInAcademyTimezone();
        // Also convert target to academy timezone for display purposes
        $targetInTz = $target->copy()->setTimezone(getAcademyTimezone());

        // Check if time has passed
        if ($target->isPast()) {
            $diffInMinutes = $now->diffInMinutes($target);

            return [
                'formatted' => 'انتهت منذ '.formatTimePassed($target),
                'is_past' => true,
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0,
                'total_minutes' => -$diffInMinutes,
            ];
        }

        // Calculate differences using Carbon's built-in methods
        $totalMinutes = (int) $now->diffInMinutes($target);
        $days = (int) floor($totalMinutes / (24 * 60));
        $remainingMinutes = $totalMinutes % (24 * 60);
        $hours = (int) floor($remainingMinutes / 60);
        $minutes = (int) ($remainingMinutes % 60);
        $totalSeconds = (int) $now->diffInSeconds($target);
        $seconds = $totalSeconds % 60;

        // Build formatted string
        $parts = [];

        if ($days > 0) {
            $parts[] = $days.' '.($days == 1 ? 'يوم' : ($days == 2 ? 'يومان' : 'أيام'));
        }

        if ($hours > 0) {
            $parts[] = $hours.' '.($hours == 1 ? 'ساعة' : ($hours == 2 ? 'ساعتان' : 'ساعات'));
        }

        if ($minutes > 0) {
            $parts[] = $minutes.' '.($minutes == 1 ? 'دقيقة' : ($minutes == 2 ? 'دقيقتان' : 'دقائق'));
        }

        if ($showSeconds && $seconds > 0 && $days == 0 && $hours == 0) {
            $parts[] = $seconds.' '.($seconds == 1 ? 'ثانية' : ($seconds == 2 ? 'ثانيتان' : 'ثوان'));
        }

        // Handle edge cases
        if (empty($parts)) {
            if ($showSeconds) {
                $formatted = 'أقل من ثانية';
            } else {
                $formatted = 'أقل من دقيقة';
            }
        } else {
            $formatted = implode(' و ', $parts);
        }

        return [
            'formatted' => $formatted,
            'is_past' => false,
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds,
            'total_minutes' => $totalMinutes,
        ];
    }
}

if (! function_exists('formatTimePassed')) {
    /**
     * Format time that has already passed in Arabic
     *
     * @param Carbon|DateTime|string $pastTime
     */
    function formatTimePassed($pastTime): string
    {
        $past = Carbon::parse($pastTime);
        // Use academy timezone for "now"
        $now = nowInAcademyTimezone();

        $days = $past->diffInDays($now);
        $hours = $past->copy()->addDays($days)->diffInHours($now);
        $minutes = $past->copy()->addDays($days)->addHours($hours)->diffInMinutes($now);

        $parts = [];

        if ($days > 0) {
            $parts[] = $days.' '.($days == 1 ? 'يوم' : ($days == 2 ? 'يومان' : 'أيام'));
        }

        if ($hours > 0 && $days < 7) {
            $parts[] = $hours.' '.($hours == 1 ? 'ساعة' : ($hours == 2 ? 'ساعتان' : 'ساعات'));
        }

        if ($minutes > 0 && $days == 0 && $hours < 2) {
            $parts[] = $minutes.' '.($minutes == 1 ? 'دقيقة' : ($minutes == 2 ? 'دقيقتان' : 'دقائق'));
        }

        return empty($parts) ? 'لحظات' : implode(' و ', $parts);
    }
}

if (! function_exists('getMeetingPreparationMessage')) {
    /**
     * Get meeting preparation message with friendly time format
     *
     * @param  mixed  $session  Session model or Carbon time
     * @param  int|null  $preparationMinutes  Optional preparation minutes (only used if $session is Carbon)
     */
    function getMeetingPreparationMessage($session, ?int $preparationMinutes = null): array
    {
        // Handle Carbon time directly
        if ($session instanceof Carbon) {
            $sessionTime = $session;
            $prepMinutes = $preparationMinutes ?? 10;
        }
        // Handle session model
        elseif (is_object($session) && method_exists($session, 'getStatusDisplayData')) {
            $sessionTime = $session->scheduled_at;
            // Get preparation minutes from session if available
            $statusData = $session->getStatusDisplayData();
            $prepMinutes = $statusData['preparation_minutes'] ?? 10;
        } else {
            return [
                'message' => '',
                'type' => 'none',
            ];
        }

        if (! $sessionTime) {
            return [
                'message' => '',
                'type' => 'none',
            ];
        }

        $preparationTime = $sessionTime->copy()->subMinutes($prepMinutes);
        // Use academy timezone for "now"
        $now = nowInAcademyTimezone();

        // If we're past the preparation time
        if ($now->isAfter($preparationTime)) {
            // If we're before session start
            if ($now->isBefore($sessionTime)) {
                return [
                    'message' => 'جاري تحضير الاجتماع...',
                    'type' => 'preparing',
                    'icon' => 'ri-settings-3-line animate-spin',
                ];
            }

            // If session has started
            return [
                'message' => 'الاجتماع متاح الآن',
                'type' => 'ready',
                'icon' => 'ri-video-line',
            ];
        }

        // If we're before preparation time
        $timeRemaining = formatTimeRemaining($preparationTime);

        return [
            'message' => 'سيتم تحضير الاجتماع خلال '.$timeRemaining['formatted'],
            'type' => 'waiting',
            'icon' => 'ri-timer-line',
        ];
    }
}

if (! function_exists('formatTimeArabic')) {
    /**
     * Format time in Arabic 12-hour format
     * Automatically converts to academy timezone
     *
     * @param Carbon|DateTime|string $time
     * @param  string|null  $timezone  Optional timezone override
     */
    function formatTimeArabic($time, ?string $timezone = null): string
    {
        if (! $time) {
            return 'غير محدد';
        }

        // Parse the time and convert to appropriate timezone
        $carbon = Carbon::parse($time);
        $tz = $timezone ?? getAcademyTimezone();
        $carbon = $carbon->setTimezone($tz);

        $hour = $carbon->format('g'); // 12-hour format without leading zeros
        $minute = $carbon->format('i');

        // Determine Arabic period
        $period = $carbon->format('A') === 'AM' ? 'صباحاً' : 'مساءً';

        // Handle special cases
        if ($hour == 12 && $carbon->format('A') === 'AM') {
            $period = 'منتصف الليل';
        } elseif ($hour == 12 && $carbon->format('A') === 'PM') {
            $period = 'ظهراً';
        }

        return "{$hour}:{$minute} {$period}";
    }
}

if (! function_exists('formatDateArabic')) {
    /**
     * Format date in Arabic format with academy timezone
     *
     * @param Carbon|DateTime|string $date
     * @param  string  $format  Date format (default: 'Y/m/d')
     * @param  string|null  $timezone  Optional timezone override
     */
    function formatDateArabic($date, string $format = 'Y/m/d', ?string $timezone = null): string
    {
        if (! $date) {
            return 'غير محدد';
        }

        $carbon = Carbon::parse($date);
        $tz = $timezone ?? getAcademyTimezone();
        $carbon = $carbon->setTimezone($tz);

        return $carbon->format($format);
    }
}

if (! function_exists('formatDateTimeArabic')) {
    /**
     * Format date and time together in Arabic format with academy timezone
     *
     * @param Carbon|DateTime|string $datetime
     * @param  string|null  $timezone  Optional timezone override
     */
    function formatDateTimeArabic($datetime, ?string $timezone = null): string
    {
        if (! $datetime) {
            return 'غير محدد';
        }

        $carbon = Carbon::parse($datetime);
        $tz = $timezone ?? getAcademyTimezone();
        $carbon = $carbon->setTimezone($tz);

        $date = $carbon->format('Y/m/d');
        $time = formatTimeArabic($carbon, $tz);

        return "{$date} - {$time}";
    }
}

if (! function_exists('toSaudiTime')) {
    /**
     * Convert datetime to Saudi Arabia timezone (Asia/Riyadh)
     * Alias for toAcademyTimezone() when academy uses Saudi timezone
     *
     * @param Carbon|DateTime|string|null $datetime
     */
    function toSaudiTime($datetime): ?Carbon
    {
        if (! $datetime) {
            return null;
        }

        $carbon = $datetime instanceof Carbon ? $datetime : Carbon::parse($datetime);

        return $carbon->copy()->setTimezone('Asia/Riyadh');
    }
}

if (! function_exists('formatSaudiTime')) {
    /**
     * Format datetime in Saudi timezone
     *
     * @param Carbon|DateTime|string|null $datetime
     * @param  string  $format  Carbon format string (default: 'Y-m-d H:i:s')
     */
    function formatSaudiTime($datetime, string $format = 'Y-m-d H:i:s'): string
    {
        if (! $datetime) {
            return '';
        }

        $saudiTime = toSaudiTime($datetime);

        return $saudiTime ? $saudiTime->format($format) : '';
    }
}

if (! function_exists('parseSaudiTime')) {
    /**
     * Parse user input time string assuming it's in Saudi timezone, convert to UTC
     * Used when accepting time input that should be interpreted as Saudi time
     *
     * @param  string  $timeString
     */
    function parseSaudiTime(string $timeString): ?Carbon
    {
        try {
            // Parse the string assuming Saudi timezone
            return Carbon::parse($timeString, 'Asia/Riyadh');
        } catch (Exception $e) {
            return null;
        }
    }
}

if (! function_exists('parseAndConvertToUtc')) {
    /**
     * Parse datetime input in academy timezone and convert to UTC for storage
     * This is the correct way to handle form input before saving to database
     *
     * @param Carbon|DateTime|string|null $datetime
     * @param  string|null  $fromTimezone  Optional timezone to parse from (defaults to academy timezone)
     */
    function parseAndConvertToUtc($datetime, ?string $fromTimezone = null): ?Carbon
    {
        if (! $datetime) {
            return null;
        }

        $timezone = $fromTimezone ?? getAcademyTimezone();

        if ($datetime instanceof Carbon) {
            return $datetime->copy()->setTimezone($timezone)->utc();
        }

        // Parse string in specified timezone, then convert to UTC
        return Carbon::parse($datetime, $timezone)->utc();
    }
}
