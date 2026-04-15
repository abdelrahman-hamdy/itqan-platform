<?php

namespace App\Http\Controllers\Supervisor;

use App\Contracts\MeetingObserverServiceInterface;
use App\Contracts\RecordingCapable;
use App\Enums\RecordingStatus;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\SessionRecording;
use App\Models\User;
use App\Services\RecordingOrchestratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SupervisorRecordingController extends BaseSupervisorWebController
{
    public function __construct(
        protected MeetingObserverServiceInterface $observerService,
        protected RecordingOrchestratorService $orchestrator,
    ) {
        parent::__construct();
    }

    public function index(Request $request, $subdomain = null): View
    {
        if (! $this->canManageRecording()) {
            abort(403);
        }

        $tab = $request->query('tab', 'live');
        $sessionTab = $request->query('session_tab', 'all');
        $teacherId = $request->query('teacher_id');
        $studentId = $request->query('student_id');
        $search = $request->query('search');
        $dateFilter = $request->query('date', 'all');

        $capacityStatus = $this->orchestrator->getCapacityStatus();
        $sessions = collect();
        $teachers = $this->getTeachersList();
        $students = $this->getStudentsList();
        $tabCounts = [];
        $historyData = null;

        if ($tab === 'live') {
            $sessions = $this->getLiveSessions($sessionTab, $teacherId, $studentId, $search);
            $tabCounts = [
                'quran' => $sessions->where('_type', 'quran')->count(),
                'academic' => $sessions->where('_type', 'academic')->count(),
                'interactive' => $sessions->where('_type', 'interactive')->count(),
            ];
        } elseif ($tab === 'history') {
            $historyData = $this->getHistoryData($sessionTab, $dateFilter);
        }

        $liveSessions = $sessions->pluck('id')->values();

        $recordedToday = SessionRecording::query()
            ->whereDate('created_at', today())
            ->where('status', RecordingStatus::COMPLETED->value)
            ->count();

        return view('supervisor.recording.index', [
            'sessions' => $sessions,
            'activeTab' => $tab,
            'sessionTab' => $sessionTab,
            'dateFilter' => $dateFilter,
            'teacherId' => $teacherId,
            'studentId' => $studentId,
            'search' => $search,
            'capacityStatus' => $capacityStatus,
            'recordedToday' => $recordedToday,
            'teachers' => $teachers,
            'students' => $students,
            'liveSessions' => $liveSessions,
            'tabCounts' => $tabCounts,
            'historyData' => $historyData,
            'recordingSystemEnabled' => config('livekit.recordings.system_enabled', true),
        ]);
    }

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

    public function deleteRecording(Request $request, $subdomain, $recordingId): \Illuminate\Http\RedirectResponse
    {
        if (! $this->canManageRecording()) {
            abort(403);
        }

        $recording = SessionRecording::findOrFail($recordingId);

        if (! $recording->status->canDelete()) {
            abort(403);
        }

        $recording->markAsDeleted();

        return redirect()->back()->with('success', __('supervisor.recording.deleted_success'));
    }

    public function bulkDelete(Request $request, $subdomain = null): \Illuminate\Http\RedirectResponse
    {
        if (! $this->canManageRecording()) {
            abort(403);
        }

        $request->validate([
            'recording_ids' => 'required|array',
            'recording_ids.*' => 'integer',
        ]);

        $ids = $request->input('recording_ids');

        // Use model method per-record so observer fires (handles file cleanup)
        $recordings = SessionRecording::whereIn('id', $ids)
            ->whereIn('status', [
                RecordingStatus::COMPLETED->value,
                RecordingStatus::SKIPPED->value,
                RecordingStatus::FAILED->value,
            ])
            ->get();

        $count = 0;
        foreach ($recordings as $recording) {
            $recording->markAsDeleted();
            $count++;
        }

        return redirect()->back()->with('success', __('supervisor.recording.bulk_deleted_success', ['count' => $count]));
    }

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
                    $presence[$session->id] = ['count' => $isObservable ? 1 : 0, 'observable' => $isObservable];
                } catch (\Exception $e) {
                    $presence[$session->id] = ['count' => 0, 'observable' => false];
                }
            }
        }

        return response()->json($presence);
    }

    // ────────────────────────────────────────────────────────────────
    // Live Sessions
    // ────────────────────────────────────────────────────────────────

    private function getLiveSessions(string $sessionTab, ?string $teacherId, ?string $studentId, ?string $search): Collection
    {
        $liveStatuses = [SessionStatus::READY->value, SessionStatus::ONGOING->value];
        $sessions = collect();
        $recordingWith = ['recordings' => fn ($r) => $r->whereIn('status', [RecordingStatus::RECORDING->value, RecordingStatus::QUEUED->value])];

        $quranNeeded = in_array($sessionTab, ['all', 'quran']);
        $academicNeeded = in_array($sessionTab, ['all', 'academic']);
        $interactiveNeeded = in_array($sessionTab, ['all', 'interactive']);

        if ($quranNeeded) {
            $q = $this->getQuranQuery()->whereIn('status', $liveStatuses)->with($recordingWith);
            if ($teacherId) {
                $q->where('quran_teacher_id', (int) $teacherId);
            }
            if ($studentId) {
                $q->where('student_id', (int) $studentId);
            }
            if ($search) {
                $q->where(fn ($sq) => $sq->where('session_code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"));
            }
            $sessions = $sessions->merge(
                $q->get()->map(fn ($s) => $s->setAttribute('_type', 'quran')->setAttribute('_recording_status', $this->getSessionRecordingStatus($s, 'quran')))
            );
        }

        if ($academicNeeded) {
            $q = $this->getAcademicQuery()->whereIn('status', $liveStatuses)->with($recordingWith);
            // Teacher filter: teacher_id is User.id but academic_teacher_id is profile ID
            if ($teacherId) {
                $q->whereHas('academicTeacher', fn ($tq) => $tq->where('user_id', (int) $teacherId));
            }
            if ($studentId) {
                $q->where('student_id', (int) $studentId);
            }
            if ($search) {
                $q->where(fn ($sq) => $sq->where('session_code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"));
            }
            $sessions = $sessions->merge(
                $q->get()->map(fn ($s) => $s->setAttribute('_type', 'academic')->setAttribute('_recording_status', $this->getSessionRecordingStatus($s, 'academic')))
            );
        }

        if ($interactiveNeeded) {
            $q = $this->getInteractiveQuery()->whereIn('status', $liveStatuses)->with($recordingWith);
            // Teacher filter: teacher_id is User.id but assigned_teacher_id is profile ID
            if ($teacherId) {
                $q->whereHas('course.assignedTeacher', fn ($tq) => $tq->where('user_id', (int) $teacherId));
            }
            if ($search) {
                $q->where(fn ($sq) => $sq->where('session_code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"));
            }
            $sessions = $sessions->merge(
                $q->get()->map(fn ($s) => $s->setAttribute('_type', 'interactive')->setAttribute('_recording_status', $this->getSessionRecordingStatus($s, 'interactive')))
            );
        }

        return $sessions->sortBy('scheduled_at');
    }

    private function getSessionRecordingStatus($session, string $type): string
    {
        $activeRecording = $session->recordings->first();
        if ($activeRecording) {
            return $activeRecording->status->value;
        }

        return $type === 'interactive' ? 'manual' : 'none';
    }

    // ────────────────────────────────────────────────────────────────
    // History
    // ────────────────────────────────────────────────────────────────

    private function getHistoryData(string $sessionTab, string $dateFilter = 'all'): array
    {
        $query = SessionRecording::query()
            ->with(['recordable' => function (\Illuminate\Database\Eloquent\Relations\MorphTo $morphTo) {
                $morphTo->morphWith([
                    QuranSession::class => ['quranTeacher', 'circle', 'student', 'trialRequest.student'],
                    AcademicSession::class => ['academicTeacher.user', 'student'],
                    InteractiveCourseSession::class => ['course.assignedTeacher.user'],
                ]);
            }])
            ->where('status', RecordingStatus::COMPLETED->value)
            ->orderByDesc('created_at');

        // Date filter
        match ($dateFilter) {
            'today' => $query->whereDate('created_at', today()),
            'week' => $query->where('created_at', '>=', now()->startOfWeek()),
            'month' => $query->where('created_at', '>=', now()->startOfMonth()),
            default => null,
        };

        if ($sessionTab !== 'all') {
            $morphClass = match ($sessionTab) {
                'quran' => (new QuranSession)->getMorphClass(),
                'academic' => (new AcademicSession)->getMorphClass(),
                'interactive' => (new InteractiveCourseSession)->getMorphClass(),
                default => null,
            };
            if ($morphClass) {
                $query->where('recordable_type', $morphClass);
            }
        }

        $recordings = $query->paginate(20)->withQueryString();

        $statsQuery = SessionRecording::query()->where('status', RecordingStatus::COMPLETED->value);
        match ($dateFilter) {
            'today' => $statsQuery->whereDate('created_at', today()),
            'week' => $statsQuery->where('created_at', '>=', now()->startOfWeek()),
            'month' => $statsQuery->where('created_at', '>=', now()->startOfMonth()),
            default => null,
        };
        $stats = $statsQuery->selectRaw('
                COUNT(*) as total_recorded,
                COALESCE(SUM(duration), 0) as total_duration,
                COALESCE(SUM(file_size), 0) as total_storage
            ')->first();

        return [
            'recordings' => $recordings,
            'stats' => $stats,
        ];
    }

    // ────────────────────────────────────────────────────────────────
    // Reusable query builders (same pattern as SupervisorSessionsController)
    // ────────────────────────────────────────────────────────────────

    private function getQuranQuery(): Builder
    {
        $query = QuranSession::query()
            ->with(['quranTeacher', 'student', 'circle', 'individualCircle', 'trialRequest.student']);

        if (! $this->isAdminUser()) {
            $query->whereIn('quran_teacher_id', $this->getAssignedQuranTeacherIds());
        }

        return $query;
    }

    private function getAcademicQuery(): Builder
    {
        $query = AcademicSession::query()
            ->with(['academicTeacher.user', 'student']);

        if (! $this->isAdminUser()) {
            $query->whereIn('academic_teacher_id', $this->getAssignedAcademicTeacherProfileIds());
        }

        return $query;
    }

    private function getInteractiveQuery(): Builder
    {
        $query = InteractiveCourseSession::query()
            ->with(['course.assignedTeacher.user']);

        if (! $this->isAdminUser()) {
            $profileIds = $this->getAssignedAcademicTeacherProfileIds();
            $query->whereHas('course', fn ($q) => $q->whereIn('assigned_teacher_id', $profileIds));
        }

        return $query;
    }

    // ────────────────────────────────────────────────────────────────
    // Filter data builders (same pattern as SupervisorSessionsController)
    // ────────────────────────────────────────────────────────────────

    private function getTeachersList(): array
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
        $teachers = collect();

        if (! empty($quranTeacherIds)) {
            $quran = User::whereIn('id', $quranTeacherIds)
                ->with('quranTeacherProfile')
                ->orderBy('name')->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'gender' => $u->quranTeacherProfile?->gender ?? $u->gender ?? '',
                    'type' => 'quran',
                    'type_label' => __('supervisor.sessions.type_quran'),
                ]);
            $teachers = $teachers->merge($quran);
        }

        if (! empty($academicTeacherIds)) {
            $academic = User::whereIn('id', $academicTeacherIds)
                ->whereNotIn('id', $quranTeacherIds)
                ->with('academicTeacherProfile')
                ->orderBy('name')->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'gender' => $u->academicTeacherProfile?->gender ?? $u->gender ?? '',
                    'type' => 'academic',
                    'type_label' => __('supervisor.sessions.type_academic'),
                ]);
            $teachers = $teachers->merge($academic);
        }

        return $teachers->sortBy('name')->values()->toArray();
    }

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
            ->with('studentProfile:id,user_id,gender')
            ->whereIn('id', $allIds)
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'gender' => $u->studentProfile?->gender ?? $u->gender ?? '',
            ])
            ->toArray();
    }

    public function toggleSystem(Request $request, $subdomain = null): RedirectResponse
    {
        if (! $this->canManageRecording()) {
            abort(403);
        }

        $currentValue = config('livekit.recordings.system_enabled', true);
        $newValue = ! $currentValue;

        // Write to .env file
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        if (str_contains($envContent, 'LIVEKIT_RECORDING_ENABLED=')) {
            $envContent = preg_replace('/LIVEKIT_RECORDING_ENABLED=.*/', 'LIVEKIT_RECORDING_ENABLED='.($newValue ? 'true' : 'false'), $envContent);
        } else {
            $envContent .= "\nLIVEKIT_RECORDING_ENABLED=".($newValue ? 'true' : 'false');
        }

        file_put_contents($envPath, $envContent);

        // Clear config cache so the change takes effect
        \Artisan::call('config:cache');

        Log::info('[RECORDINGS] System toggled', ['enabled' => $newValue, 'by' => auth()->id()]);

        return back()->with('success', $newValue ? __('supervisor.recording.system_enabled') : __('supervisor.recording.system_disabled'));
    }

    public function stopAllActive(Request $request, $subdomain = null): RedirectResponse
    {
        if (! $this->canManageRecording()) {
            abort(403);
        }

        $activeRecordings = SessionRecording::where('status', RecordingStatus::RECORDING)->with('recordable')->get();
        $stoppedCount = 0;

        foreach ($activeRecordings as $recording) {
            $session = $recording->recordable;
            if ($session instanceof RecordingCapable) {
                try {
                    $session->stopRecording();
                    $stoppedCount++;
                } catch (\Exception $e) {
                    $recording->markAsFailed('Manually stopped: '.$e->getMessage());
                    $stoppedCount++;
                }
            }
        }

        Log::info('[RECORDINGS] All active recordings stopped', ['count' => $stoppedCount, 'by' => auth()->id()]);

        return back()->with('success', __('supervisor.recording.all_stopped', ['count' => $stoppedCount]));
    }
}
