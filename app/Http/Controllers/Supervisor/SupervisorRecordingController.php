<?php

namespace App\Http\Controllers\Supervisor;

use App\Contracts\MeetingObserverServiceInterface;
use App\Enums\RecordingStatus;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\SessionRecording;
use App\Services\RecordingOrchestratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SupervisorRecordingController extends BaseSupervisorWebController
{
    public function __construct(
        protected MeetingObserverServiceInterface $observerService,
        protected RecordingOrchestratorService $orchestrator,
    ) {
        parent::__construct();
    }

    /**
     * Recording management main page.
     */
    public function index(Request $request, $subdomain = null): View
    {
        if (! $this->canManageRecording()) {
            abort(403);
        }

        $tab = $request->query('tab', 'live');
        $typeFilter = $request->query('type', 'all');
        $statusFilter = $request->query('recording_status');
        $teacherId = $request->query('teacher_id');
        $search = $request->query('search');

        $capacityStatus = $this->orchestrator->getCapacityStatus();
        $sessions = collect();
        $teachers = $this->getTeachersList();

        if ($tab === 'live') {
            $sessions = $this->getLiveSessions($typeFilter, $statusFilter, $teacherId, $search);
        }

        // Get live session IDs for presence polling
        $liveSessions = $sessions->pluck('id')->values();

        // Recorded today count
        $recordedToday = SessionRecording::query()
            ->whereDate('created_at', today())
            ->where('status', RecordingStatus::COMPLETED->value)
            ->count();

        return view('supervisor.recording.index', [
            'sessions' => $sessions,
            'activeTab' => $tab,
            'typeFilter' => $typeFilter,
            'statusFilter' => $statusFilter,
            'teacherId' => $teacherId,
            'search' => $search,
            'capacityStatus' => $capacityStatus,
            'recordedToday' => $recordedToday,
            'teachers' => $teachers,
            'liveSessions' => $liveSessions,
            'allowedTypes' => $this->getAllowedRecordingTypes(),
        ]);
    }

    /**
     * AJAX: Current capacity status for polling.
     */
    public function capacityStatus(Request $request): JsonResponse
    {
        if (! $this->canManageRecording()) {
            abort(403);
        }

        $status = $this->orchestrator->getCapacityStatus();

        $status['recorded_today'] = SessionRecording::query()
            ->whereDate('created_at', today())
            ->where('status', RecordingStatus::COMPLETED->value)
            ->count();

        return response()->json($status);
    }

    /**
     * AJAX: Recording history data.
     */
    public function history(Request $request): JsonResponse
    {
        if (! $this->canManageRecording()) {
            abort(403);
        }

        $typeFilter = $request->query('type', 'all');
        $statusFilter = $request->query('recording_status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = SessionRecording::query()
            ->with('recordable')
            ->whereIn('status', [
                RecordingStatus::COMPLETED->value,
                RecordingStatus::SKIPPED->value,
                RecordingStatus::FAILED->value,
            ])
            ->orderByDesc('created_at');

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($typeFilter && $typeFilter !== 'all') {
            $morphType = $this->getMorphTypeFromFilter($typeFilter);
            if ($morphType) {
                $query->where('recordable_type', $morphType);
            }
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $recordings = $query->paginate(20);

        // Single aggregate query instead of 5 separate queries
        $stats = SessionRecording::query()
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->selectRaw('
                COUNT(CASE WHEN status = ? THEN 1 END) as total_recorded,
                COUNT(CASE WHEN status = ? THEN 1 END) as total_skipped,
                COUNT(CASE WHEN status = ? THEN 1 END) as total_failed,
                COALESCE(SUM(CASE WHEN status = ? THEN duration END), 0) as total_duration,
                COALESCE(SUM(CASE WHEN status = ? THEN file_size END), 0) as total_storage
            ', [
                RecordingStatus::COMPLETED->value,
                RecordingStatus::SKIPPED->value,
                RecordingStatus::FAILED->value,
                RecordingStatus::COMPLETED->value,
                RecordingStatus::COMPLETED->value,
            ])
            ->first()
            ->toArray();

        return response()->json([
            'recordings' => $recordings,
            'stats' => $stats,
        ]);
    }

    /**
     * AJAX: Live session presence data.
     */
    public function livePresence(Request $request): JsonResponse
    {
        if (! $this->canManageRecording()) {
            abort(403);
        }

        $sessionIds = array_map('intval', (array) $request->input('sessions', []));

        if (empty($sessionIds)) {
            return response()->json([]);
        }

        $presence = [];
        foreach (['quran', 'academic', 'interactive'] as $type) {
            $modelClass = match ($type) {
                'quran' => QuranSession::class,
                'academic' => AcademicSession::class,
                'interactive' => InteractiveCourseSession::class,
            };

            $sessions = $modelClass::whereIn('id', $sessionIds)
                ->whereNotNull('meeting_room_name')
                ->get(['id', 'meeting_room_name']);

            foreach ($sessions as $session) {
                try {
                    $isObservable = $this->observerService->isSessionObservable($session);
                    $presence[$session->id] = [
                        'count' => $isObservable ? 1 : 0,
                        'observable' => $isObservable,
                    ];
                } catch (\Exception $e) {
                    $presence[$session->id] = ['count' => 0, 'observable' => false];
                }
            }
        }

        return response()->json($presence);
    }

    /**
     * Get live sessions with recording status.
     */
    private function getLiveSessions(string $typeFilter, ?string $statusFilter, ?string $teacherId, ?string $search): Collection
    {
        $liveStatuses = [SessionStatus::READY->value, SessionStatus::ONGOING->value];
        $sessions = collect();

        $allowedTypes = $this->getAllowedRecordingTypes();

        // Query each session type based on filter and permissions
        $typesToQuery = $this->getTypesToQuery($typeFilter, $allowedTypes);

        foreach ($typesToQuery as $type) {
            $query = match ($type) {
                'quran_individual', 'quran_group', 'trial' => QuranSession::query()
                    ->whereIn('status', $liveStatuses)
                    ->with([
                        'quranTeacher:id,name',
                        'recordings' => fn ($q) => $q->whereIn('status', [
                            RecordingStatus::RECORDING->value,
                            RecordingStatus::QUEUED->value,
                        ]),
                    ])
                    ->when($type === 'quran_individual', fn ($q) => $q->where('session_type', 'individual')->where('is_trial', false))
                    ->when($type === 'quran_group', fn ($q) => $q->whereIn('session_type', ['group', 'circle']))
                    ->when($type === 'trial', fn ($q) => $q->where('is_trial', true)),

                'academic_lesson' => AcademicSession::query()
                    ->whereIn('status', $liveStatuses)
                    ->with([
                        'academicTeacher.user:id,name',
                        'recordings' => fn ($q) => $q->whereIn('status', [
                            RecordingStatus::RECORDING->value,
                            RecordingStatus::QUEUED->value,
                        ]),
                    ]),

                'interactive_course' => InteractiveCourseSession::query()
                    ->whereIn('status', $liveStatuses)
                    ->with([
                        'course.assignedTeacher.user:id,name',
                        'recordings' => fn ($q) => $q->whereIn('status', [
                            RecordingStatus::RECORDING->value,
                            RecordingStatus::QUEUED->value,
                        ]),
                    ]),

                default => null,
            };

            if (! $query) {
                continue;
            }

            // Scope by supervisor's assigned teachers
            $this->scopeByAssignedTeachers($query, $type);

            // Search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('session_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            }

            // Teacher filter
            if ($teacherId) {
                $this->filterByTeacher($query, $type, (int) $teacherId);
            }

            $results = $query->get()->map(function ($session) use ($type) {
                $session->_recording_type = $type;
                $session->_recording_status = $this->getSessionRecordingStatus($session, $type);

                return $session;
            });

            // Recording status filter
            if ($statusFilter) {
                $results = $results->filter(fn ($s) => $s->_recording_status === $statusFilter);
            }

            $sessions = $sessions->merge($results);
        }

        return $sessions->sortBy('scheduled_at');
    }

    /**
     * Determine which session types to query based on filter and permissions.
     */
    private function getTypesToQuery(string $typeFilter, array $allowedTypes): array
    {
        if ($typeFilter !== 'all') {
            return in_array($typeFilter, $allowedTypes) ? [$typeFilter] : [];
        }

        return $allowedTypes;
    }

    /**
     * Get recording status for a session.
     */
    private function getSessionRecordingStatus($session, string $type): string
    {
        $activeRecording = $session->recordings->first();

        if ($activeRecording) {
            return $activeRecording->status->value;
        }

        // Interactive courses are manually controlled
        if ($type === 'interactive_course') {
            return 'manual';
        }

        return 'none';
    }

    /**
     * Scope query by supervisor's assigned teachers.
     */
    private function scopeByAssignedTeachers($query, string $type): void
    {
        if ($this->isAdminUser()) {
            return;
        }

        $profile = $this->getCurrentSupervisorProfile();
        if (! $profile) {
            $query->whereRaw('1 = 0');

            return;
        }

        match ($type) {
            'quran_individual', 'quran_group', 'trial' => $query->whereIn(
                'quran_teacher_id',
                $profile->getAssignedQuranTeacherIds()
            ),
            'academic_lesson' => $query->whereIn(
                'academic_teacher_id',
                $profile->getAssignedAcademicTeacherProfileIds()
            ),
            'interactive_course' => $query->whereHas('course', fn ($q) => $q->whereIn(
                'id',
                $profile->getDerivedInteractiveCourseIds()
            )),
            default => null,
        };
    }

    /**
     * Filter by teacher ID.
     */
    private function filterByTeacher($query, string $type, int $teacherId): void
    {
        match ($type) {
            'quran_individual', 'quran_group', 'trial' => $query->where('quran_teacher_id', $teacherId),
            'academic_lesson' => $query->where('academic_teacher_id', $teacherId),
            'interactive_course' => $query->whereHas('course', fn ($q) => $q->where('assigned_teacher_id', $teacherId)),
            default => null,
        };
    }

    /**
     * Get the session types this supervisor can see.
     */
    private function getAllowedRecordingTypes(): array
    {
        if ($this->isAdminUser()) {
            return ['quran_individual', 'quran_group', 'academic_lesson', 'interactive_course', 'trial'];
        }

        $profile = $this->getCurrentSupervisorProfile();

        return $profile?->getRecordingSessionTypes() ?: ['quran_individual', 'quran_group', 'academic_lesson', 'interactive_course', 'trial'];
    }

    /**
     * Get the morph type class from a filter string.
     */
    private function getMorphTypeFromFilter(string $filter): ?string
    {
        return match ($filter) {
            'quran_individual', 'quran_group', 'trial' => (new QuranSession)->getMorphClass(),
            'academic_lesson' => (new AcademicSession)->getMorphClass(),
            'interactive_course' => (new InteractiveCourseSession)->getMorphClass(),
            default => null,
        };
    }

    /**
     * Get all teachers for the filter dropdown.
     */
    private function getTeachersList(): array
    {
        $teachers = [];

        if ($this->isAdminUser()) {
            $quranTeachers = \App\Models\User::where('user_type', 'quran_teacher')
                ->where('active_status', true)
                ->select('id', 'name', 'email')
                ->get()
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'type' => 'quran'])
                ->toArray();

            $academicTeachers = \App\Models\User::where('user_type', 'academic_teacher')
                ->where('active_status', true)
                ->select('id', 'name', 'email')
                ->get()
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'type' => 'academic'])
                ->toArray();

            $teachers = array_merge($quranTeachers, $academicTeachers);
        } else {
            $profile = $this->getCurrentSupervisorProfile();
            if ($profile) {
                $quranTeachers = $profile->quranTeachers()
                    ->select('users.id', 'users.name')
                    ->get()
                    ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'type' => 'quran'])
                    ->toArray();

                $academicTeachers = $profile->academicTeachers()
                    ->select('users.id', 'users.name')
                    ->get()
                    ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'type' => 'academic'])
                    ->toArray();

                $teachers = array_merge($quranTeachers, $academicTeachers);
            }
        }

        return $teachers;
    }
}
