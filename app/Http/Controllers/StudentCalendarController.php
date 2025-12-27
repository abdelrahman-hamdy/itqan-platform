<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Services\CalendarService;
use App\Services\AcademyContextService;
use App\Models\Academy;
use Carbon\Carbon;
use App\Enums\SessionStatus;

class StudentCalendarController extends Controller
{
    private CalendarService $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
        $this->middleware('auth');
    }

    /**
     * Show the student calendar page
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        // Check if user is a student
        if (!$user->isStudent()) {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        $view = $request->get('view', 'month');
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();

        // Get calendar data
        $startDate = $this->getStartDate($date, $view);
        $endDate = $this->getEndDate($date, $view);
        
        $events = $this->calendarService->getUserCalendar($user, $startDate, $endDate);
        $stats = $this->calendarService->getCalendarStats($user, $date);

        return view('student.calendar.index', compact(
            'events', 'stats', 'view', 'date', 'user'
        ));
    }

    /**
     * Get calendar events via AJAX
     */
    public function getEvents(Request $request): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        $startDate = Carbon::parse($request->start);
        $endDate = Carbon::parse($request->end);

        // Get events from CalendarService
        $events = $this->calendarService->getUserCalendar($user, $startDate, $endDate);

        // Return events as-is (CalendarService already provides the correct format)
        return response()->json($events->values());
    }

    /**
     * Get current academy
     */
    private function getCurrentAcademy(): Academy
    {
        return AcademyContextService::getCurrentAcademy();
    }

    /**
     * Helper methods for date ranges
     */
    private function getStartDate(Carbon $date, string $view): Carbon
    {
        return match ($view) {
            'day' => $date->copy()->startOfDay(),
            'week' => $date->copy()->startOfWeek(),
            'month' => $date->copy()->startOfMonth()->startOfWeek(),
            default => $date->copy()->startOfMonth(),
        };
    }

    private function getEndDate(Carbon $date, string $view): Carbon
    {
        return match ($view) {
            'day' => $date->copy()->endOfDay(),
            'week' => $date->copy()->endOfWeek(),
            'month' => $date->copy()->endOfMonth()->endOfWeek(),
            default => $date->copy()->endOfMonth(),
        };
    }
}