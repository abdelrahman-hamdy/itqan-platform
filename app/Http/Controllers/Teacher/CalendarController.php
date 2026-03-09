<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\AcademyContextService;
use App\Services\CalendarService;
use App\Services\Calendar\SessionStrategyFactory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

class CalendarController extends Controller
{
    public function __construct(
        private CalendarService $calendarService,
        private SessionStrategyFactory $strategyFactory,
    ) {
        $this->middleware('auth');
    }

    /**
     * Show the teacher calendar page.
     */
    public function index(Request $request, $subdomain = null): View
    {
        $user = Auth::user();
        $teacherType = $user->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';

        $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();
        $startDate = $date->copy()->startOfMonth()->startOfWeek();
        $endDate = $date->copy()->endOfMonth()->endOfWeek();

        $events = $this->calendarService->getUserCalendar($user, $startDate, $endDate);
        $stats = $this->calendarService->getCalendarStats($user, $date);

        // Get tab configuration for the scheduling panel
        $strategy = $this->strategyFactory->make($teacherType);
        $strategy->forUser($user);
        $tabConfig = $strategy->getTabConfiguration();

        // Transform to {key: label} for Alpine.js tabs
        $tabs = collect($tabConfig)->mapWithKeys(fn ($tab, $key) => [$key => $tab['label']])->all();

        return view('teacher.calendar.index', compact(
            'events',
            'stats',
            'user',
            'teacherType',
            'date',
            'tabs',
        ));
    }

    /**
     * Get calendar events via AJAX.
     */
    public function getEvents(Request $request, $subdomain = null): JsonResponse
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
        ]);

        $user = Auth::user();
        $startDate = Carbon::parse($request->start);
        $endDate = Carbon::parse($request->end);

        $events = $this->calendarService->getUserCalendar($user, $startDate, $endDate);

        return response()->json($events->values());
    }

    /**
     * Get schedulable items for a specific tab in the scheduling panel.
     */
    public function getSchedulableItems(Request $request, $subdomain = null): JsonResponse
    {
        $request->validate([
            'tab' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $teacherType = $user->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';

        $strategy = $this->strategyFactory->make($teacherType);
        $strategy->forUser($user);

        $tabs = $strategy->getTabConfiguration();
        $tab = $request->input('tab');

        if (! isset($tabs[$tab])) {
            return response()->json([
                'success' => false,
                'message' => __('calendar.invalid_tab'),
            ], 422);
        }

        $method = $tabs[$tab]['items_method'];

        if (! method_exists($strategy, $method)) {
            return response()->json([
                'success' => false,
                'message' => __('calendar.invalid_tab_method'),
            ], 422);
        }

        $items = $strategy->{$method}();

        return response()->json([
            'success' => true,
            'items' => $items->values(),
        ]);
    }

    /**
     * Create scheduled sessions from the scheduling panel.
     */
    public function createSchedule(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'item_type' => ['required', 'string', 'in:group,individual,trial,private_lesson,interactive_course'],
            'schedule_days' => ['required', 'array', 'min:1'],
            'schedule_days.*' => ['string', 'in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday'],
            'schedule_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'schedule_start_date' => ['required', 'date', 'after_or_equal:today'],
            'session_count' => ['required', 'integer', 'min:1'],
        ]);

        $user = Auth::user();
        $teacherType = $user->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';

        try {
            $strategy = $this->strategyFactory->make($teacherType);
            $strategy->forUser($user);

            $item = ['id' => $validated['item_id']];
            $validator = $strategy->getValidator($validated['item_type'], $item);

            // Run validation checks
            $dayResult = $validator->validateDaySelection($validated['schedule_days']);
            if (! $dayResult->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => $dayResult->getMessage(),
                ], 422);
            }

            $countResult = $validator->validateSessionCount($validated['session_count']);
            if (! $countResult->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => $countResult->getMessage(),
                ], 422);
            }

            $startDate = Carbon::parse($validated['schedule_start_date']);
            $weeksNeeded = (int) ceil($validated['session_count'] / max(count($validated['schedule_days']), 1));
            $dateResult = $validator->validateDateRange($startDate, $weeksNeeded);
            if (! $dateResult->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => $dateResult->getMessage(),
                ], 422);
            }

            $pacingResult = $validator->validateWeeklyPacing($validated['schedule_days'], $weeksNeeded);
            if (! $pacingResult->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => $pacingResult->getMessage(),
                ], 422);
            }

            $strategy->createSchedule($validated, $validator);

            return response()->json([
                'success' => true,
                'message' => __('calendar.schedule_created_successfully'),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check for time conflicts before scheduling.
     */
    public function checkConflicts(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
        ]);

        $user = Auth::user();

        $startTime = Carbon::parse($validated['date'])
            ->setTimeFromTimeString($validated['time']);
        $endTime = $startTime->copy()->addMinutes($validated['duration_minutes']);

        $conflicts = $this->calendarService->checkConflicts($user, $startTime, $endTime);

        return response()->json([
            'success' => true,
            'has_conflicts' => $conflicts->isNotEmpty(),
            'conflicts' => $conflicts->values(),
        ]);
    }
}
