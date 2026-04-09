<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\AttendanceStatus;
use App\Models\MeetingAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupervisorAttendanceController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        // Gather all teacher user IDs the supervisor can see
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
        $allTeacherUserIds = array_unique(array_merge($quranTeacherIds, $academicTeacherIds));

        if (empty($allTeacherUserIds)) {
            return view('supervisor.attendance.index', [
                'records' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25),
                'stats' => $this->emptyStats(),
                'teachers' => collect(),
            ]);
        }

        // Build the base query on MeetingAttendance, scoped to supervised sessions
        $academicProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $baseQuery = MeetingAttendance::query()
            ->join('users', 'meeting_attendances.user_id', '=', 'users.id')
            ->where(function ($outer) use ($allTeacherUserIds, $academicProfileIds) {
                // Session must belong to one of the supervised teachers (OR across 3 session types)
                $outer->whereExists(function ($sub) use ($allTeacherUserIds) {
                    $sub->select(DB::raw(1))
                        ->from('quran_sessions')
                        ->whereColumn('quran_sessions.id', 'meeting_attendances.session_id')
                        ->whereIn('quran_sessions.quran_teacher_id', $allTeacherUserIds)
                        ->whereNotIn('meeting_attendances.session_type', ['academic', 'interactive']);
                });

                if (! empty($academicProfileIds)) {
                    $outer->orWhereExists(function ($sub) use ($academicProfileIds) {
                        $sub->select(DB::raw(1))
                            ->from('academic_sessions')
                            ->whereColumn('academic_sessions.id', 'meeting_attendances.session_id')
                            ->whereIn('academic_sessions.academic_teacher_id', $academicProfileIds)
                            ->where('meeting_attendances.session_type', 'academic');
                    });

                    $outer->orWhereExists(function ($sub) use ($academicProfileIds) {
                        $sub->select(DB::raw(1))
                            ->from('interactive_course_sessions')
                            ->join('interactive_courses', 'interactive_courses.id', '=', 'interactive_course_sessions.course_id')
                            ->whereColumn('interactive_course_sessions.id', 'meeting_attendances.session_id')
                            ->whereIn('interactive_courses.assigned_teacher_id', $academicProfileIds)
                            ->where('meeting_attendances.session_type', 'interactive');
                    });
                }
            });

        // Default to teacher tab
        $activeTab = $request->input('tab', 'teachers');

        // Apply tab filter (replaces participant_type dropdown)
        if ($activeTab === 'teachers') {
            $baseQuery->whereIn('meeting_attendances.user_type', ['teacher', 'quran_teacher', 'academic_teacher']);
        } else {
            $baseQuery->where('meeting_attendances.user_type', 'student');
        }

        // Apply filters
        $this->applyMeetingFilters($baseQuery, $request);

        // Calculate stats from the filtered set (before pagination)
        $stats = $this->calculateStats(clone $baseQuery);

        // Select fields and paginate
        $records = $baseQuery
            ->select([
                'meeting_attendances.*',
                'users.name as user_name',
            ])
            ->orderByDesc('meeting_attendances.first_join_time')
            ->orderByDesc('meeting_attendances.created_at')
            ->paginate(25)
            ->withQueryString();

        // Teachers for filter dropdown
        $teachers = User::whereIn('id', $allTeacherUserIds)->get();

        return view('supervisor.attendance.index', [
            'records' => $records,
            'stats' => $stats,
            'teachers' => $teachers,
            'activeTab' => $activeTab,
        ]);
    }

    private function applyMeetingFilters($query, Request $request): void
    {
        // Date range
        if ($request->filled('date_from')) {
            $query->where('meeting_attendances.created_at', '>=', $request->date_from.' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('meeting_attendances.created_at', '<=', $request->date_to.' 23:59:59');
        }

        // Session type
        if ($sessionType = $request->input('session_type')) {
            if ($sessionType === 'quran') {
                $query->whereNotIn('meeting_attendances.session_type', ['academic', 'interactive']);
            } else {
                $query->where('meeting_attendances.session_type', $sessionType);
            }
        }

        // Name search (matches user name)
        if ($search = $request->input('search')) {
            $query->where('users.name', 'like', '%'.$search.'%');
        }

        // Attendance status
        if ($status = $request->input('status')) {
            $query->where('meeting_attendances.attendance_status', $status);
        }

        // Calculated filter
        if ($request->filled('is_calculated')) {
            $query->where('meeting_attendances.is_calculated', $request->input('is_calculated') === 'yes');
        }
    }

    private function calculateStats($query): array
    {
        $attended = AttendanceStatus::ATTENDED->value;
        $absent = AttendanceStatus::ABSENT->value;
        $late = AttendanceStatus::LATE->value;

        $row = (clone $query)->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN meeting_attendances.attendance_status = ? THEN 1 ELSE 0 END) as attended,
            SUM(CASE WHEN meeting_attendances.attendance_status = ? THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN meeting_attendances.attendance_status = ? THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN meeting_attendances.is_calculated = 0 THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN meeting_attendances.user_type IN ('teacher','quran_teacher','academic_teacher') THEN 1 ELSE 0 END) as teacher_total,
            SUM(CASE WHEN meeting_attendances.user_type IN ('teacher','quran_teacher','academic_teacher') AND meeting_attendances.attendance_status = ? THEN 1 ELSE 0 END) as teacher_attended
        ", [$attended, $absent, $late, $attended])->first();

        $total = (int) ($row->total ?? 0);
        $teacherTotal = (int) ($row->teacher_total ?? 0);
        $teacherAttendedCount = (int) ($row->teacher_attended ?? 0);

        return [
            'total' => $total,
            'attended' => (int) ($row->attended ?? 0),
            'absent' => (int) ($row->absent ?? 0),
            'late' => (int) ($row->late ?? 0),
            'pending' => (int) ($row->pending ?? 0),
            'attendance_rate' => $total > 0 ? round(((int) $row->attended / $total) * 100) : 0,
            'teacher_total' => $teacherTotal,
            'teacher_attended' => $teacherAttendedCount,
            'teacher_rate' => $teacherTotal > 0 ? round(($teacherAttendedCount / $teacherTotal) * 100) : 0,
        ];
    }

    private function emptyStats(): array
    {
        return [
            'total' => 0,
            'attended' => 0,
            'absent' => 0,
            'late' => 0,
            'pending' => 0,
            'attendance_rate' => 0,
            'teacher_total' => 0,
            'teacher_attended' => 0,
            'teacher_rate' => 0,
        ];
    }
}
