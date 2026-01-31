<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\ParentStudentRelationship;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Parent Calendar API Controller
 *
 * Provides calendar events for all linked children's sessions.
 */
class CalendarController extends Controller
{
    use ApiResponses;

    /**
     * Get calendar events for the current month.
     */
    public function index(Request $request): JsonResponse
    {
        $now = AcademyContextService::nowInAcademyTimezone();

        return $this->getCalendarData($request, $now->year, $now->month);
    }

    /**
     * Get calendar events for a specific month.
     */
    public function month(Request $request, int $year, int $month): JsonResponse
    {
        // Validate year and month
        if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
            return $this->error(
                __('Invalid date parameters.'),
                400,
                'INVALID_DATE'
            );
        }

        return $this->getCalendarData($request, $year, $month);
    }

    /**
     * Get calendar data for a specific month.
     */
    protected function getCalendarData(Request $request, int $year, int $month): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->notFound(__('Parent profile not found.'));
        }

        // Get all linked children
        $children = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with(['student.user'])
            ->get();

        if ($children->isEmpty()) {
            return $this->success([
                'year' => $year,
                'month' => $month,
                'month_name' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F'),
                'events' => [],
                'events_by_date' => [],
                'total_events' => 0,
            ], __('No children linked to this parent account.'));
        }

        // Collect child IDs
        $childUserIds = $children->pluck('student.user.id')->filter()->toArray();
        $studentProfileIds = $children->pluck('student.id')->toArray();

        $timezone = AcademyContextService::getTimezone();
        $startOfMonth = Carbon::createFromDate($year, $month, 1, $timezone)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $events = [];

        // Quran sessions for all children (student_id references User.id)
        $quranSessions = QuranSession::whereIn('student_id', $childUserIds)
            ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
            ->with(['quranTeacher', 'student'])
            ->get();

        foreach ($quranSessions as $session) {
            $events[] = $this->formatEvent($session, 'quran');
        }

        // Academic sessions for all children (student_id references User.id)
        $academicSessions = AcademicSession::whereIn('student_id', $childUserIds)
            ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
            ->with(['academicTeacher.user', 'academicSubscription', 'student'])
            ->get();

        foreach ($academicSessions as $session) {
            $events[] = $this->formatEvent($session, 'academic');
        }

        // Interactive course sessions for all children (enrollments.student_id references StudentProfile.id)
        $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($studentProfileIds) {
            $q->whereIn('student_id', $studentProfileIds);
        })
            ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
            ->with(['course.assignedTeacher.user', 'course.enrollments.student.user'])
            ->get();

        foreach ($interactiveSessions as $session) {
            // Get the child's name for this session
            $childNames = $session->course?->enrollments
                ->filter(fn ($e) => in_array($e->student_id, $studentProfileIds))
                ->map(fn ($e) => $e->student?->full_name)
                ->filter()
                ->unique()
                ->implode(', ');

            $events[] = $this->formatEvent($session, 'interactive', $childNames);
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
    protected function formatEvent($session, string $type, ?string $childName = null): array
    {
        // All session types now use scheduled_at
        // Convert to academy timezone for display
        $timezone = AcademyContextService::getTimezone();
        $start = $session->scheduled_at?->copy()->setTimezone($timezone);

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

        // Get child name from session
        $studentName = $childName ?? match ($type) {
            'quran', 'academic' => $session->student?->name ?? $session->student?->first_name,
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
            'time' => $start?->format('h:i A'),
            'duration_minutes' => $duration,
            'status' => $session->status->value ?? $session->status,
            'color' => $color,
            'teacher_name' => $teacher?->name,
            'child_name' => $studentName,
            'all_day' => false,
        ];
    }
}
