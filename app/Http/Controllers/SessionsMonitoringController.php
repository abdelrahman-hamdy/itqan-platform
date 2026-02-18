<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use App\Services\MeetingObserverService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Frontend sessions monitoring page.
 * Index: Admins, Supervisors, SuperAdmins can list all sessions.
 * Show: All authenticated staff (including teachers) can view their own sessions.
 */
class SessionsMonitoringController extends Controller
{
    public function __construct(
        protected MeetingObserverService $observerService,
    ) {
        $this->middleware(['auth']);
    }

    public function index(Request $request): View
    {
        $user = auth()->user();

        if (! $user->isSupervisor() && ! $user->isSuperAdmin() && ! $user->isAdmin()) {
            abort(403);
        }

        $tab = $request->query('tab', 'quran');
        $statusFilter = $request->query('status');
        $dateFilter = $request->query('date', 'all'); // all, today, week

        $sessions = $this->getSessions($user, $tab, $statusFilter, $dateFilter);

        $counts = $this->getSessionCounts($user);

        return view('sessions-monitoring.index', [
            'sessions' => $sessions,
            'activeTab' => $tab,
            'statusFilter' => $statusFilter,
            'dateFilter' => $dateFilter,
            'counts' => $counts,
            'statusOptions' => SessionStatus::options(),
        ]);
    }

    public function show(Request $request, $subdomain, string $sessionType, string $sessionId): View
    {
        $user = auth()->user();

        if (! $user->isSupervisor() && ! $user->isSuperAdmin() && ! $user->isAdmin()
            && ! $user->isQuranTeacher() && ! $user->isAcademicTeacher()) {
            abort(403);
        }

        $session = $this->resolveSession($user, $sessionType, $sessionId);

        if (! $session) {
            abort(404, __('supervisor.observation.session_not_found'));
        }

        // Ensure meeting exists for active sessions
        if (method_exists($session, 'ensureMeetingExists')) {
            $session->ensureMeetingExists();
        }

        // Determine if user can observe silently
        $canObserve = $this->observerService->canObserveSession($user, $session)
            && $this->observerService->isSessionObservable($session);

        // Mode: 'participant' (default) or 'observer' (silent)
        $mode = $request->query('mode', 'participant');
        if ($mode === 'observer' && ! $canObserve) {
            $mode = 'participant';
        }

        return view('sessions-monitoring.show', [
            'session' => $session,
            'sessionType' => $sessionType,
            'canObserve' => $canObserve,
            'mode' => $mode,
        ]);
    }

    protected function resolveSession($user, string $type, string $id)
    {
        $query = match ($type) {
            'academic' => $this->getAcademicQuery($user)->with([
                'academicTeacher.user', 'student', 'academicIndividualLesson.academicSubject',
                'studentReports', 'homeworkSubmissions',
            ]),
            'interactive' => $this->getInteractiveQuery($user)->with([
                'course.assignedTeacher.user', 'course.subject', 'course.enrolledStudents.student.user',
                'studentReports',
            ]),
            default => $this->getQuranQuery($user)->with([
                'quranTeacher', 'student', 'circle.students', 'individualCircle.subscription.package',
                'sessionHomework', 'studentReports',
            ]),
        };

        return $query->find($id);
    }

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
            ->orderByRaw('CASE WHEN scheduled_at >= NOW() THEN scheduled_at END ASC')
            ->orderByRaw('CASE WHEN scheduled_at < NOW() THEN scheduled_at END DESC')
            ->paginate(15)
            ->withQueryString();
    }

    protected function getQuranQuery($user): Builder
    {
        $query = QuranSession::query()
            ->with(['quranTeacher', 'circle', 'student', 'individualCircle', 'academy']);

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            $academyId = $user->isAdmin() ? $user->academy_id : AcademyContextService::getCurrentAcademyId();
            if ($academyId) {
                $query->where('academy_id', $academyId);
            }
        } elseif ($user->isQuranTeacher()) {
            $query->where('quran_teacher_id', $user->id);
        } else {
            // Supervisor: scoped to assigned teachers
            $teacherIds = $user->supervisorProfile?->getAssignedQuranTeacherIds() ?? [];
            $query->whereIn('quran_teacher_id', $teacherIds);
        }

        return $query;
    }

    protected function getAcademicQuery($user): Builder
    {
        $query = AcademicSession::query()
            ->with(['academicTeacher.user', 'academicIndividualLesson.academicSubject', 'student', 'academy']);

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            $academyId = $user->isAdmin() ? $user->academy_id : AcademyContextService::getCurrentAcademyId();
            if ($academyId) {
                $query->where('academy_id', $academyId);
            }
        } elseif ($user->isAcademicTeacher()) {
            $teacherProfileId = $user->academicTeacherProfile?->id;
            $query->where('academic_teacher_id', $teacherProfileId);
        } else {
            $profileIds = $user->supervisorProfile?->getAssignedAcademicTeacherIds() ?? [];
            $query->whereIn('academic_teacher_id', $profileIds);
        }

        return $query;
    }

    protected function getInteractiveQuery($user): Builder
    {
        $query = InteractiveCourseSession::query()
            ->with(['course.assignedTeacher.user', 'course.subject', 'course.academy']);

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            $academyId = $user->isAdmin() ? $user->academy_id : AcademyContextService::getCurrentAcademyId();
            if ($academyId) {
                $query->whereHas('course', fn ($q) => $q->where('academy_id', $academyId));
            }
        } elseif ($user->isAcademicTeacher()) {
            $teacherProfileId = $user->academicTeacherProfile?->id;
            $query->whereHas('course', fn ($q) => $q->where('assigned_teacher_id', $teacherProfileId));
        } else {
            $courseIds = $user->supervisorProfile?->getDerivedInteractiveCourseIds() ?? [];
            $query->whereIn('course_id', $courseIds);
        }

        return $query;
    }

    protected function getSessionCounts($user): array
    {
        return [
            'quran' => $this->getQuranQuery($user)->count(),
            'academic' => $this->getAcademicQuery($user)->count(),
            'interactive' => $this->getInteractiveQuery($user)->count(),
        ];
    }
}
