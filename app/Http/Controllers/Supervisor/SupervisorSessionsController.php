<?php

namespace App\Http\Controllers\Supervisor;

use App\Contracts\MeetingObserverServiceInterface;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Http\Requests\Supervisor\UpdateSessionRequest;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\SessionCountingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SupervisorSessionsController extends BaseSupervisorWebController
{
    public function __construct(
        protected MeetingObserverServiceInterface $observerService,
    ) {
        parent::__construct();
    }

    /**
     * Sessions management list page.
     */
    public function index(Request $request, $subdomain = null): View
    {
        $tab = $request->query('tab', 'all');
        $statusFilter = $request->query('status');
        $dateFilter = $request->query('date', 'all');
        $teacherId = $request->query('teacher_id');
        $studentId = $request->query('student_id');
        $search = $request->query('search');

        // Get sessions based on tab
        if ($tab === 'all') {
            $sessions = $this->getAllSessions($statusFilter, $dateFilter, $teacherId, $studentId, $search, $request);
        } else {
            $sessions = $this->getFilteredSessions($tab, $statusFilter, $dateFilter, $teacherId, $studentId, $search, $request);
        }

        // Stats
        $stats = $this->getStats();

        // Tab counts
        $tabCounts = $this->getTabCounts();

        // Teachers list for filter
        $teachers = $this->getTeachersList();

        $students = $this->getStudentsList();
        $studentName = $studentId
            ? collect($students)->firstWhere('id', (int) $studentId)['name'] ?? null
            : null;

        return view('supervisor.sessions.index', [
            'sessions' => $sessions,
            'activeTab' => $tab,
            'statusFilter' => $statusFilter,
            'dateFilter' => $dateFilter,
            'teacherId' => $teacherId,
            'studentId' => $studentId,
            'studentName' => $studentName,
            'search' => $search,
            'stats' => $stats,
            'tabCounts' => $tabCounts,
            'teachers' => $teachers,
            'students' => $students,
            'statusOptions' => SessionStatus::options(),
        ]);
    }

    /**
     * Session detail page.
     */
    public function show(Request $request, $subdomain, string $sessionType, string $sessionId): View
    {
        $session = $this->resolveSession($sessionType, $sessionId);

        if (! $session) {
            abort(404, __('supervisor.observation.session_not_found'));
        }

        if (method_exists($session, 'ensureMeetingExists')) {
            $session->ensureMeetingExists();
        }

        $canObserve = $this->observerService->canObserveSession(auth()->user(), $session)
            && $this->observerService->isSessionObservable($session);

        $mode = $request->query('mode', 'participant');
        if ($mode === 'observer' && ! $canObserve) {
            $mode = 'participant';
        }

        $filamentUrl = $this->getFilamentUrl($sessionType, $sessionId);

        return view('supervisor.sessions.show', [
            'session' => $session,
            'sessionType' => $sessionType,
            'canObserve' => $canObserve,
            'mode' => $mode,
            'filamentUrl' => $filamentUrl,
        ]);
    }

    /**
     * Update session via AJAX.
     */
    public function update(UpdateSessionRequest $request, $subdomain, string $sessionType, string $sessionId): JsonResponse
    {
        $session = $this->resolveSession($sessionType, $sessionId);

        if (! $session) {
            return response()->json(['message' => __('supervisor.observation.session_not_found')], 404);
        }

        $validated = $request->validated();
        $updated = [];

        if (isset($validated['status']) && $validated['status'] !== $session->status->value) {
            $session->status = SessionStatus::from($validated['status']);
            $updated[] = 'status';
        }

        if (isset($validated['scheduled_at'])) {
            $session->scheduled_at = $validated['scheduled_at'];
            $updated[] = 'scheduled_at';
        }

        if (isset($validated['duration_minutes'])) {
            $session->duration_minutes = $validated['duration_minutes'];
            $updated[] = 'duration_minutes';
        }

        if (array_key_exists('supervisor_notes', $validated)) {
            $session->supervisor_notes = $validated['supervisor_notes'];
            $updated[] = 'supervisor_notes';
        }

        if (array_key_exists('admin_notes', $validated) && auth()->user()->isAdmin()) {
            $session->admin_notes = $validated['admin_notes'];
            $updated[] = 'admin_notes';
        }

        if (! empty($updated)) {
            $session->save();
        }

        return response()->json([
            'message' => __('supervisor.sessions.edit_success'),
            'updated' => $updated,
        ]);
    }

    /**
     * Cancel session via AJAX.
     */
    public function cancel(Request $request, $subdomain, string $sessionType, string $sessionId): JsonResponse
    {
        $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ]);

        $session = $this->resolveSession($sessionType, $sessionId);

        if (! $session) {
            return response()->json(['message' => __('supervisor.observation.session_not_found')], 404);
        }

        if (! $session->status->canCancel()) {
            return response()->json(['message' => __('validation.custom.session_cannot_cancel')], 422);
        }

        $session->update([
            'status' => SessionStatus::CANCELLED,
            'cancellation_reason' => $request->input('cancellation_reason'),
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'message' => __('supervisor.sessions.cancel_success'),
        ]);
    }

    /**
     * Toggle whether a session counts for teacher earnings.
     */
    public function toggleCountsForTeacher(Request $request, $subdomain, string $sessionType, int $sessionId): JsonResponse
    {
        $session = $this->resolveSession($sessionType, (string) $sessionId);

        if (! $session) {
            return response()->json(['message' => __('supervisor.observation.session_not_found')], 404);
        }

        $validated = $request->validate(['counts' => 'required|boolean']);

        app(SessionCountingService::class)->setCountsForTeacher(
            $session,
            $validated['counts'],
            auth()->id()
        );

        return response()->json(['success' => true, 'counts_for_teacher' => $validated['counts']]);
    }

    /**
     * Toggle whether a session attendance record counts for subscription.
     */
    public function toggleCountsForSubscription(Request $request, $subdomain, string $sessionType, int $sessionId, int $attendanceId): JsonResponse
    {
        $session = $this->resolveSession($sessionType, (string) $sessionId);

        if (! $session) {
            return response()->json(['message' => __('supervisor.observation.session_not_found')], 404);
        }

        $meetingAttendance = MeetingAttendance::where('session_id', $session->id)
            ->where('id', $attendanceId)
            ->firstOrFail();

        $validated = $request->validate(['counts' => 'required|boolean']);
        $counts = $validated['counts'];

        app(SessionCountingService::class)->setCountsForSubscription(
            $meetingAttendance,
            $session,
            $counts,
            auth()->id()
        );

        return response()->json(['success' => true, 'counts_for_subscription' => $counts]);
    }

    // ========================================================================
    // Private helpers
    // ========================================================================

    /**
     * Get all sessions across types (merged + paginated).
     */
    private function getAllSessions(?string $status, string $date, ?string $teacherId, ?string $studentId, ?string $search, Request $request): LengthAwarePaginator
    {
        $perPage = 20;
        $page = (int) $request->query('page', 1);

        $quranSessions = $this->buildQuery('quran', $status, $date, $teacherId, $studentId, $search)->get()
            ->each(fn ($s) => $s->setAttribute('_type', 'quran'));

        $academicSessions = $this->buildQuery('academic', $status, $date, $teacherId, $studentId, $search)->get()
            ->each(fn ($s) => $s->setAttribute('_type', 'academic'));

        $interactiveSessions = $this->buildQuery('interactive', $status, $date, $teacherId, $studentId, $search)->get()
            ->each(fn ($s) => $s->setAttribute('_type', 'interactive'));

        $all = $quranSessions->concat($academicSessions)->concat($interactiveSessions);

        // Sort: live first, then upcoming (nearest), then past (most recent)
        $sorted = $this->sortSessions($all);

        $total = $sorted->count();
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }

    /**
     * Get sessions for a specific tab (single query with pagination).
     */
    private function getFilteredSessions(string $tab, ?string $status, string $date, ?string $teacherId, ?string $studentId, ?string $search, Request $request): LengthAwarePaginator
    {
        $query = $this->buildQuery($tab, $status, $date, $teacherId, $studentId, $search);

        return $query
            ->orderByRaw("CASE WHEN status IN ('ready', 'ongoing') THEN 0 WHEN scheduled_at >= NOW() THEN 1 ELSE 2 END")
            ->orderByRaw('CASE WHEN scheduled_at >= NOW() THEN scheduled_at END ASC')
            ->orderByRaw('CASE WHEN scheduled_at < NOW() THEN scheduled_at END DESC')
            ->paginate(20)
            ->withQueryString()
            ->through(function ($session) use ($tab) {
                $session->setAttribute('_type', $tab);

                return $session;
            });
    }

    /**
     * Build a query for a specific session type with filters.
     */
    private function buildQuery(string $type, ?string $status, string $date, ?string $teacherId, ?string $studentId, ?string $search): Builder
    {
        $query = match ($type) {
            'academic' => $this->getAcademicQuery(),
            'interactive' => $this->getInteractiveQuery(),
            default => $this->getQuranQuery(),
        };

        // Status filter
        if ($status === 'need_review') {
            // Completed sessions where teacher or student attendance is not fully attended, excluding trials
            $reviewStatuses = [AttendanceStatus::ABSENT->value, AttendanceStatus::PARTIALLY_ATTENDED->value];
            $query->where('status', SessionStatus::COMPLETED)
                ->where(function ($q) use ($reviewStatuses) {
                    $q->whereIn('teacher_attendance_status', $reviewStatuses)
                        ->orWhereHas('meetingAttendances', function ($mq) use ($reviewStatuses) {
                            $mq->where('user_type', 'student')
                                ->where('is_calculated', true)
                                ->whereIn('attendance_status', $reviewStatuses);
                        });
                });

            if ($query->getModel() instanceof QuranSession) {
                $query->where('session_type', '!=', 'trial');
            }
        } elseif ($status) {
            $statuses = array_filter(explode(',', $status));
            if (! empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
        }

        // Date filter
        if ($date === 'today') {
            $query->whereDate('scheduled_at', today());
        } elseif ($date === 'week') {
            $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($date === 'month') {
            $query->whereBetween('scheduled_at', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($date === 'custom') {
            $dateFrom = request('date_from');
            $dateTo = request('date_to');
            if ($dateFrom) {
                $query->whereDate('scheduled_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('scheduled_at', '<=', $dateTo);
            }
        }

        // Teacher filter
        if ($teacherId) {
            $this->applyTeacherFilter($query, $type, (int) $teacherId);
        }

        // Student filter
        if ($studentId) {
            $this->applyStudentFilter($query, $type, (int) $studentId);
        }

        // Search filter
        if ($search) {
            $this->applySearchFilter($query, $type, $search);
        }

        return $query;
    }

    /**
     * Build scoped query for Quran sessions.
     */
    private function getQuranQuery(): Builder
    {
        $query = QuranSession::query()
            ->with(['quranTeacher', 'student', 'circle', 'individualCircle', 'trialRequest.student', 'meetingAttendances.user']);

        if (! $this->isAdminUser()) {
            $teacherIds = $this->getAssignedQuranTeacherIds();
            $query->whereIn('quran_teacher_id', $teacherIds);
        }

        return $query;
    }

    /**
     * Build scoped query for Academic sessions.
     */
    private function getAcademicQuery(): Builder
    {
        $query = AcademicSession::query()
            ->with(['academicTeacher.user', 'student', 'academicIndividualLesson.academicSubject', 'meetingAttendances.user']);

        if (! $this->isAdminUser()) {
            $profileIds = $this->getAssignedAcademicTeacherProfileIds();
            $query->whereIn('academic_teacher_id', $profileIds);
        }

        return $query;
    }

    /**
     * Build scoped query for Interactive Course sessions.
     */
    private function getInteractiveQuery(): Builder
    {
        $query = InteractiveCourseSession::query()
            ->with(['course.assignedTeacher.user', 'course.subject', 'meetingAttendances.user']);

        if (! $this->isAdminUser()) {
            $profileIds = $this->getAssignedAcademicTeacherProfileIds();
            $query->whereHas('course', fn ($q) => $q->whereIn('assigned_teacher_id', $profileIds));
        }

        return $query;
    }

    /**
     * Apply teacher filter to query.
     */
    private function applyTeacherFilter(Builder $query, string $type, int $teacherUserId): void
    {
        match ($type) {
            'academic' => $query->whereHas('academicTeacher', fn ($q) => $q->where('user_id', $teacherUserId)),
            'interactive' => $query->whereHas('course.assignedTeacher', fn ($q) => $q->where('user_id', $teacherUserId)),
            default => $query->where('quran_teacher_id', $teacherUserId),
        };
    }

    /**
     * Apply student filter to query.
     */
    private function applyStudentFilter(Builder $query, string $type, int $studentUserId): void
    {
        match ($type) {
            'interactive' => $query->whereHas('course.enrollments.student', fn ($q) => $q->where('user_id', $studentUserId)),
            default => $query->where('student_id', $studentUserId),
        };
    }

    /**
     * Apply search filter to query.
     */
    private function applySearchFilter(Builder $query, string $type, string $search): void
    {
        $query->where(function ($q) use ($type, $search) {
            $q->where('session_code', 'LIKE', "%{$search}%");

            match ($type) {
                'academic' => $q
                    ->orWhereHas('academicTeacher.user', fn ($uq) => $uq->where('name', 'LIKE', "%{$search}%"))
                    ->orWhereHas('student', fn ($uq) => $uq->where('name', 'LIKE', "%{$search}%")),
                'interactive' => $q
                    ->orWhereHas('course.assignedTeacher.user', fn ($uq) => $uq->where('name', 'LIKE', "%{$search}%"))
                    ->orWhereHas('course', fn ($cq) => $cq->where('title', 'LIKE', "%{$search}%")),
                default => $q
                    ->orWhereHas('quranTeacher', fn ($uq) => $uq->where('name', 'LIKE', "%{$search}%"))
                    ->orWhereHas('student', fn ($uq) => $uq->where('name', 'LIKE', "%{$search}%")),
            };
        });
    }

    /**
     * Sort sessions: live first, then upcoming (nearest), then past (most recent).
     */
    private function sortSessions(Collection $sessions): Collection
    {
        return $sessions->sortBy(function ($session) {
            $status = $session->status;
            $scheduledAt = $session->scheduled_at;

            if (in_array($status, [SessionStatus::READY, SessionStatus::ONGOING])) {
                return [0, $scheduledAt?->timestamp ?? 0];
            }

            if ($scheduledAt && $scheduledAt->isFuture()) {
                return [1, $scheduledAt->timestamp];
            }

            // Past sessions: negate timestamp so most recent comes first
            return [2, -($scheduledAt?->timestamp ?? 0)];
        })->values();
    }

    /**
     * Get stats for the stats bar.
     */
    private function getStats(): array
    {
        $quranBase = $this->getQuranQuery();
        $academicBase = $this->getAcademicQuery();
        $interactiveBase = $this->getInteractiveQuery();

        $total = $quranBase->count() + $academicBase->count() + $interactiveBase->count();

        $liveStatuses = [SessionStatus::ONGOING->value, SessionStatus::READY->value];
        $liveNow = (clone $quranBase)->whereIn('status', $liveStatuses)->count()
            + (clone $academicBase)->whereIn('status', $liveStatuses)->count()
            + (clone $interactiveBase)->whereIn('status', $liveStatuses)->count();

        $scheduledToday = (clone $quranBase)->whereDate('scheduled_at', today())->where('status', '!=', SessionStatus::CANCELLED)->count()
            + (clone $academicBase)->whereDate('scheduled_at', today())->where('status', '!=', SessionStatus::CANCELLED)->count()
            + (clone $interactiveBase)->whereDate('scheduled_at', today())->where('status', '!=', SessionStatus::CANCELLED)->count();

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $completedWeek = (clone $quranBase)->where('status', SessionStatus::COMPLETED)->whereBetween('scheduled_at', [$weekStart, $weekEnd])->count()
            + (clone $academicBase)->where('status', SessionStatus::COMPLETED)->whereBetween('scheduled_at', [$weekStart, $weekEnd])->count()
            + (clone $interactiveBase)->where('status', SessionStatus::COMPLETED)->whereBetween('scheduled_at', [$weekStart, $weekEnd])->count();

        return [
            'total' => $total,
            'live_now' => $liveNow,
            'scheduled_today' => $scheduledToday,
            'completed_week' => $completedWeek,
        ];
    }

    /**
     * Get counts per session type tab.
     */
    private function getTabCounts(): array
    {
        return [
            'quran' => $this->getQuranQuery()->count(),
            'academic' => $this->getAcademicQuery()->count(),
            'interactive' => $this->getInteractiveQuery()->count(),
        ];
    }

    /**
     * Get combined teachers list for filter dropdown.
     */
    private function getTeachersList(): array
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        $teachers = collect();

        if (! empty($quranTeacherIds)) {
            $quran = User::whereIn('id', $quranTeacherIds)->orderBy('name')->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'gender' => $u->gender ?? '',
                    'type' => 'quran',
                    'type_label' => __('supervisor.sessions.type_quran'),
                ]);
            $teachers = $teachers->merge($quran);
        }

        if (! empty($academicTeacherIds)) {
            $academic = User::whereIn('id', $academicTeacherIds)
                ->whereNotIn('id', $quranTeacherIds) // avoid duplicates
                ->orderBy('name')->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'gender' => $u->gender ?? '',
                    'type' => 'academic',
                    'type_label' => __('supervisor.sessions.type_academic'),
                ]);
            $teachers = $teachers->merge($academic);
        }

        return $teachers->sortBy('name')->values()->toArray();
    }

    /**
     * Get combined students list for filter dropdown.
     */
    private function getStudentsList(): array
    {
        $quranQuery = QuranSession::query();
        $academicQuery = AcademicSession::query();

        if (! $this->isAdminUser()) {
            $quranQuery->whereIn('quran_teacher_id', $this->getAssignedQuranTeacherIds());
            $academicQuery->whereIn('academic_teacher_id', $this->getAssignedAcademicTeacherProfileIds());
        }

        $quranStudentIds = $quranQuery->distinct()->pluck('student_id')->filter()->toArray();
        $academicStudentIds = $academicQuery->distinct()->pluck('student_id')->filter()->toArray();

        $allIds = array_unique(array_merge($quranStudentIds, $academicStudentIds));

        if (empty($allIds)) {
            return [];
        }

        return User::query()
            ->select('id', 'first_name', 'last_name', 'gender')
            ->whereIn('id', $allIds)
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'gender' => $user->gender ?? '',
            ])
            ->toArray();
    }

    /**
     * Resolve a session by type and ID with eager loading.
     */
    private function resolveSession(string $type, string $id)
    {
        $query = match ($type) {
            'academic' => $this->getAcademicQuery()->with([
                'homeworkAssignments', 'homeworkSubmissions', 'sessionReports', 'cancelledBy',
            ]),
            'interactive' => $this->getInteractiveQuery()->with([
                'course.enrolledStudents.student.user', 'homework', 'studentReports', 'cancelledBy',
            ]),
            default => $this->getQuranQuery()->with([
                'circle.students', 'individualCircle.subscription.package',
                'sessionHomework', 'studentReports', 'cancelledBy',
            ]),
        };

        return $query->find($id);
    }

    /**
     * Get Filament panel URL for a session (only for admin/super_admin).
     */
    private function getFilamentUrl(string $type, string $id): ?string
    {
        $user = auth()->user();

        if (! $user->isAdmin()) {
            return null;
        }

        $resource = match ($type) {
            'academic' => 'academic-sessions',
            'interactive' => 'interactive-course-sessions',
            default => 'quran-sessions',
        };

        if ($user->isSuperAdmin()) {
            return url("/admin/{$resource}/{$id}");
        }

        $academyId = $user->academy?->id;
        if (! $academyId) {
            return null;
        }

        return url("/panel/{$academyId}/{$resource}/{$id}");
    }
}
