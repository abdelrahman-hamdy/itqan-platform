<?php

namespace App\Services;

use App\Models\InteractiveCourseSession;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class CalendarService
{
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

        return Cache::remember($cacheKey, 300, function () use ($user, $startDate, $endDate, $filters) {
            $events = collect();

            // Get Quran sessions
            if ($this->shouldIncludeEventType('quran_sessions', $filters)) {
                $quranSessions = $this->getQuranSessions($user, $startDate, $endDate);
                $events = $events->merge($this->formatQuranSessions($quranSessions, $user));
            }

            // Get Interactive course sessions
            if ($this->shouldIncludeEventType('course_sessions', $filters)) {
                $courseSessions = $this->getCourseSessions($user, $startDate, $endDate);
                $events = $events->merge($this->formatCourseSessions($courseSessions, $user));
            }

            // Get Circle sessions
            if ($this->shouldIncludeEventType('circle_sessions', $filters)) {
                $circleSessions = $this->getCircleSessions($user, $startDate, $endDate);
                $events = $events->merge($this->formatCircleSessions($circleSessions, $user));
            }

            // Get Break times / Unavailable periods
            if ($this->shouldIncludeEventType('breaks', $filters)) {
                $breaks = $this->getBreakTimes($user, $startDate, $endDate);
                $events = $events->merge($breaks);
            }

            // Apply additional filters
            if (isset($filters['status'])) {
                $statusFilters = (array) $filters['status'];
                $events = $events->filter(function ($event) use ($statusFilters) {
                    $eventStatus = $event['status'] ?? null;
                    // Convert enum to string if needed
                    if ($eventStatus instanceof \BackedEnum) {
                        $eventStatus = $eventStatus->value;
                    } elseif (is_object($eventStatus)) {
                        $eventStatus = $eventStatus->name ?? null;
                    }
                    return in_array($eventStatus, $statusFilters);
                });
            }

            if (isset($filters['search'])) {
                $search = strtolower($filters['search']);
                $events = $events->filter(function ($event) use ($search) {
                    return str_contains(strtolower($event['title']), $search) ||
                           str_contains(strtolower($event['description'] ?? ''), $search);
                });
            }

            return $events->sortBy('start_time')->values();
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
        $sessionConflicts = $this->checkSessionConflicts($user, $startTime, $endTime, $excludeType, $excludeId);
        $conflicts = $conflicts->merge($sessionConflicts);

        // Check course conflicts
        $courseConflicts = $this->checkCourseConflicts($user, $startTime, $endTime, $excludeType, $excludeId);
        $conflicts = $conflicts->merge($courseConflicts);

        // Check circle conflicts (for teachers)
        if ($user->isQuranTeacher()) {
            $circleConflicts = $this->checkCircleConflicts($user, $startTime, $endTime, $excludeType, $excludeId);
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
                if ($status instanceof \BackedEnum) {
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
     * Get Quran sessions for user (optimized)
     */
    private function getQuranSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $cacheKey = "quran_sessions:{$user->id}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 600, function () use ($user, $startDate, $endDate) {
            $query = QuranSession::select([
                'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
                'quran_teacher_id', 'student_id', 'quran_subscription_id', 'circle_id', 'session_type',
                'individual_circle_id', 'is_template', 'is_scheduled',
            ])
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->with([
                    'quranTeacher:id,first_name,last_name',
                    'student:id,name',
                    'subscription:id,package_id',
                    'circle:id,name_ar,circle_code',
                    'individualCircle:id,name,circle_code,default_duration_minutes',
                ]);

            if ($user->isQuranTeacher()) {
                $query->where('quran_teacher_id', $user->id);
            } else {
                $query->where('student_id', $user->id);
            }

            return $query->get();
        });
    }

    /**
     * Get course sessions for user
     */
    private function getCourseSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $query = InteractiveCourseSession::where(function ($q) use ($startDate, $endDate) {
            $q->whereDate('scheduled_at', '>=', $startDate->toDateString())
              ->whereDate('scheduled_at', '<=', $endDate->toDateString());
        })
            ->with(['course']);

        if ($user->isAcademicTeacher()) {
            $query->whereHas('course', function ($q) use ($user) {
                $q->where('assigned_teacher_id', $user->academicTeacherProfile->id);
            });
        } else {
            // Check if user has a student profile before accessing it
            if ($user->studentProfile) {
                $query->whereHas('course.enrollments', function ($q) use ($user) {
                    $q->where('student_id', $user->studentProfile->id);
                });
            } else {
                // Return empty collection if user has no student profile
                return collect();
            }
        }

        return $query->get();
    }

    /**
     * Get circle sessions for user (optimized)
     */
    private function getCircleSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $cacheKey = "circle_sessions:{$user->id}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 600, function () use ($user, $startDate, $endDate) {
            if ($user->isQuranTeacher()) {
                return QuranSession::select([
                    'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
                    'quran_teacher_id', 'circle_id', 'session_type',
                ])
                    ->whereBetween('scheduled_at', [$startDate, $endDate])
                    ->where('session_type', 'group')
                    ->where('quran_teacher_id', $user->id)
                    ->with(['circle:id,name_ar,circle_code,enrolled_students'])
                    ->get();
            } else {
                // Get sessions for circles the user is enrolled in
                $userCircles = QuranCircle::whereHas('students', function ($q) use ($user) {
                    $q->where('student_id', $user->id);
                })->pluck('id');

                return QuranSession::select([
                    'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
                    'quran_teacher_id', 'circle_id', 'session_type',
                ])
                    ->whereBetween('scheduled_at', [$startDate, $endDate])
                    ->where('session_type', 'group')
                    ->whereIn('circle_id', $userCircles)
                    ->with([
                        'circle:id,name_ar,circle_code',
                        'quranTeacher:id,first_name,last_name',
                    ])
                    ->get();
            }
        });
    }

    /**
     * Format Quran sessions as calendar events
     */
    private function formatQuranSessions(Collection $sessions, User $user): Collection
    {
        return $sessions->map(function ($session) use ($user) {
            $perspective = $user->isQuranTeacher() ? 'teacher' : 'student';

            // Convert enum status to string value
            $status = $session->status;
            if ($status instanceof \BackedEnum) {
                $status = $status->value;
            } elseif (is_object($status)) {
                $status = $status->name ?? 'unknown';
            }

            return [
                'id' => 'quran_session_'.$session->id,
                'type' => 'session',
                'source' => 'quran_session',
                'title' => $this->getSessionTitle($session, $perspective),
                'description' => $this->getSessionDescription($session, $perspective),
                'start_time' => $session->scheduled_at,
                'end_time' => $session->scheduled_at->copy()->addMinutes($session->duration_minutes),
                'duration_minutes' => $session->duration_minutes,
                'status' => $status,
                'color' => $this->getSessionColor($session),
                'url' => $this->getSessionUrl($session),
                'meeting_url' => $session->google_meet_url ?? $session->meeting_link,
                'can_reschedule' => $session->can_reschedule,
                'can_cancel' => $session->can_cancel,
                'participants' => $this->getSessionParticipants($session),
                'metadata' => [
                    'session_id' => $session->id,
                    'session_type' => $session->session_type,
                    'teacher_id' => $session->quran_teacher_id,
                    'student_id' => $session->student_id,
                    'circle_id' => $session->circle_id,
                    'quran_subscription_id' => $session->quran_subscription_id,
                ],
            ];
        });
    }

    /**
     * Format course sessions as calendar events
     */
    private function formatCourseSessions(Collection $sessions, User $user): Collection
    {
        return $sessions->map(function ($session) use ($user) {
            $perspective = $user->isAcademicTeacher() ? 'teacher' : 'student';

            $courseTitle = $session->course?->title ?? 'دورة تعليمية';
            $sessionTitle = $session->title ?? 'جلسة';
            $participantsCount = $session->course?->enrollments?->count() ?? 0;

            // Convert enum status to string value
            $status = $session->status;
            if ($status instanceof \BackedEnum) {
                $status = $status->value;
            } elseif (is_object($status)) {
                $status = $status->name ?? 'unknown';
            }

            // Try to generate URL safely
            $sessionUrl = '#';
            try {
                if (Route::has('courses.session')) {
                    $sessionUrl = route('courses.session', $session->id);
                }
            } catch (\Exception $e) {
                // Keep default '#' if route doesn't exist
            }

            return [
                'id' => 'course_session_'.$session->id,
                'type' => 'course',
                'source' => 'course_session',
                'title' => $courseTitle.' - '.$sessionTitle,
                'description' => $session->description ?? '',
                'start_time' => $session->scheduled_at,
                'end_time' => $session->scheduled_at->copy()->addMinutes($session->duration_minutes),
                'duration_minutes' => $session->duration_minutes,
                'status' => $status,
                'color' => '#3B82F6', // Blue for courses
                'url' => $sessionUrl,
                'meeting_url' => $session->meeting_link ?? null,
                'participants' => $participantsCount,
                'metadata' => [
                    'session_id' => $session->id,
                    'course_id' => $session->course_id,
                    'teacher_id' => $session->course?->assigned_teacher_id ?? null,
                ],
            ];
        });
    }

    /**
     * Format circle sessions as calendar events
     */
    private function formatCircleSessions(Collection $sessions, User $user): Collection
    {
        return $sessions->map(function ($session) {
            $circleName = $session->circle?->name_ar ?? 'حلقة جماعية';
            $circleDescription = $session->circle?->description_ar ?? '';
            $participantsCount = $session->circle?->students?->count() ?? 0;

            // Convert enum status to string value
            $status = $session->status;
            if ($status instanceof \BackedEnum) {
                $status = $status->value;
            } elseif (is_object($status)) {
                $status = $status->name ?? 'unknown';
            }

            // Try to generate URL safely
            $circleUrl = '#';
            try {
                if ($session->circle_id && Route::has('circles.show')) {
                    $circleUrl = route('circles.show', $session->circle_id);
                }
            } catch (\Exception $e) {
                // Keep default '#' if route doesn't exist or fails
            }

            return [
                'id' => 'circle_session_'.$session->id,
                'type' => 'circle',
                'source' => 'circle_session',
                'title' => $circleName,
                'description' => 'حلقة جماعية - '.$circleDescription,
                'start_time' => $session->scheduled_at,
                'end_time' => $session->scheduled_at->copy()->addMinutes($session->duration_minutes),
                'duration_minutes' => $session->duration_minutes,
                'status' => $status,
                'color' => '#10B981', // Green for circles
                'url' => $circleUrl,
                'meeting_url' => $session->google_meet_url ?? $session->meeting_link ?? null,
                'participants' => $participantsCount,
                'metadata' => [
                    'session_id' => $session->id,
                    'circle_id' => $session->circle_id,
                    'teacher_id' => $session->quran_teacher_id,
                ],
            ];
        });
    }

    /**
     * Get break times and unavailable periods
     */
    private function getBreakTimes(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        // This would be extended to include user-defined break times
        // For now, return empty collection
        return collect();
    }

    /**
     * Check session conflicts
     */
    private function checkSessionConflicts(
        User $user,
        Carbon $startTime,
        Carbon $endTime,
        ?string $excludeType,
        ?int $excludeId
    ): Collection {

        $query = QuranSession::where(function ($q) use ($startTime, $endTime) {
            $q->whereBetween('scheduled_at', [$startTime, $endTime])
                ->orWhere(function ($subQuery) use ($startTime) {
                    $subQuery->where('scheduled_at', '<', $startTime)
                        ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$startTime]);
                });
        })->where('status', '!=', 'cancelled');

        if ($user->isQuranTeacher()) {
            $query->where('quran_teacher_id', $user->id);
        } else {
            $query->where('student_id', $user->id);
        }

        if ($excludeType === 'quran_session' && $excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }

    /**
     * Check course conflicts
     */
    private function checkCourseConflicts(
        User $user,
        Carbon $startTime,
        Carbon $endTime,
        ?string $excludeType,
        ?int $excludeId
    ): Collection {

        $query = InteractiveCourseSession::where(function ($q) use ($startTime, $endTime) {
            // Check for time conflicts using scheduled_at datetime
            $q->where('scheduled_at', '>=', $startTime)
                ->where('scheduled_at', '<=', $endTime);
        })->where('status', '!=', 'cancelled');

        if ($user->isAcademicTeacher()) {
            $query->whereHas('course', function ($q) use ($user) {
                $q->where('assigned_teacher_id', $user->academicTeacherProfile->id);
            });
        } else {
            $query->whereHas('course.enrollments', function ($q) use ($user) {
                $q->where('student_id', $user->studentProfile->id);
            });
        }

        if ($excludeType === 'course_session' && $excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }

    /**
     * Check circle conflicts for teachers
     */
    private function checkCircleConflicts(
        User $user,
        Carbon $startTime,
        Carbon $endTime,
        ?string $excludeType,
        ?int $excludeId
    ): Collection {

        if (! $user->isQuranTeacher()) {
            return collect();
        }

        $query = QuranSession::where('session_type', 'group')
            ->where('quran_teacher_id', $user->id)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('scheduled_at', [$startTime, $endTime])
                    ->orWhere(function ($subQuery) use ($startTime) {
                        $subQuery->where('scheduled_at', '<', $startTime)
                            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$startTime]);
                    });
            })->where('status', '!=', 'cancelled');

        if ($excludeType === 'circle_session' && $excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }

    /**
     * Helper methods
     */
    private function shouldIncludeEventType(string $type, array $filters): bool
    {
        return ! isset($filters['types']) || in_array($type, (array) $filters['types']);
    }

    private function generateCacheKey(User $user, Carbon $startDate, Carbon $endDate, array $filters): string
    {
        $filterHash = md5(serialize($filters));

        return "calendar:user:{$user->id}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}:{$filterHash}";
    }

    private function getSessionTitle($session, string $perspective): string
    {
        if ($session->session_type === 'group') {
            return $session->circle?->name_ar ?? 'حلقة جماعية';
        }

        if ($perspective === 'teacher') {
            return "جلسة مع " . ($session->student?->name ?? 'طالب غير محدد');
        } else {
            return "جلسة مع الأستاذ " . ($session->quranTeacher?->user?->name ?? 'معلم غير محدد');
        }
    }

    private function getSessionDescription($session, string $perspective): string
    {
        $description = '';

        if ($session->session_type === 'individual') {
            if ($perspective === 'teacher') {
                $studentName = $session->student?->name ?? 'طالب غير محدد';
                $description = "جلسة فردية مع الطالب {$studentName}";
            } else {
                $teacherName = $session->quranTeacher?->user?->name ?? 'معلم غير محدد';
                $description = "جلسة فردية مع الأستاذ {$teacherName}";
            }
        } else {
            $circleName = $session->circle?->name_ar ?? 'حلقة جماعية';
            $description = "حلقة جماعية - {$circleName}";
        }

        if ($session->current_surah) {
            $description .= ' - سورة '.$this->getSurahName($session->current_surah);
        }

        return $description;
    }

    private function getSessionColor($session): string
    {
        return match ($session->status) {
            'scheduled' => '#059669', // Green
            'ongoing' => '#DC2626', // Red
            'completed' => '#6B7280', // Gray
            'cancelled' => '#EF4444', // Light red
            'rescheduled' => '#F59E0B', // Orange
            default => '#6366F1' // Indigo
        };
    }

    private function getSessionUrl($session): string
    {
        // Return URL based on session type
        if ($session->session_type === 'group' && $session->circle_id) {
            try {
                return route('circles.show', $session->circle_id);
            } catch (\Exception $e) {
                return '#';
            }
        }

        // For individual sessions, return placeholder
        // Frontend teacher calendar has been removed - use Filament dashboard instead
        return '#';
    }

    private function getSessionParticipants($session): array
    {
        $participants = [];

        if ($session->quranTeacher && $session->quranTeacher->user) {
            $participants[] = [
                'name' => $session->quranTeacher->user->name ?? 'معلم غير محدد',
                'role' => 'teacher',
                'email' => $session->quranTeacher->user->email ?? '',
            ];
        }

        if ($session->student) {
            $participants[] = [
                'name' => $session->student->name ?? 'طالب غير محدد',
                'role' => 'student',
                'email' => $session->student->email ?? '',
            ];
        }

        if ($session->circle && $session->circle->students) {
            foreach ($session->circle->students as $student) {
                $participants[] = [
                    'name' => $student->name ?? 'طالب غير محدد',
                    'role' => 'student',
                    'email' => $student->email ?? '',
                ];
            }
        }

        return $participants;
    }

    private function getSurahName(int $surahNumber): string
    {
        $surahNames = [
            1 => 'الفاتحة', 2 => 'البقرة', 3 => 'آل عمران', 4 => 'النساء',
            5 => 'المائدة', 6 => 'الأنعام', 7 => 'الأعراف', 8 => 'الأنفال',
            // Add more as needed
        ];

        return $surahNames[$surahNumber] ?? "سورة رقم {$surahNumber}";
    }
}
