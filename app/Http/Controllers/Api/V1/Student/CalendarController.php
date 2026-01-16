<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarController extends Controller
{
    use ApiResponses;

    /**
     * Get calendar events for the current month.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = AcademyContextService::nowInAcademyTimezone();

        return $this->getCalendarData($request, $user, $now->year, $now->month);
    }

    /**
     * Get calendar events for a specific month.
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

        return $this->getCalendarData($request, $user, $year, $month);
    }

    /**
     * Get calendar data for a specific month.
     *
     * @param  \App\Models\User  $user
     */
    protected function getCalendarData(Request $request, $user, int $year, int $month): JsonResponse
    {
        $timezone = AcademyContextService::getTimezone();
        $startOfMonth = Carbon::createFromDate($year, $month, 1, $timezone)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $userId = $user->id;
        $studentProfileId = $user->studentProfile?->id;

        $events = [];

        // Quran sessions (student_id references User.id)
        $quranSessions = QuranSession::where('student_id', $userId)
            ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
            ->with(['quranTeacher'])
            ->get();

        foreach ($quranSessions as $session) {
            $events[] = $this->formatEvent($session, 'quran');
        }

        // Academic sessions (student_id references User.id)
        $academicSessions = AcademicSession::where('student_id', $userId)
            ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
            ->with(['academicTeacher.user', 'academicSubscription'])
            ->get();

        foreach ($academicSessions as $session) {
            $events[] = $this->formatEvent($session, 'academic');
        }

        // Interactive course sessions (enrollments.student_id references StudentProfile.id)
        if ($studentProfileId) {
            $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($studentProfileId) {
                $q->where('student_id', $studentProfileId);
            })
                ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
                ->with(['course.assignedTeacher.user'])
                ->get();
        } else {
            $interactiveSessions = collect();
        }

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
            $date = AcademyContextService::parseInAcademyTimezone($event['start'])->toDateString();
            if (! isset($eventsByDate[$date])) {
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
