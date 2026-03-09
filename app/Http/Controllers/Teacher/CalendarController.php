<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Constants\DefaultAcademy;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use App\Services\AcademyContextService;
use App\Services\CalendarService;
use App\Services\Calendar\SessionStrategyFactory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
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

    /**
     * Get scheduling recommendations for a specific item.
     */
    public function getRecommendations(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'item_type' => ['required', 'string', 'in:group,individual,trial,private_lesson,interactive_course'],
        ]);

        $user = Auth::user();
        $teacherType = $user->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';

        try {
            $strategy = $this->strategyFactory->make($teacherType);
            $strategy->forUser($user);

            $item = ['id' => $validated['item_id']];
            $validator = $strategy->getValidator($validated['item_type'], $item);
            $recommendations = $validator->getRecommendations();

            return response()->json([
                'success' => true,
                'recommendations' => $recommendations,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reschedule a session via drag & drop on the calendar.
     */
    public function rescheduleEvent(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'in:quran_session,circle_session,course_session,academic_session'],
            'session_id' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $user = Auth::user();
        $sessionId = $validated['session_id'];
        $source = $validated['source'];

        // Resolve session model based on source type
        $session = match ($source) {
            'quran_session', 'circle_session' => QuranSession::where('id', $sessionId)
                ->where('quran_teacher_id', $user->id)
                ->first(),
            'academic_session' => AcademicSession::where('id', $sessionId)
                ->whereHas('academicTeacher', fn ($q) => $q->where('user_id', $user->id))
                ->first(),
            'course_session' => InteractiveCourseSession::where('id', $sessionId)
                ->whereHas('course.assignedTeacher', fn ($q) => $q->where('user_id', $user->id))
                ->first(),
            default => null,
        };

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => __('teacher.calendar.session_not_found'),
            ], 404);
        }

        // Only allow rescheduling scheduled/ready sessions
        $status = $session->status instanceof SessionStatus
            ? $session->status
            : SessionStatus::tryFrom($session->status);

        if (! in_array($status, [SessionStatus::SCHEDULED, SessionStatus::READY])) {
            return response()->json([
                'success' => false,
                'message' => __('teacher.calendar.cannot_reschedule_status'),
            ], 422);
        }

        $oldScheduledAt = $session->scheduled_at;
        $newScheduledAt = Carbon::parse($validated['scheduled_at']);

        $session->update([
            'scheduled_at' => $newScheduledAt,
            'rescheduled_from' => $oldScheduledAt,
            'rescheduled_to' => $newScheduledAt,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('teacher.calendar.reschedule_success'),
        ]);
    }

    /**
     * Get detailed session info for the event modal.
     */
    public function getSessionDetail(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'in:quran_session,circle_session,course_session,academic_session'],
            'session_id' => ['required', 'integer'],
        ]);

        $user = Auth::user();
        $session = $this->resolveSessionWithOwnership($validated['source'], $validated['session_id'], $user);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => __('teacher.calendar.session_not_found'),
            ], 404);
        }

        $source = $validated['source'];
        $status = $session->status instanceof SessionStatus ? $session->status : SessionStatus::tryFrom($session->status);
        $canEdit = in_array($status, [SessionStatus::SCHEDULED, SessionStatus::READY]);

        $data = [
            'id' => $session->id,
            'source' => $source,
            'title' => $this->getSessionModalTitle($session, $source),
            'status' => $status?->value ?? 'unknown',
            'status_label' => $status?->label() ?? __('teacher.calendar.unknown'),
            'scheduled_at' => $session->scheduled_at?->toIso8601String(),
            'scheduled_at_formatted' => $session->scheduled_at
                ? AcademyContextService::toAcademyTimezone($session->scheduled_at)->format('Y/m/d H:i')
                : null,
            'duration_minutes' => $session->duration_minutes,
            'meeting_link' => $session->meeting_link ?? $session->google_meet_url ?? null,
            'teacher_notes' => $session->teacher_notes ?? null,
            'can_edit' => $canEdit,
            'detail_url' => $this->getTeacherDetailUrl($session, $source, $subdomain),
        ];

        // Source-specific fields
        if (in_array($source, ['quran_session', 'circle_session'])) {
            $data['student_name'] = $session->student?->name ?? null;
            $data['circle_name'] = $session->circle?->name ?? null;
            $data['session_type'] = $session->session_type;

            // Homework info
            $homework = QuranSessionHomework::where('session_id', $session->id)->where('is_active', true)->first();
            $data['has_homework'] = (bool) $homework;
            $data['homework_type'] = 'quran';
            $data['homework_data'] = $homework ? [
                'has_new_memorization' => (bool) $homework->has_new_memorization,
                'has_review' => (bool) $homework->has_review,
                'has_comprehensive_review' => (bool) $homework->has_comprehensive_review,
                'new_memorization_surah' => $homework->new_memorization_surah,
                'new_memorization_pages' => $homework->new_memorization_pages,
                'review_surah' => $homework->review_surah,
                'review_pages' => $homework->review_pages,
                'comprehensive_review_surahs' => $homework->comprehensive_review_surahs,
                'additional_instructions' => $homework->additional_instructions,
            ] : null;
        } elseif ($source === 'academic_session') {
            $data['student_name'] = $session->student?->name ?? null;
            $data['subject_name'] = $session->academicSubscription?->subject_name ?? null;
            $data['has_homework'] = ! empty($session->homework_description);
            $data['homework_type'] = 'academic';
            $data['homework_data'] = $data['has_homework'] ? [
                'description' => $session->homework_description,
            ] : null;
        } elseif ($source === 'course_session') {
            $data['course_title'] = $session->course?->title ?? null;
            $data['subject_name'] = $session->course?->subject?->name ?? null;
            $data['enrolled_count'] = $session->course?->enrollments?->count() ?? 0;
            $data['has_homework'] = false;
            $data['homework_type'] = 'academic';
        }

        return response()->json(['success' => true, 'session' => $data]);
    }

    /**
     * Update a session (reschedule, notes, duration) from the calendar modal.
     */
    public function updateSession(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'in:quran_session,circle_session,course_session,academic_session'],
            'session_id' => ['required', 'integer'],
            'scheduled_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'in:30,45,60,90,120'],
            'teacher_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();
        $session = $this->resolveSessionWithOwnership($validated['source'], $validated['session_id'], $user);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => __('teacher.calendar.session_not_found'),
            ], 404);
        }

        $status = $session->status instanceof SessionStatus ? $session->status : SessionStatus::tryFrom($session->status);
        if (! in_array($status, [SessionStatus::SCHEDULED, SessionStatus::READY])) {
            return response()->json([
                'success' => false,
                'message' => __('teacher.calendar.cannot_edit_status'),
            ], 422);
        }

        $updateData = [];

        if (isset($validated['scheduled_at'])) {
            $updateData['scheduled_at'] = Carbon::parse($validated['scheduled_at']);
        }
        if (isset($validated['duration_minutes'])) {
            $updateData['duration_minutes'] = $validated['duration_minutes'];
        }
        if (array_key_exists('teacher_notes', $validated)) {
            $updateData['teacher_notes'] = $validated['teacher_notes'];
        }

        if (! empty($updateData)) {
            $session->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => __('teacher.calendar.session_updated'),
        ]);
    }

    /**
     * Save Quran homework from the calendar modal.
     */
    public function saveQuranHomework(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer'],
            'has_new_memorization' => ['boolean'],
            'has_review' => ['boolean'],
            'has_comprehensive_review' => ['boolean'],
            'new_memorization_surah' => ['nullable', 'string'],
            'new_memorization_pages' => ['nullable', 'integer', 'min:1'],
            'review_surah' => ['nullable', 'string'],
            'review_pages' => ['nullable', 'integer', 'min:1'],
            'comprehensive_review_surahs' => ['nullable', 'array'],
            'comprehensive_review_surahs.*' => ['string'],
            'additional_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = Auth::user();
        $session = QuranSession::where('id', $validated['session_id'])
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => __('teacher.calendar.session_not_found'),
            ], 404);
        }

        QuranSessionHomework::updateOrCreate(
            ['session_id' => $session->id],
            [
                'created_by' => $user->id,
                'has_new_memorization' => $validated['has_new_memorization'] ?? false,
                'has_review' => $validated['has_review'] ?? false,
                'has_comprehensive_review' => $validated['has_comprehensive_review'] ?? false,
                'new_memorization_surah' => $validated['new_memorization_surah'] ?? null,
                'new_memorization_pages' => $validated['new_memorization_pages'] ?? null,
                'review_surah' => $validated['review_surah'] ?? null,
                'review_pages' => $validated['review_pages'] ?? null,
                'comprehensive_review_surahs' => $validated['comprehensive_review_surahs'] ?? null,
                'additional_instructions' => $validated['additional_instructions'] ?? null,
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => __('teacher.calendar.homework_saved'),
        ]);
    }

    /**
     * Save Academic homework from the calendar modal.
     */
    public function saveAcademicHomework(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer'],
            'source' => ['required', 'string', 'in:academic_session,course_session'],
            'homework_description' => ['required', 'string', 'max:5000'],
        ]);

        $user = Auth::user();
        $session = $this->resolveSessionWithOwnership($validated['source'], $validated['session_id'], $user);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => __('teacher.calendar.session_not_found'),
            ], 404);
        }

        $session->update([
            'homework_description' => $validated['homework_description'],
        ]);

        return response()->json([
            'success' => true,
            'message' => __('teacher.calendar.homework_saved'),
        ]);
    }

    /**
     * Resolve a session model with teacher ownership verification.
     */
    private function resolveSessionWithOwnership(string $source, int $sessionId, $user)
    {
        return match ($source) {
            'quran_session', 'circle_session' => QuranSession::where('id', $sessionId)
                ->where('quran_teacher_id', $user->id)
                ->first(),
            'academic_session' => AcademicSession::where('id', $sessionId)
                ->whereHas('academicTeacher', fn ($q) => $q->where('user_id', $user->id))
                ->first(),
            'course_session' => InteractiveCourseSession::where('id', $sessionId)
                ->whereHas('course.assignedTeacher', fn ($q) => $q->where('user_id', $user->id))
                ->first(),
            default => null,
        };
    }

    /**
     * Get modal title for a session.
     */
    private function getSessionModalTitle($session, string $source): string
    {
        return match ($source) {
            'quran_session' => $session->student?->name
                ? __('calendar.formatting.session_with_student', ['name' => $session->student->name])
                : __('calendar.formatting.session'),
            'circle_session' => $session->circle?->name ?? __('calendar.formatting.group_circle'),
            'academic_session' => $session->student?->name
                ? __('calendar.formatting.session_with_student', ['name' => $session->student->name])
                : __('calendar.formatting.session'),
            'course_session' => ($session->course?->title ?? __('calendar.formatting.educational_course'))
                . ' - ' . ($session->title ?? __('calendar.formatting.session')),
            default => __('calendar.formatting.session'),
        };
    }

    /**
     * Get the teacher-facing detail URL for a session.
     */
    private function getTeacherDetailUrl($session, string $source, ?string $subdomain): string
    {
        $subdomain = $subdomain ?? auth()->user()?->academy?->subdomain ?? DefaultAcademy::subdomain();

        try {
            return match ($source) {
                'quran_session', 'circle_session' => Route::has('teacher.sessions.show')
                    ? route('teacher.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $session->id])
                    : '#',
                'academic_session' => Route::has('teacher.academic-sessions.show')
                    ? route('teacher.academic-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id])
                    : '#',
                'course_session' => Route::has('teacher.interactive-sessions.show')
                    ? route('teacher.interactive-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id])
                    : '#',
                default => '#',
            };
        } catch (Exception $e) {
            return '#';
        }
    }
}
