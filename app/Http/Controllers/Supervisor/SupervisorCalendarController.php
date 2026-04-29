<?php

namespace App\Http\Controllers\Supervisor;

use App\Constants\DefaultAcademy;
use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use App\Http\Controllers\Concerns\RespondsWithScheduleResult;
use App\Http\Controllers\SessionsMonitoringController;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use App\Models\User;
use App\Services\AcademyContextService;
use App\Services\Calendar\SessionStrategyFactory;
use App\Services\CalendarService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class SupervisorCalendarController extends BaseSupervisorWebController
{
    use RespondsWithScheduleResult;

    public function __construct(
        private CalendarService $calendarService,
        private SessionStrategyFactory $strategyFactory,
    ) {
        parent::__construct();
    }

    public function index(Request $request, $subdomain = null): View
    {
        $allTeacherIds = $this->getAllAssignedTeacherIds();
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        $teachers = collect();

        if (! empty($quranTeacherIds)) {
            $quranTeachers = User::whereIn('id', $quranTeacherIds)
                ->with('quranTeacherProfile')
                ->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'gender' => $u->quranTeacherProfile?->gender ?? $u->gender ?? '',
                    'type' => 'quran',
                    'type_label' => __('supervisor.teachers.teacher_type_quran'),
                ]);
            $teachers = $teachers->merge($quranTeachers);
        }

        if (! empty($academicTeacherIds)) {
            $academicTeachers = User::whereIn('id', $academicTeacherIds)
                ->with('academicTeacherProfile')
                ->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'gender' => $u->academicTeacherProfile?->gender ?? $u->gender ?? '',
                    'type' => 'academic',
                    'type_label' => __('supervisor.teachers.teacher_type_academic'),
                ]);
            $teachers = $teachers->merge($academicTeachers);
        }

        $selectedTeacherId = $request->teacher_id;
        $selectedTeacher = $selectedTeacherId ? User::find($selectedTeacherId) : null;

        // When a teacher is selected, compute stats, type, and tabs for the scheduling panel
        $stats = null;
        $teacherType = null;
        $tabs = [];
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();

        if ($selectedTeacher && in_array((int) $selectedTeacherId, $allTeacherIds)) {
            $stats = $this->calendarService->getCalendarStats($selectedTeacher, $date);

            $isQuran = $teachers->where('type', 'quran')->pluck('id')->contains($selectedTeacher->id);
            $teacherType = $isQuran ? 'quran_teacher' : 'academic_teacher';

            try {
                $strategy = $this->strategyFactory->make($teacherType);
                $strategy->forUser($selectedTeacher);
                $tabConfig = $strategy->getTabConfiguration();
                $tabs = collect($tabConfig)->mapWithKeys(fn ($tab, $key) => [$key => $tab['label']])->all();
            } catch (Exception $e) {
                // Strategy may not exist for this teacher type — no tabs
            }
        }

        return view('supervisor.calendar.index', compact(
            'teachers',
            'selectedTeacherId',
            'selectedTeacher',
            'stats',
            'teacherType',
            'tabs',
            'date',
        ));
    }

    public function getEvents(Request $request, $subdomain = null): JsonResponse
    {
        $teacher = $this->verifyTeacherAccess($request);

        $events = $this->calendarService->getUserCalendar(
            $teacher,
            Carbon::parse($request->input('start')),
            Carbon::parse($request->input('end'))
        );

        return response()->json($events->values());
    }

    public function getSchedulableItems(Request $request, $subdomain = null): JsonResponse
    {
        $request->validate([
            'tab' => ['required', 'string'],
        ]);

        $teacher = $this->verifyTeacherAccess($request);
        $teacherType = $teacher->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';

        $strategy = $this->strategyFactory->make($teacherType);
        $strategy->forUser($teacher);

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

    public function getRecommendations(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'item_type' => ['required', 'string', 'in:group,individual,trial,private_lesson,interactive_course'],
        ]);

        $teacher = $this->verifyTeacherAccess($request);
        $teacherType = $teacher->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';

        try {
            $strategy = $this->strategyFactory->make($teacherType);
            $strategy->forUser($teacher);

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

    public function createSchedule(Request $request, $subdomain = null): JsonResponse
    {
        // Direct file write to bypass any caching
        file_put_contents(storage_path('logs/schedule_debug.log'),
            date('Y-m-d H:i:s') . ' createSchedule ENTRY: ' . json_encode($request->all()) . "\n",
            FILE_APPEND
        );
        \Log::info('createSchedule: ENTRY', [
            'all_input' => $request->all(),
            'user_id' => auth()->id(),
        ]);

        // Use academy timezone for "today" so dates are not rejected due to UTC server clock
        $todayInAcademy = Carbon::now(AcademyContextService::getTimezone())->toDateString();

        // For trial items, schedule_days is auto-derived from the selected date on the frontend
        $itemType = $request->input('item_type');
        $scheduleDaysRules = $itemType === 'trial'
            ? ['array']
            : ['required', 'array', 'min:1'];

        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'item_type' => ['required', 'string', 'in:group,individual,trial,private_lesson,interactive_course'],
            'schedule_days' => $scheduleDaysRules,
            'schedule_days.*' => ['string', 'in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday'],
            'schedule_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'schedule_start_date' => ['required', 'date', "after_or_equal:{$todayInAcademy}"],
            'session_count' => ['required', 'integer', 'min:1'],
        ]);

        $teacher = $this->verifyTeacherAccess($request);
        $teacherType = $teacher->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';

        try {
            $strategy = $this->strategyFactory->make($teacherType);
            $strategy->forUser($teacher);

            $item = ['id' => $validated['item_id']];
            $validator = $strategy->getValidator($validated['item_type'], $item);

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

            \Log::info('SupervisorCalendarController::createSchedule - calling strategy', [
                'validated' => $validated,
                'teacher_id' => $teacher->id,
                'teacher_type' => $teacherType,
            ]);

            $createdCount = $strategy->createSchedule($validated, $validator);

            return $this->scheduleResultResponse($createdCount, $validated['session_count']);
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
     * Remove all future scheduled sessions for a given entity.
     */
    public function removeScheduledSessions(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'item_type' => ['required', 'string', 'in:group,individual,trial,private_lesson,interactive_course'],
        ]);

        $teacher = $this->verifyTeacherAccess($request);
        $teacherType = $teacher->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';

        try {
            $strategy = $this->strategyFactory->make($teacherType);
            $strategy->forUser($teacher);

            $removedCount = $strategy->removeScheduledSessions(
                $validated['item_type'],
                $validated['item_id']
            );

            return response()->json([
                'success' => true,
                'message' => __('teacher.calendar.sessions_removed_success', ['count' => $removedCount]),
                'deleted_count' => $removedCount,
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

    public function checkConflicts(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
        ]);

        $teacher = $this->verifyTeacherAccess($request);

        $academyTz = AcademyContextService::getTimezone();
        $startTime = Carbon::parse($validated['date'], $academyTz)
            ->setTimeFromTimeString($validated['time']);
        $startTime = AcademyContextService::toUtcForStorage($startTime);
        $endTime = $startTime->copy()->addMinutes($validated['duration_minutes']);

        $conflicts = $this->calendarService->checkConflicts($teacher, $startTime, $endTime);

        return response()->json([
            'success' => true,
            'has_conflicts' => $conflicts->isNotEmpty(),
            'conflicts' => $conflicts->values(),
        ]);
    }

    public function rescheduleEvent(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'in:quran_session,circle_session,course_session,academic_session'],
            'session_id' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $teacher = $this->verifyTeacherAccess($request);
        $session = $this->resolveSessionForTeacher($validated['source'], $validated['session_id'], $teacher);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => __('teacher.calendar.session_not_found'),
            ], 404);
        }

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
        $newScheduledAt = AcademyContextService::toUtcForStorage(
            AcademyContextService::parseInAcademyTimezone($validated['scheduled_at'])
        );

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

    public function getSessionDetail(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'in:quran_session,circle_session,course_session,academic_session'],
            'session_id' => ['required', 'integer'],
        ]);

        $teacher = $this->verifyTeacherAccess($request);
        $session = $this->resolveSessionForTeacher($validated['source'], $validated['session_id'], $teacher);

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
            'can_reschedule' => $canEdit,
            'detail_url' => $this->getManageDetailUrl($session, $source, $subdomain),
        ];

        // Source-specific fields
        if (in_array($source, ['quran_session', 'circle_session'])) {
            $data['student_name'] = $session->student?->name ?? $session->trialRequest?->student_name ?? null;
            $data['circle_name'] = $session->circle?->name ?? null;
            $data['session_type'] = $session->session_type;

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

    public function updateSession(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'in:quran_session,circle_session,course_session,academic_session'],
            'session_id' => ['required', 'integer'],
            'scheduled_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', Rule::in(SessionDuration::values())],
            'teacher_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $teacher = $this->verifyTeacherAccess($request);
        $session = $this->resolveSessionForTeacher($validated['source'], $validated['session_id'], $teacher);

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
            $updateData['scheduled_at'] = AcademyContextService::toUtcForStorage(
                AcademyContextService::parseInAcademyTimezone($validated['scheduled_at'])
            );
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

        $teacher = $this->verifyTeacherAccess($request);
        $session = QuranSession::where('id', $validated['session_id'])
            ->where('quran_teacher_id', $teacher->id)
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
                'created_by' => $teacher->id,
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

    public function saveAcademicHomework(Request $request, $subdomain = null): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer'],
            'source' => ['required', 'string', 'in:academic_session,course_session'],
            'homework_description' => ['required', 'string', 'max:5000'],
        ]);

        $teacher = $this->verifyTeacherAccess($request);
        $session = $this->resolveSessionForTeacher($validated['source'], $validated['session_id'], $teacher);

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
     * Sessions monitoring page embedded in supervisor layout.
     */
    public function monitoring(Request $request, $subdomain = null): View
    {
        $monitoringController = app(SessionsMonitoringController::class);
        $user = auth()->user();
        $tab = $request->input('tab', 'quran');
        $dateFilter = $request->input('date', 'all');
        $statusFilter = $request->input('status');

        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $quranSessions = collect();
        $academicSessions = collect();
        $interactiveSessions = collect();

        if (! empty($quranTeacherIds)) {
            $query = \App\Models\QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->with(['quranTeacher', 'student', 'circle', 'individualCircle', 'meeting']);
            $this->applyDateFilter($query, $dateFilter);
            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }
            $quranSessions = $query->orderByRaw("FIELD(status, 'ready', 'ongoing', 'live') DESC, ABS(TIMESTAMPDIFF(SECOND, scheduled_at, NOW())) ASC")
                ->limit(50)->get();
        }

        if (! empty($academicTeacherProfileIds)) {
            $query = \App\Models\AcademicSession::whereIn('academic_teacher_id', $academicTeacherProfileIds)
                ->with(['academicTeacher.user', 'student', 'meeting']);
            $this->applyDateFilter($query, $dateFilter);
            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }
            $academicSessions = $query->orderByRaw("FIELD(status, 'ready', 'ongoing', 'live') DESC, ABS(TIMESTAMPDIFF(SECOND, scheduled_at, NOW())) ASC")
                ->limit(50)->get();
        }

        return view('supervisor.sessions-monitoring.index', compact(
            'tab', 'dateFilter', 'statusFilter',
            'quranSessions', 'academicSessions', 'interactiveSessions'
        ));
    }

    // ========================================================================
    // Private helpers
    // ========================================================================

    /**
     * Verify teacher_id belongs to this supervisor's assigned teachers and return the User.
     */
    private function verifyTeacherAccess(Request $request): User
    {
        $teacherId = $request->input('teacher_id');
        abort_unless($teacherId, 422, 'teacher_id is required');

        $allTeacherIds = $this->getAllAssignedTeacherIds();
        abort_unless(in_array((int) $teacherId, $allTeacherIds), 403, 'Unauthorized teacher access');

        return User::findOrFail($teacherId);
    }

    /**
     * Resolve a session model owned by the given teacher.
     */
    private function resolveSessionForTeacher(string $source, int $sessionId, User $teacher)
    {
        return match ($source) {
            'quran_session', 'circle_session' => QuranSession::where('id', $sessionId)
                ->where('quran_teacher_id', $teacher->id)
                ->with('trialRequest:id,student_name,status')
                ->first(),
            'academic_session' => AcademicSession::where('id', $sessionId)
                ->whereHas('academicTeacher', fn ($q) => $q->where('user_id', $teacher->id))
                ->first(),
            'course_session' => InteractiveCourseSession::where('id', $sessionId)
                ->whereHas('course.assignedTeacher', fn ($q) => $q->where('user_id', $teacher->id))
                ->first(),
            default => null,
        };
    }

    /**
     * Get modal title for a session.
     */
    private function getSessionModalTitle($session, string $source): string
    {
        $studentName = $session->student?->name ?? $session->trialRequest?->student_name ?? null;

        return match ($source) {
            'quran_session' => $studentName
                ? __('calendar.formatting.session_with_student', ['name' => $studentName])
                : __('calendar.formatting.session'),
            'circle_session' => $session->circle?->name ?? __('calendar.formatting.group_circle'),
            'academic_session' => $session->student?->name
                ? __('calendar.formatting.session_with_student', ['name' => $session->student->name])
                : __('calendar.formatting.session'),
            'course_session' => ($session->course?->title ?? __('calendar.formatting.educational_course'))
                .' - '.($session->title ?? __('calendar.formatting.session')),
            default => __('calendar.formatting.session'),
        };
    }

    /**
     * Get the supervisor/manage detail URL for a session.
     */
    private function getManageDetailUrl($session, string $source, ?string $subdomain): string
    {
        $subdomain = $subdomain ?? auth()->user()?->academy?->subdomain ?? DefaultAcademy::subdomain();

        try {
            $type = match ($source) {
                'quran_session', 'circle_session' => 'quran',
                'academic_session' => 'academic',
                'course_session' => 'interactive',
                default => 'quran',
            };

            return route('manage.sessions.show', [
                'subdomain' => $subdomain,
                'sessionType' => $type,
                'sessionId' => $session->id,
            ]);
        } catch (Exception $e) {
            return '#';
        }
    }

    private function applyDateFilter($query, string $dateFilter): void
    {
        match ($dateFilter) {
            'today' => $query->whereDate('scheduled_at', today()),
            'week' => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()]),
            default => null,
        };
    }
}
