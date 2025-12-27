<?php

namespace App\Http\Controllers;

use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Enums\SessionStatus;

class CalendarController extends Controller
{
    private CalendarService $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * Show calendar page for authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $view = $request->get('view', 'month');
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();

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
    public function getEvents(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'types' => 'array',
            'status' => 'array',
            'search' => 'string|max:255',
        ]);

        $startDate = Carbon::parse($request->start);
        $endDate = Carbon::parse($request->end);
        
        $filters = array_filter([
            'types' => $request->types,
            'status' => $request->status,
            'search' => $request->search,
        ]);

        $events = $this->calendarService->getUserCalendar($user, $startDate, $endDate, $filters);

        return response()->json([
            'success' => true,
            'events' => $events,
            'count' => $events->count(),
        ]);
    }

    /**
     * Get available time slots for scheduling
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $request->validate([
            'date' => 'required|date',
            'duration' => 'integer|min:15|max:240',
            'start_time' => 'string|date_format:H:i',
            'end_time' => 'string|date_format:H:i',
        ]);

        $date = Carbon::parse($request->date);
        $duration = $request->duration ?? 60;
        $workingHours = [
            $request->start_time ?? '09:00',
            $request->end_time ?? '17:00'
        ];

        $slots = $this->calendarService->getAvailableSlots($user, $date, $duration, $workingHours);

        return response()->json([
            'success' => true,
            'date' => $date->toDateString(),
            'slots' => $slots,
            'count' => $slots->count(),
        ]);
    }

    /**
     * Check for conflicts when scheduling
     */
    public function checkConflicts(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'exclude_type' => 'string',
            'exclude_id' => 'integer',
        ]);

        $startTime = Carbon::parse($request->start_time);
        $endTime = Carbon::parse($request->end_time);

        $conflicts = $this->calendarService->checkConflicts(
            $user,
            $startTime,
            $endTime,
            $request->exclude_type,
            $request->exclude_id
        );

        return response()->json([
            'success' => true,
            'has_conflicts' => $conflicts->isNotEmpty(),
            'conflicts' => $conflicts,
            'count' => $conflicts->count(),
        ]);
    }

    /**
     * Get teacher weekly availability (for teachers only)
     */
    public function getWeeklyAvailability(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() && !$user->isAcademicTeacher()) {
            return response()->json(['error' => 'غير مسموح'], 403);
        }

        $request->validate([
            'week_start' => 'date',
        ]);

        $weekStart = $request->week_start ? 
            Carbon::parse($request->week_start)->startOfWeek() : 
            now()->startOfWeek();

        $availability = $this->calendarService->getTeacherWeeklyAvailability($user, $weekStart);

        return response()->json([
            'success' => true,
            'week_start' => $weekStart->toDateString(),
            'availability' => $availability,
        ]);
    }

    /**
     * Get calendar statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $request->validate([
            'month' => 'date_format:Y-m',
        ]);

        $month = $request->month ? 
            Carbon::createFromFormat('Y-m', $request->month) : 
            now();

        $stats = $this->calendarService->getCalendarStats($user, $month);

        return response()->json([
            'success' => true,
            'month' => $month->format('Y-m'),
            'stats' => $stats,
        ]);
    }

    /**
     * Export calendar events
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
            'format' => 'in:ics,csv',
        ]);

        $startDate = Carbon::parse($request->start);
        $endDate = Carbon::parse($request->end);
        $format = $request->get('format', 'ics');

        $events = $this->calendarService->getUserCalendar($user, $startDate, $endDate);

        if ($format === 'ics') {
            return $this->exportAsICS($events, $user);
        } else {
            return $this->exportAsCSV($events, $user);
        }
    }

    /**
     * Helper methods
     */
    protected function getStartDate(Carbon $date, string $view): Carbon
    {
        return match ($view) {
            'day' => $date->copy()->startOfDay(),
            'week' => $date->copy()->startOfWeek(),
            'month' => $date->copy()->startOfMonth()->startOfWeek(), // Include partial weeks
            default => $date->copy()->startOfMonth(),
        };
    }

    protected function getEndDate(Carbon $date, string $view): Carbon
    {
        return match ($view) {
            'day' => $date->copy()->endOfDay(),
            'week' => $date->copy()->endOfWeek(),
            'month' => $date->copy()->endOfMonth()->endOfWeek(), // Include partial weeks
            default => $date->copy()->endOfMonth(),
        };
    }

    protected function exportAsICS($events, $user)
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
            $ics .= "DTSTAMP:" . now()->format('Ymd\THis\Z') . "\r\n";
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

    protected function exportAsCSV($events, $user)
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