<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ChildSelectionMiddleware;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\SessionStatus;

/**
 * Parent Calendar Controller
 *
 * Handles viewing of children's calendar events.
 * Uses session-based child selection via middleware.
 * Returns student view with parent layout for consistent design.
 */
class ParentCalendarController extends Controller
{
    private CalendarService $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;

        // Enforce read-only access
        $this->middleware('parent.readonly');
    }

    /**
     * Show the parent calendar page
     *
     * Uses the student calendar view with parent layout.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        // Get child User IDs from middleware (session-based selection)
        // Note: Must use getChildUserIds() not getChildIds() since we need User.id for CalendarService
        $childUserIds = ChildSelectionMiddleware::getChildUserIds();

        $view = $request->get('view', 'month');
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();

        // Get calendar data for all children
        $startDate = $this->getStartDate($date, $view);
        $endDate = $this->getEndDate($date, $view);

        // Get events for all children
        $events = $this->getChildrenEvents($childUserIds, $startDate, $endDate);
        $stats = $this->getChildrenStats($childUserIds, $date);

        // Return student view with parent layout
        return view('student.calendar.index', [
            'events' => $events,
            'stats' => $stats,
            'view' => $view,
            'date' => $date,
            'user' => $user,
            'layout' => 'parent',
        ]);
    }

    /**
     * Get calendar events via AJAX for children
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEvents(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        // Get child User IDs from middleware (session-based selection)
        // Note: Must use getChildUserIds() not getChildIds() since we need User.id for CalendarService
        $childUserIds = ChildSelectionMiddleware::getChildUserIds();

        $startDate = Carbon::parse($request->start);
        $endDate = Carbon::parse($request->end);

        // Get events for all children
        $events = $this->getChildrenEvents($childUserIds, $startDate, $endDate);

        return response()->json($events->values());
    }

    /**
     * Get events for multiple children
     * Optimized to avoid N+1 by eager loading users in a single query.
     *
     * @param array $childUserIds
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Support\Collection
     */
    private function getChildrenEvents(array $childUserIds, Carbon $startDate, Carbon $endDate)
    {
        $allEvents = collect();

        // Eager load all child users at once to avoid N+1
        $childUsers = \App\Models\User::whereIn('id', $childUserIds)->get()->keyBy('id');

        foreach ($childUserIds as $childUserId) {
            $childUser = $childUsers->get($childUserId);
            if ($childUser) {
                $events = $this->calendarService->getUserCalendar($childUser, $startDate, $endDate);
                // Add child name to each event for identification
                $events = $events->map(function ($event) use ($childUser) {
                    $event['child_name'] = $childUser->name;
                    return $event;
                });
                $allEvents = $allEvents->merge($events);
            }
        }

        // Sort by start time
        return $allEvents->sortBy('start_time');
    }

    /**
     * Get aggregated stats for children
     * Optimized to avoid N+1 by eager loading users in a single query.
     *
     * @param array $childUserIds
     * @param Carbon $date
     * @return array
     */
    private function getChildrenStats(array $childUserIds, Carbon $date): array
    {
        $totalStats = [
            'total' => 0,
            'scheduled' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        // Eager load all child users at once to avoid N+1
        $childUsers = \App\Models\User::whereIn('id', $childUserIds)->get();

        foreach ($childUsers as $childUser) {
            $stats = $this->calendarService->getCalendarStats($childUser, $date);
            // CalendarService returns 'total_events' and 'by_status' array
            $totalStats['total'] += $stats['total_events'] ?? 0;
            $byStatus = $stats['by_status'] ?? [];
            $totalStats['scheduled'] += $byStatus[SessionStatus::SCHEDULED->value] ?? 0;
            $totalStats['completed'] += $byStatus[SessionStatus::COMPLETED->value] ?? 0;
            $totalStats['cancelled'] += $byStatus[SessionStatus::CANCELLED->value] ?? 0;
        }

        return $totalStats;
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
