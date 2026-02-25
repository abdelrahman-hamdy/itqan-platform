<?php

namespace App\Services;

use BackedEnum;
use App\Contracts\CalendarServiceInterface;
use App\Models\User;
use App\Services\Calendar\CalendarFilterService;
use App\Services\Calendar\EventFetchingService;
use App\Services\Calendar\EventFormattingService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CalendarService implements CalendarServiceInterface
{
    /**
     * Constructor with dependency injection
     */
    public function __construct(
        private EventFetchingService $eventFetcher,
        private EventFormattingService $eventFormatter,
        private CalendarFilterService $filterService
    ) {}

    /**
     * Get unified calendar for user
     */
    public function getUserCalendar(
        User $user,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): Collection {

        $cacheKey = $this->generateCacheKey($user, $startDate, $endDate, $filters);

        return Cache::remember($cacheKey, config('business.cache.calendar_ttl', 300), function () use ($user, $startDate, $endDate, $filters) {
            $events = collect();

            // Get Quran sessions
            if ($this->filterService->shouldIncludeEventType('quran_sessions', $filters)) {
                $quranSessions = $this->eventFetcher->getQuranSessions($user, $startDate, $endDate);
                $events = $events->merge($this->eventFormatter->formatQuranSessions($quranSessions, $user));
            }

            // Get Interactive course sessions
            if ($this->filterService->shouldIncludeEventType('course_sessions', $filters)) {
                $courseSessions = $this->eventFetcher->getCourseSessions($user, $startDate, $endDate);
                $events = $events->merge($this->eventFormatter->formatCourseSessions($courseSessions, $user));
            }

            // Get Circle sessions
            if ($this->filterService->shouldIncludeEventType('circle_sessions', $filters)) {
                $circleSessions = $this->eventFetcher->getCircleSessions($user, $startDate, $endDate);
                $events = $events->merge($this->eventFormatter->formatCircleSessions($circleSessions, $user));
            }

            // Get Break times / Unavailable periods
            if ($this->filterService->shouldIncludeEventType('breaks', $filters)) {
                $breaks = $this->eventFetcher->getBreakTimes($user, $startDate, $endDate);
                $events = $events->merge($breaks);
            }

            // Apply additional filters
            return $this->filterService->applyFilters($events, $filters);
        });
    }

    /**
     * Check for conflicts when scheduling new event
     */
    public function checkConflicts(
        User $user,
        Carbon $startTime,
        Carbon $endTime,
        ?string $excludeType = null,
        ?int $excludeId = null
    ): Collection {

        $conflicts = collect();

        // Check session conflicts
        $sessionConflicts = $this->eventFetcher->checkSessionConflicts($user, $startTime, $endTime, $excludeType, $excludeId);
        $conflicts = $conflicts->merge($sessionConflicts);

        // Check course conflicts
        $courseConflicts = $this->eventFetcher->checkCourseConflicts($user, $startTime, $endTime, $excludeType, $excludeId);
        $conflicts = $conflicts->merge($courseConflicts);

        // Check circle conflicts (for teachers)
        if ($user->isQuranTeacher()) {
            $circleConflicts = $this->eventFetcher->checkCircleConflicts($user, $startTime, $endTime, $excludeType, $excludeId);
            $conflicts = $conflicts->merge($circleConflicts);
        }

        return $conflicts;
    }

    /**
     * Get available time slots for user
     */
    public function getAvailableSlots(
        User $user,
        Carbon $date,
        int $durationMinutes = 60,
        array $workingHours = ['09:00', '17:00']
    ): Collection {

        $slots = collect();
        $startTime = $date->copy()->setTimeFromTimeString($workingHours[0]);
        $endTime = $date->copy()->setTimeFromTimeString($workingHours[1]);

        // Generate potential slots
        $currentTime = $startTime->copy();
        while ($currentTime->copy()->addMinutes($durationMinutes)->lte($endTime)) {
            $slotEnd = $currentTime->copy()->addMinutes($durationMinutes);

            // Check if slot is available
            $conflicts = $this->checkConflicts($user, $currentTime, $slotEnd);

            if ($conflicts->isEmpty()) {
                $slots->push([
                    'start_time' => $currentTime->copy(),
                    'end_time' => $slotEnd,
                    'duration_minutes' => $durationMinutes,
                    'available' => true,
                ]);
            }

            $currentTime->addMinutes(30); // 30-minute intervals
        }

        return $slots;
    }

    /**
     * Get teacher availability for week
     */
    public function getTeacherWeeklyAvailability(User $teacher, Carbon $weekStart): array
    {
        if (! $teacher->isQuranTeacher()) {
            return [];
        }

        $availability = [];
        $period = CarbonPeriod::create($weekStart, '1 day', $weekStart->copy()->addDays(6));

        foreach ($period as $date) {
            $dayName = strtolower($date->format('l'));

            // Get available slots for this day
            $slots = $this->getAvailableSlots($teacher, $date);

            // Get existing sessions
            $sessions = $this->getUserCalendar($teacher, $date->startOfDay(), $date->endOfDay())
                ->filter(fn ($event) => $event['type'] === 'session');

            $availability[$dayName] = [
                'date' => $date->toDateString(),
                'available_slots' => $slots->count(),
                'booked_sessions' => $sessions->count(),
                'available_hours' => $slots->sum(fn ($slot) => $slot['duration_minutes']) / 60,
                'slots' => $slots,
                'sessions' => $sessions,
            ];
        }

        return $availability;
    }

    /**
     * Get calendar statistics for user
     */
    public function getCalendarStats(User $user, Carbon $month): array
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        $events = $this->getUserCalendar($user, $startDate, $endDate);

        $stats = [
            'total_events' => $events->count(),
            'by_type' => $events->countBy('type'),
            'by_status' => $events->countBy(function ($event) {
                // Convert Enum to string if needed
                $status = $event['status'] ?? 'unknown';
                if ($status instanceof BackedEnum) {
                    return $status->value;
                }

                return is_object($status) ? $status->name ?? 'unknown' : $status;
            }),
            'by_week' => [],
            'busiest_day' => null,
            'total_hours' => 0,
        ];

        // Calculate weekly breakdown
        $period = CarbonPeriod::create($startDate, '1 week', $endDate);
        foreach ($period as $weekStart) {
            $weekEnd = $weekStart->copy()->addDays(6)->min($endDate);
            $weekEvents = $events->filter(function ($event) use ($weekStart, $weekEnd) {
                $eventDate = Carbon::parse($event['start_time']);

                return $eventDate->between($weekStart, $weekEnd);
            });

            $stats['by_week'][] = [
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'events_count' => $weekEvents->count(),
                'total_hours' => $weekEvents->sum('duration_minutes') / 60,
            ];
        }

        // Find busiest day
        $dayEvents = $events->groupBy(function ($event) {
            return Carbon::parse($event['start_time'])->toDateString();
        });

        if ($dayEvents->isNotEmpty()) {
            $busiestDay = $dayEvents->map->count()->sortByDesc(function ($count) {
                return $count;
            })->keys()->first();
            $stats['busiest_day'] = [
                'date' => $busiestDay,
                'events_count' => $dayEvents[$busiestDay]->count(),
            ];
        }

        // Calculate total hours
        $stats['total_hours'] = $events->sum('duration_minutes') / 60;

        return $stats;
    }

    /**
     * Generate cache key for calendar data
     */
    private function generateCacheKey(User $user, Carbon $startDate, Carbon $endDate, array $filters): string
    {
        $filterHash = md5(serialize($filters));

        // Include academy_id to prevent cross-tenant cache collisions for super-admins
        // who may switch between academies while keeping the same user ID
        return "calendar:academy:{$user->academy_id}:user:{$user->id}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}:{$filterHash}";
    }
}
