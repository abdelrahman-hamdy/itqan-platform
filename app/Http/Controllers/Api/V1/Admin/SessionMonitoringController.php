<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\SessionDetailResource;
use App\Http\Resources\Api\V1\Admin\SessionSummaryResource;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Contracts\MeetingObserverServiceInterface;
use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API for session monitoring by Supervisors, Admins, and SuperAdmins.
 * Allows viewing sessions and generating observer tokens for active meetings.
 */
class SessionMonitoringController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected MeetingObserverServiceInterface $observerService,
    ) {}

    /**
     * List sessions with filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Authorization: only supervisor, admin, or super_admin
        if (! $user->isSupervisor() && ! $user->isAdmin()) {
            return $this->error(
                __('Access denied. Admin or Supervisor account required.'),
                403,
                'FORBIDDEN'
            );
        }

        $tab = $request->query('tab', 'quran');
        $statusFilter = $request->query('status');
        $dateFilter = $request->query('date', 'all');

        $sessions = $this->getSessions($user, $tab, $statusFilter, $dateFilter);
        $counts = $this->getSessionCounts($user);

        return $this->success([
            'sessions' => SessionSummaryResource::collection($sessions->items()),
            'counts' => $counts,
            'pagination' => [
                'total' => $sessions->total(),
                'per_page' => $sessions->perPage(),
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'from' => $sessions->firstItem(),
                'to' => $sessions->lastItem(),
            ],
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Get session details with observer token
     *
     * @param Request $request
     * @param string $sessionType
     * @param string $sessionId
     * @return JsonResponse
     */
    public function show(Request $request, string $sessionType, string $sessionId): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSupervisor() && ! $user->isAdmin()) {
            return $this->error(
                __('Access denied. Admin or Supervisor account required.'),
                403,
                'FORBIDDEN'
            );
        }

        $session = $this->observerService->resolveSession($sessionType, $sessionId);

        if (! $session) {
            return $this->error(
                __('Session not found.'),
                404,
                'NOT_FOUND'
            );
        }

        // Check if user can observe this session
        if (! $this->observerService->canObserveSession($user, $session)) {
            return $this->error(
                __('You do not have permission to observe this session.'),
                403,
                'FORBIDDEN'
            );
        }

        // Ensure meeting exists for active sessions
        if (method_exists($session, 'ensureMeetingExists')) {
            $session->ensureMeetingExists();
        }

        // Load relationships
        $session->load([
            'studentReports',
            'meeting',
        ]);

        // Generate observer token if session is observable
        $observerToken = null;
        $canObserve = $this->observerService->isSessionObservable($session);

        if ($canObserve && $session->meeting_room_name) {
            $observerToken = $this->observerService->generateObserverToken($session->meeting_room_name, $user);
        }

        return $this->success([
            'session' => new SessionDetailResource($session),
            'observer_token' => $observerToken,
            'livekit_url' => config('livekit.ws_url'),
            'can_observe' => $canObserve,
        ], __('Session details retrieved successfully'));
    }

    /**
     * Get sessions with filters and pagination
     */
    protected function getSessions($user, string $tab, ?string $status, string $dateFilter)
    {
        $query = match ($tab) {
            'academic' => $this->getAcademicQuery($user),
            'interactive' => $this->getInteractiveQuery($user),
            default => $this->getQuranQuery($user),
        };

        // Apply status filter
        if ($status) {
            $query->where('status', $status);
        }

        // Apply date filter
        if ($dateFilter === 'today') {
            $query->whereDate('scheduled_at', today());
        } elseif ($dateFilter === 'week') {
            $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        // Order: ongoing/ready first, then nearest upcoming, then past (most recent first)
        return $query
            ->orderByRaw("CASE WHEN status IN ('ready', 'ongoing') THEN 0 WHEN scheduled_at >= NOW() THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN scheduled_at >= NOW() THEN scheduled_at END ASC")
            ->orderByRaw("CASE WHEN scheduled_at < NOW() THEN scheduled_at END DESC")
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Get Quran sessions query with role-based scoping
     */
    protected function getQuranQuery($user): Builder
    {
        $query = QuranSession::query()
            ->with(['quranTeacher', 'circle', 'student', 'individualCircle', 'academy', 'meeting']);

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            $academyId = AcademyContextService::getCurrentAcademyId();
            if ($academyId) {
                $query->where('academy_id', $academyId);
            } elseif (! $user->isSuperAdmin()) {
                // Admin without academy context: deny all (SuperAdmin may have global view)
                return $query->whereRaw('1 = 0');
            }
        } else {
            // Supervisor: scoped to assigned teachers
            $teacherIds = $user->supervisorProfile?->getAssignedQuranTeacherIds() ?? [];
            $query->whereIn('quran_teacher_id', $teacherIds);
        }

        return $query;
    }

    /**
     * Get Academic sessions query with role-based scoping
     */
    protected function getAcademicQuery($user): Builder
    {
        $query = AcademicSession::query()
            ->with(['academicTeacher.user', 'academicIndividualLesson.academicSubject', 'student', 'academy', 'meeting']);

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            $academyId = AcademyContextService::getCurrentAcademyId();
            if ($academyId) {
                $query->where('academy_id', $academyId);
            } elseif (! $user->isSuperAdmin()) {
                return $query->whereRaw('1 = 0');
            }
        } else {
            $profileIds = $user->supervisorProfile?->getAssignedAcademicTeacherIds() ?? [];
            $query->whereIn('academic_teacher_id', $profileIds);
        }

        return $query;
    }

    /**
     * Get Interactive course sessions query with role-based scoping
     */
    protected function getInteractiveQuery($user): Builder
    {
        $query = InteractiveCourseSession::query()
            ->with(['course.assignedTeacher.user', 'course.subject', 'course.academy', 'meeting']);

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            $academyId = AcademyContextService::getCurrentAcademyId();
            if ($academyId) {
                $query->whereHas('course', fn ($q) => $q->where('academy_id', $academyId));
            } elseif (! $user->isSuperAdmin()) {
                return $query->whereRaw('1 = 0');
            }
        } else {
            $courseIds = $user->supervisorProfile?->getDerivedInteractiveCourseIds() ?? [];
            $query->whereIn('course_id', $courseIds);
        }

        return $query;
    }

    /**
     * Get session counts per tab
     */
    protected function getSessionCounts($user): array
    {
        return [
            'quran' => $this->getQuranQuery($user)->count(),
            'academic' => $this->getAcademicQuery($user)->count(),
            'interactive' => $this->getInteractiveQuery($user)->count(),
        ];
    }
}
