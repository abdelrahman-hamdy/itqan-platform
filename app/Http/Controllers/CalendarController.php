<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Http\Requests\CheckCalendarConflictsRequest;
use App\Http\Requests\ExportCalendarRequest;
use App\Http\Requests\GetAvailableSlotsRequest;
use App\Http\Requests\GetCalendarEventsRequest;
use App\Http\Requests\GetCalendarStatsRequest;
use App\Http\Requests\GetWeeklyAvailabilityRequest;
use App\Services\AcademyContextService;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Enums\SessionStatus;

class CalendarController extends Controller
{
    use ApiResponses;

    private CalendarService $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * Show calendar page for authenticated user
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $view = $request->get('view', 'month');
        $date = $request->get('date')
            ? AcademyContextService::parseInAcademyTimezone($request->get('date'))
            : AcademyContextService::nowInAcademyTimezone();

        // Get initial calendar data
        $startDate = $this->getStartDate($date, $view);
        $endDate = $this->getEndDate($date, $view);
        
        $events = $this->calendarService->getUserCalendar($user, $startDate, $endDate);
        $stats = $this->calendarService->getCalendarStats($user, $date);

        // Determine view based on user role
        if ($user->isQuranTeacher() || $user->isAcademicTeacher()) {
            return view('teacher.calendar.index', compact(
                'events', 'stats', 'view', 'date', 'user'
            ));
        } else {
            return view('student.calendar.index', compact(
                'events', 'stats', 'view', 'date', 'user'
            ));
        }
    }

    /**
     * Get calendar events via API
     */
    public function getEvents(GetCalendarEventsRequest $request): JsonResponse
    {
        $user = Auth::user();

        $startDate = AcademyContextService::parseInAcademyTimezone($request->start);
        $endDate = AcademyContextService::parseInAcademyTimezone($request->end);
        
        $filters = array_filter([
            'types' => $request->types,
            'status' => $request->status,
            'search' => $request->search,
        ]);

        $events = $this->calendarService->getUserCalendar($user, $startDate, $endDate, $filters);

        return $this->success([
            'events' => $events,
            'count' => $events->count(),
        ]);
    }

    /**
     * Get available time slots for scheduling
     */
    public function getAvailableSlots(GetAvailableSlotsRequest $request): JsonResponse
    {
        $user = Auth::user();

        $date = AcademyContextService::parseInAcademyTimezone($request->date);
        $duration = $request->duration ?? 60;
        $workingHours = [
            $request->start_time ?? '09:00',
            $request->end_time ?? '17:00'
        ];

        $slots = $this->calendarService->getAvailableSlots($user, $date, $duration, $workingHours);

        return $this->success([
            'date' => $date->toDateString(),
            'slots' => $slots,
            'count' => $slots->count(),
        ]);
    }

    /**
     * Check for conflicts when scheduling
     */
    public function checkConflicts(CheckCalendarConflictsRequest $request): JsonResponse
    {
        $user = Auth::user();

        $startTime = AcademyContextService::parseInAcademyTimezone($request->start_time);
        $endTime = AcademyContextService::parseInAcademyTimezone($request->end_time);

        $conflicts = $this->calendarService->checkConflicts(
            $user,
            $startTime,
            $endTime,
            $request->exclude_type,
            $request->exclude_id
        );

        return $this->success([
            'has_conflicts' => $conflicts->isNotEmpty(),
            'conflicts' => $conflicts,
            'count' => $conflicts->count(),
        ]);
    }

    /**
     * Get teacher weekly availability (for teachers only)
     */
    public function getWeeklyAvailability(GetWeeklyAvailabilityRequest $request): JsonResponse
    {
        $user = Auth::user();

        $weekStart = $request->week_start
            ? AcademyContextService::parseInAcademyTimezone($request->week_start)->startOfWeek()
            : AcademyContextService::nowInAcademyTimezone()->startOfWeek();

        $availability = $this->calendarService->getTeacherWeeklyAvailability($user, $weekStart);

        return $this->success([
            'week_start' => $weekStart->toDateString(),
            'availability' => $availability,
        ]);
    }

    /**
     * Get calendar statistics
     */
    public function getStats(GetCalendarStatsRequest $request): JsonResponse
    {
        $user = Auth::user();

        $now = AcademyContextService::nowInAcademyTimezone();
        $month = $request->month
            ? Carbon::createFromFormat('Y-m', $request->month, AcademyContextService::getTimezone())
            : $now;

        $stats = $this->calendarService->getCalendarStats($user, $month);

        return $this->success([
            'month' => $month->format('Y-m'),
            'stats' => $stats,
        ]);
    }

    /**
     * Export calendar events
     */
    public function export(ExportCalendarRequest $request): Response
    {
        $user = Auth::user();

        $startDate = AcademyContextService::parseInAcademyTimezone($request->start);
        $endDate = AcademyContextService::parseInAcademyTimezone($request->end);
        $format = $request->get('format', 'ics');

        $events = $this->calendarService->getUserCalendar($user, $startDate, $endDate);

        if ($format === 'ics') {
            return $this->exportAsICS($events, $user);
        } else {
            return $this->exportAsCSV($events, $user);
        }
    }

    /**
     * Helper methods (private methods already have return types)
     */
    private function getStartDate(Carbon $date, string $view): Carbon
    {
        return match ($view) {
            'day' => $date->copy()->startOfDay(),
            'week' => $date->copy()->startOfWeek(),
            'month' => $date->copy()->startOfMonth()->startOfWeek(), // Include partial weeks
            default => $date->copy()->startOfMonth(),
        };
    }

    private function getEndDate(Carbon $date, string $view): Carbon
    {
        return match ($view) {
            'day' => $date->copy()->endOfDay(),
            'week' => $date->copy()->endOfWeek(),
            'month' => $date->copy()->endOfMonth()->endOfWeek(), // Include partial weeks
            default => $date->copy()->endOfMonth(),
        };
    }

    private function exportAsICS($events, $user): Response
    {
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Itqan Platform//Calendar//AR\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";

        foreach ($events as $event) {
            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "DTSTART:" . Carbon::parse($event['start_time'])->format('Ymd\THis\Z') . "\r\n";
            $ics .= "DTEND:" . Carbon::parse($event['end_time'])->format('Ymd\THis\Z') . "\r\n";
            $ics .= "SUMMARY:" . $event['title'] . "\r\n";
            $ics .= "DESCRIPTION:" . ($event['description'] ?? '') . "\r\n";
            $ics .= "UID:" . $event['id'] . "@itqan.com\r\n";
            $ics .= "DTSTAMP:" . AcademyContextService::nowInAcademyTimezone()->utc()->format('Ymd\THis\Z') . "\r\n";
            if (isset($event['meeting_url'])) {
                $ics .= "URL:" . $event['meeting_url'] . "\r\n";
            }
            $ics .= "END:VEVENT\r\n";
        }

        $ics .= "END:VCALENDAR\r\n";

        return response($ics)
            ->header('Content-Type', 'text/calendar')
            ->header('Content-Disposition', 'attachment; filename="calendar.ics"');
    }

    private function exportAsCSV($events, $user): Response
    {
        $csv = "التاريخ,الوقت,العنوان,النوع,الحالة,المدة,رابط الاجتماع\n";
        
        foreach ($events as $event) {
            $startTime = Carbon::parse($event['start_time']);
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%d دقيقة,%s\n",
                $startTime->format('Y-m-d'),
                $startTime->format('H:i'),
                $event['title'],
                $event['type'],
                $event['status'],
                $event['duration_minutes'],
                $event['meeting_url'] ?? ''
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="calendar.csv"');
    }
}