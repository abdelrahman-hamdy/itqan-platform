<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\SessionsMonitoringController;
use App\Models\User;
use App\Services\CalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorCalendarController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $allTeacherIds = $this->getAllAssignedTeacherIds();
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        $teachers = collect();

        if (!empty($quranTeacherIds)) {
            $quranTeachers = User::whereIn('id', $quranTeacherIds)->get()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'type' => 'quran',
                'type_label' => __('supervisor.teachers.teacher_type_quran'),
            ]);
            $teachers = $teachers->merge($quranTeachers);
        }

        if (!empty($academicTeacherIds)) {
            $academicTeachers = User::whereIn('id', $academicTeacherIds)->get()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'type' => 'academic',
                'type_label' => __('supervisor.teachers.teacher_type_academic'),
            ]);
            $teachers = $teachers->merge($academicTeachers);
        }

        $selectedTeacherId = $request->teacher_id;
        $selectedTeacher = $selectedTeacherId ? User::find($selectedTeacherId) : null;

        return view('supervisor.calendar.index', compact('teachers', 'selectedTeacherId', 'selectedTeacher'));
    }

    public function getEvents(Request $request, $subdomain = null): JsonResponse
    {
        $teacherId = $request->teacher_id;

        if (!$teacherId) {
            return response()->json([]);
        }

        // Verify the teacher is assigned to this supervisor
        $allTeacherIds = $this->getAllAssignedTeacherIds();
        if (!in_array((int) $teacherId, $allTeacherIds)) {
            return response()->json([], 403);
        }

        $teacher = User::findOrFail($teacherId);
        $calendarService = app(CalendarService::class);

        $events = $calendarService->getUserCalendar(
            $teacher,
            $request->input('start'),
            $request->input('end')
        );

        return response()->json($events);
    }

    /**
     * Sessions monitoring page embedded in supervisor layout.
     */
    public function monitoring(Request $request, $subdomain = null): View
    {
        // Delegate to existing monitoring controller logic but render in supervisor layout
        $monitoringController = app(SessionsMonitoringController::class);

        // We need to get the data the monitoring controller would pass to its view
        // Since we can't easily reuse it, query the same data here
        $user = auth()->user();
        $tab = $request->input('tab', 'quran');
        $dateFilter = $request->input('date', 'all');
        $statusFilter = $request->input('status');

        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $quranSessions = collect();
        $academicSessions = collect();
        $interactiveSessions = collect();

        if (!empty($quranTeacherIds)) {
            $query = \App\Models\QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->with(['quranTeacher', 'student', 'circle', 'individualCircle', 'meeting']);
            $this->applyDateFilter($query, $dateFilter);
            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }
            $quranSessions = $query->orderByRaw("FIELD(status, 'ready', 'ongoing', 'live') DESC, ABS(TIMESTAMPDIFF(SECOND, scheduled_at, NOW())) ASC")
                ->limit(50)->get();
        }

        if (!empty($academicTeacherProfileIds)) {
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

    private function applyDateFilter($query, string $dateFilter): void
    {
        match ($dateFilter) {
            'today' => $query->whereDate('scheduled_at', today()),
            'week' => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()]),
            default => null,
        };
    }
}
