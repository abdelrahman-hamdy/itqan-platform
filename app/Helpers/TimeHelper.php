<?php

if (!function_exists('formatTimeRemaining')) {
    /**
     * Format remaining time in a human-readable Arabic format
     * 
     * @param \Carbon\Carbon|\DateTime|string $targetTime The target date/time
     * @param bool $showSeconds Whether to show seconds (default: false)
     * @return array Array containing formatted string and components
     */
    function formatTimeRemaining($targetTime, bool $showSeconds = false): array
    {
        if (!$targetTime) {
            return [
                'formatted' => 'غير محدد',
                'is_past' => true,
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0,
                'total_minutes' => 0
            ];
        }

        $target = \Carbon\Carbon::parse($targetTime);
        $now = now();
        
        // Check if time has passed
        if ($target->isPast()) {
            $diffInMinutes = $now->diffInMinutes($target);
            return [
                'formatted' => 'انتهت منذ ' . formatTimePassed($target),
                'is_past' => true,
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0,
                'total_minutes' => -$diffInMinutes
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
            $parts[] = $days . ' ' . ($days == 1 ? 'يوم' : ($days == 2 ? 'يومان' : 'أيام'));
        }
        
        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours == 1 ? 'ساعة' : ($hours == 2 ? 'ساعتان' : 'ساعات'));
        }
        
        if ($minutes > 0) {
            $parts[] = $minutes . ' ' . ($minutes == 1 ? 'دقيقة' : ($minutes == 2 ? 'دقيقتان' : 'دقائق'));
        }
        
        if ($showSeconds && $seconds > 0 && $days == 0 && $hours == 0) {
            $parts[] = $seconds . ' ' . ($seconds == 1 ? 'ثانية' : ($seconds == 2 ? 'ثانيتان' : 'ثوان'));
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
            'total_minutes' => $totalMinutes
        ];
    }
}

if (!function_exists('formatTimePassed')) {
    /**
     * Format time that has already passed in Arabic
     * 
     * @param \Carbon\Carbon|\DateTime|string $pastTime
     * @return string
     */
    function formatTimePassed($pastTime): string
    {
        $past = \Carbon\Carbon::parse($pastTime);
        $now = now();
        
        $days = $past->diffInDays($now);
        $hours = $past->copy()->addDays($days)->diffInHours($now);
        $minutes = $past->copy()->addDays($days)->addHours($hours)->diffInMinutes($now);
        
        $parts = [];
        
        if ($days > 0) {
            $parts[] = $days . ' ' . ($days == 1 ? 'يوم' : ($days == 2 ? 'يومان' : 'أيام'));
        }
        
        if ($hours > 0 && $days < 7) {
            $parts[] = $hours . ' ' . ($hours == 1 ? 'ساعة' : ($hours == 2 ? 'ساعتان' : 'ساعات'));
        }
        
        if ($minutes > 0 && $days == 0 && $hours < 2) {
            $parts[] = $minutes . ' ' . ($minutes == 1 ? 'دقيقة' : ($minutes == 2 ? 'دقيقتان' : 'دقائق'));
        }

        return empty($parts) ? 'لحظات' : implode(' و ', $parts);
    }
}

if (!function_exists('getMeetingPreparationMessage')) {
    /**
     * Get meeting preparation message with friendly time format
     * 
     * @param \Carbon\Carbon $sessionTime
     * @param int $preparationMinutes
     * @return array
     */
    function getMeetingPreparationMessage($sessionTime, int $preparationMinutes = 15): array
    {
        if (!$sessionTime) {
            return [
                'message' => '',
                'type' => 'none'
            ];
        }

        $preparationTime = $sessionTime->copy()->subMinutes($preparationMinutes);
        $now = now();

        // If we're past the preparation time
        if ($now->isAfter($preparationTime)) {
            // If we're before session start
            if ($now->isBefore($sessionTime)) {
                return [
                    'message' => 'جاري تحضير الاجتماع...',
                    'type' => 'preparing',
                    'icon' => 'ri-settings-3-line animate-spin'
                ];
            }
            // If session has started
            return [
                'message' => 'الاجتماع متاح الآن',
                'type' => 'ready',
                'icon' => 'ri-video-line'
            ];
        }

        // If we're before preparation time
        $timeRemaining = formatTimeRemaining($preparationTime);
        
        return [
            'message' => 'سيتم تحضير الاجتماع خلال ' . $timeRemaining['formatted'],
            'type' => 'waiting',
            'icon' => 'ri-timer-line'
        ];
    }
}
