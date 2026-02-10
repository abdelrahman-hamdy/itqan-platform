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
 * Frontend sessions monitoring page for Supervisors & SuperAdmins.
 * Lists all sessions in scope with filters, allowing observation of active meetings.
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

        // Authorization: only supervisor or super_admin
        if (! $user->isSupervisor() && ! $user->isSuperAdmin()) {
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

        return $query->orderByDesc('scheduled_at')->paginate(15)->withQueryString();
    }

    protected function getQuranQuery($user): Builder
    {
        $query = QuranSession::query()
            ->with(['quranTeacher', 'circle', 'student', 'individualCircle', 'academy']);

        if ($user->isSuperAdmin()) {
            $academyId = AcademyContextService::getCurrentAcademyId();
            if ($academyId) {
                $query->where('academy_id', $academyId);
            }
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

        if ($user->isSuperAdmin()) {
            $academyId = AcademyContextService::getCurrentAcademyId();
            if ($academyId) {
                $query->where('academy_id', $academyId);
            }
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

        if ($user->isSuperAdmin()) {
            $academyId = AcademyContextService::getCurrentAcademyId();
            if ($academyId) {
                $query->whereHas('course', fn ($q) => $q->where('academy_id', $academyId));
            }
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
