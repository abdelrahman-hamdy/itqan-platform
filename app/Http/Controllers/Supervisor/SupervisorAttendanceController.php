<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\AttendanceStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionAttendance;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveSessionAttendance;
use App\Models\QuranSession;
use App\Models\QuranSessionAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class SupervisorAttendanceController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        // Collect attendance from all session types
        $allRecords = collect();

        // Quran attendance
        if (! empty($quranTeacherIds)) {
            $quranQuery = QuranSessionAttendance::whereHas('session', function ($q) use ($quranTeacherIds) {
                $q->whereIn('quran_teacher_id', $quranTeacherIds);
            })->with(['student', 'session.quranTeacher']);

            $this->applyFilters($quranQuery, $request, 'quran');

            $quranRecords = $quranQuery->get()->map(function ($att) {
                return [
                    'id' => $att->id,
                    'date' => $att->session?->scheduled_at,
                    'student' => $att->student,
                    'teacher_name' => $att->session?->quranTeacher?->name ?? '',
                    'session_type' => 'quran',
                    'session_info' => $att->session?->title ?? '',
                    'status' => $att->attendance_status,
                    'duration' => $att->attendance_duration_minutes,
                    'student_id' => $att->student_id,
                ];
            });
            $allRecords = $allRecords->merge($quranRecords);
        }

        // Academic attendance
        if (! empty($academicProfileIds)) {
            $academicQuery = AcademicSessionAttendance::whereHas('session', function ($q) use ($academicProfileIds) {
                $q->whereIn('academic_teacher_id', $academicProfileIds);
            })->with(['student', 'session.academicTeacher.user']);

            $this->applyFilters($academicQuery, $request, 'academic');

            $academicRecords = $academicQuery->get()->map(function ($att) {
                return [
                    'id' => $att->id,
                    'date' => $att->session?->scheduled_at,
                    'student' => $att->student,
                    'teacher_name' => $att->session?->academicTeacher?->user?->name ?? '',
                    'session_type' => 'academic',
                    'session_info' => $att->session?->title ?? '',
                    'status' => $att->attendance_status,
                    'duration' => $att->attendance_duration_minutes,
                    'student_id' => $att->student_id,
                ];
            });
            $allRecords = $allRecords->merge($academicRecords);
        }

        // Interactive attendance
        if (! empty($academicProfileIds)) {
            $interactiveQuery = InteractiveSessionAttendance::whereHas('session.course', function ($q) use ($academicProfileIds) {
                $q->whereIn('assigned_teacher_id', $academicProfileIds);
            })->with(['student', 'session.course.assignedTeacher.user']);

            $this->applyFilters($interactiveQuery, $request, 'interactive');

            $interactiveRecords = $interactiveQuery->get()->map(function ($att) {
                return [
                    'id' => $att->id,
                    'date' => $att->session?->scheduled_at,
                    'student' => $att->student,
                    'teacher_name' => $att->session?->course?->assignedTeacher?->user?->name ?? '',
                    'session_type' => 'interactive',
                    'session_info' => $att->session?->course?->title ?? '',
                    'status' => $att->attendance_status,
                    'duration' => $att->attendance_duration_minutes,
                    'student_id' => $att->student_id,
                ];
            });
            $allRecords = $allRecords->merge($interactiveRecords);
        }

        // Apply type filter
        if ($typeFilter = $request->input('session_type')) {
            $allRecords = $allRecords->where('session_type', $typeFilter);
        }

        // Apply student search filter
        if ($studentSearch = $request->input('student')) {
            $studentSearch = mb_strtolower($studentSearch);
            $allRecords = $allRecords->filter(function ($r) use ($studentSearch) {
                return $r['student'] && str_contains(mb_strtolower($r['student']->name), $studentSearch);
            });
        }

        // Apply status filter
        if ($statusFilter = $request->input('status')) {
            $allRecords = $allRecords->where('status', $statusFilter);
        }

        // Stats (from unfiltered records before type/student/status filters)
        $totalRecords = $allRecords->count();
        $presentCount = $allRecords->where('status', AttendanceStatus::ATTENDED->value)->count();
        $absentCount = $allRecords->where('status', AttendanceStatus::ABSENT->value)->count();
        $lateCount = $allRecords->where('status', AttendanceStatus::LATE->value)->count();
        $attendanceRate = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100) : 0;

        // Chronic absentees: students absent more than 3 times this month
        $monthStart = now()->startOfMonth();
        $thisMonthRecords = $allRecords->filter(fn ($r) => $r['date'] && $r['date']->gte($monthStart));
        $absentCounts = $thisMonthRecords->where('status', AttendanceStatus::ABSENT->value)
            ->groupBy('student_id')
            ->map->count();
        $chronicAbsentees = $absentCounts->filter(fn ($count) => $count > 3)->count();

        // Sort by date desc
        $allRecords = $allRecords->sortByDesc('date')->values();

        // Paginate
        $perPage = 15;
        $page = $request->input('page', 1);
        $paginated = new LengthAwarePaginator(
            $allRecords->forPage($page, $perPage)->values(),
            $allRecords->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Teachers for filter
        $teacherIds = array_unique(array_merge($quranTeacherIds, $this->getAssignedAcademicTeacherIds()));
        $teachers = User::whereIn('id', $teacherIds)->get();

        return view('supervisor.attendance.index', [
            'records' => $paginated,
            'attendanceRate' => $attendanceRate,
            'presentCount' => $presentCount,
            'absentCount' => $absentCount,
            'lateCount' => $lateCount,
            'chronicAbsentees' => $chronicAbsentees,
            'teachers' => $teachers,
        ]);
    }

    private function applyFilters($query, Request $request, string $type): void
    {
        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereHas('session', function ($q) use ($request) {
                $q->whereDate('scheduled_at', '>=', $request->date_from);
            });
        }
        if ($request->filled('date_to')) {
            $query->whereHas('session', function ($q) use ($request) {
                $q->whereDate('scheduled_at', '<=', $request->date_to);
            });
        }

        // Teacher filter
        if ($teacherId = $request->input('teacher_id')) {
            if ($type === 'quran') {
                $query->whereHas('session', fn ($q) => $q->where('quran_teacher_id', $teacherId));
            } elseif ($type === 'academic') {
                $profileIds = \App\Models\AcademicTeacherProfile::where('user_id', $teacherId)->pluck('id')->toArray();
                if (! empty($profileIds)) {
                    $query->whereHas('session', fn ($q) => $q->whereIn('academic_teacher_id', $profileIds));
                }
            } elseif ($type === 'interactive') {
                $profileIds = \App\Models\AcademicTeacherProfile::where('user_id', $teacherId)->pluck('id')->toArray();
                if (! empty($profileIds)) {
                    $query->whereHas('session.course', fn ($q) => $q->whereIn('assigned_teacher_id', $profileIds));
                }
            }
        }
    }
}
