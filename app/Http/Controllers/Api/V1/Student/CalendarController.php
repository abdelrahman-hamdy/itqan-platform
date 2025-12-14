<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarController extends Controller
{
    use ApiResponses;

    /**
     * Get calendar events for the current month.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = Carbon::now();

        return $this->getCalendarData($request, $user->id, $now->year, $now->month);
    }

    /**
     * Get calendar events for a specific month.
     *
     * @param Request $request
     * @param int $year
     * @param int $month
     * @return JsonResponse
     */
    public function month(Request $request, int $year, int $month): JsonResponse
    {
        $user = $request->user();

        // Validate year and month
        if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
            return $this->error(
                __('Invalid date parameters.'),
                400,
                'INVALID_DATE'
            );
        }

        return $this->getCalendarData($request, $user->id, $year, $month);
    }

    /**
     * Get calendar data for a specific month.
     */
    protected function getCalendarData(Request $request, int $userId, int $year, int $month): JsonResponse
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $events = [];

        // Quran sessions
        $quranSessions = QuranSession::where('student_id', $userId)
            ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
            ->with(['quranTeacher'])
            ->get();

        foreach ($quranSessions as $session) {
            $events[] = $this->formatEvent($session, 'quran');
        }

        // Academic sessions
        $academicSessions = AcademicSession::where('student_id', $userId)
            ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
            ->with(['academicTeacher.user', 'academicSubscription'])
            ->get();

        foreach ($academicSessions as $session) {
            $events[] = $this->formatEvent($session, 'academic');
        }

        // Interactive course sessions
        $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
            ->with(['course.assignedTeacher.user'])
            ->get();

        foreach ($interactiveSessions as $session) {
            $events[] = $this->formatEvent($session, 'interactive');
        }

        // Sort events by date
        usort($events, function ($a, $b) {
            return strtotime($a['start']) <=> strtotime($b['start']);
        });

        // Group events by date for easier display
        $eventsByDate = [];
        foreach ($events as $event) {
            $date = Carbon::parse($event['start'])->toDateString();
            if (!isset($eventsByDate[$date])) {
                $eventsByDate[$date] = [];
            }
            $eventsByDate[$date][] = $event;
        }

        return $this->success([
            'year' => $year,
            'month' => $month,
            'month_name' => $startOfMonth->translatedFormat('F'),
            'start_date' => $startOfMonth->toDateString(),
            'end_date' => $endOfMonth->toDateString(),
            'events' => $events,
            'events_by_date' => $eventsByDate,
            'total_events' => count($events),
        ], __('Calendar data retrieved successfully'));
    }

    /**
     * Format a session as a calendar event.
     */
    protected function formatEvent($session, string $type): array
    {
        // All session types now use scheduled_at
        $start = $session->scheduled_at;

        $duration = $session->duration_minutes ?? 45;
        $end = $start?->copy()->addMinutes($duration);

        $title = match ($type) {
            'quran' => $session->title ?? 'جلسة قرآنية',
            'academic' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
            'interactive' => $session->title ?? $session->course?->title ?? 'جلسة تفاعلية',
            default => 'جلسة',
        };

        $teacher = match ($type) {
            'quran' => $session->quranTeacher, // QuranSession::quranTeacher returns User directly
            'academic' => $session->academicTeacher?->user,
            'interactive' => $session->course?->assignedTeacher?->user,
            default => null,
        };

        $color = match ($type) {
            'quran' => '#22c55e', // Green
            'academic' => '#3b82f6', // Blue
            'interactive' => '#f59e0b', // Amber
            default => '#6b7280', // Gray
        };

        return [
            'id' => $session->id,
            'type' => $type,
            'title' => $title,
            'start' => $start?->toISOString(),
            'end' => $end?->toISOString(),
            'date' => $start?->toDateString(),
            'time' => $start?->format('H:i'),
            'duration_minutes' => $duration,
            'status' => $session->status->value ?? $session->status,
            'color' => $color,
            'teacher_name' => $teacher?->name,
            'all_day' => false,
        ];
    }
}
