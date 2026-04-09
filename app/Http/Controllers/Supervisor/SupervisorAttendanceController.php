<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\AttendanceStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\SessionCountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupervisorAttendanceController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
        $allTeacherUserIds = array_unique(array_merge($quranTeacherIds, $academicTeacherIds));

        $activeTab = in_array($request->input('tab'), ['teachers', 'students']) ? $request->input('tab') : 'teachers';

        if (empty($allTeacherUserIds)) {
            return view('supervisor.attendance.index', [
                'records' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25),
                'stats' => $this->emptyStats(),
                'teacherOptions' => [],
                'activeTab' => $activeTab,
            ]);
        }

        $academicProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $baseQuery = MeetingAttendance::query()
            ->join('users', 'meeting_attendances.user_id', '=', 'users.id')
            ->where(function ($outer) use ($allTeacherUserIds, $academicProfileIds) {
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

        // Tab filter
        if ($activeTab === 'teachers') {
            $baseQuery->whereIn('meeting_attendances.user_type', ['teacher', 'quran_teacher', 'academic_teacher']);
        } else {
            $baseQuery->where('meeting_attendances.user_type', 'student');
        }

        $this->applyMeetingFilters($baseQuery, $request, $activeTab);

        $stats = $this->calculateStats(clone $baseQuery, $activeTab);

        // Select counted field based on tab
        $selectFields = ['meeting_attendances.*', 'users.name as user_name'];

        if ($activeTab === 'teachers') {
            // Left join all 3 session tables to get counts_for_teacher
            $baseQuery->leftJoin('quran_sessions', function ($join) {
                $join->on('quran_sessions.id', '=', 'meeting_attendances.session_id')
                    ->whereNotIn('meeting_attendances.session_type', ['academic', 'interactive']);
            })
                ->leftJoin('academic_sessions', function ($join) {
                    $join->on('academic_sessions.id', '=', 'meeting_attendances.session_id')
                        ->where('meeting_attendances.session_type', 'academic');
                })
                ->leftJoin('interactive_course_sessions', function ($join) {
                    $join->on('interactive_course_sessions.id', '=', 'meeting_attendances.session_id')
                        ->where('meeting_attendances.session_type', 'interactive');
                });

            $selectFields[] = DB::raw('COALESCE(quran_sessions.counts_for_teacher, academic_sessions.counts_for_teacher, interactive_course_sessions.counts_for_teacher) as is_counted');
        } else {
            $selectFields[] = DB::raw('meeting_attendances.counts_for_subscription as is_counted');
        }

        $records = $baseQuery
            ->select($selectFields)
            ->orderByDesc('meeting_attendances.first_join_time')
            ->orderByDesc('meeting_attendances.created_at')
            ->paginate(25)
            ->withQueryString();

        // Build teacher options for searchable-select (same format as calendar)
        $teacherOptions = $this->buildTeacherOptions($quranTeacherIds, $academicTeacherIds);

        return view('supervisor.attendance.index', [
            'records' => $records,
            'stats' => $stats,
            'teacherOptions' => $teacherOptions,
            'activeTab' => $activeTab,
        ]);
    }

    public function toggleCounted(Request $request, $subdomain, int $id): RedirectResponse
    {
        if (! $this->canMonitorSessions()) {
            abort(403);
        }

        $attendance = MeetingAttendance::findOrFail($id);

        // Verify attendance belongs to a session under this supervisor's scope
        $session = $this->resolveSession($attendance);
        if (! $session) {
            abort(404);
        }

        $countingService = app(SessionCountingService::class);
        $adminId = auth()->id();

        $isTeacher = in_array($attendance->user_type, ['teacher', 'quran_teacher', 'academic_teacher']);

        if ($isTeacher) {
            $newValue = ! (bool) $session->counts_for_teacher;
            $countingService->setCountsForTeacher($session, $newValue, $adminId);
        } else {
            $newValue = ! (bool) $attendance->counts_for_subscription;
            $countingService->setCountsForSubscription($attendance, $session, $newValue, $adminId);
        }

        return redirect()->back();
    }

    private function resolveSession(MeetingAttendance $attendance)
    {
        return match ($attendance->session_type) {
            'individual', 'group', 'trial' => QuranSession::withoutGlobalScopes()->find($attendance->session_id),
            'academic' => AcademicSession::withoutGlobalScopes()->find($attendance->session_id),
            'interactive' => InteractiveCourseSession::withoutGlobalScopes()->find($attendance->session_id),
            default => null,
        };
    }

    private function applyMeetingFilters($query, Request $request, string $activeTab): void
    {
        if ($request->filled('date_from')) {
            $query->where('meeting_attendances.created_at', '>=', $request->date_from.' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('meeting_attendances.created_at', '<=', $request->date_to.' 23:59:59');
        }

        if ($sessionType = $request->input('session_type')) {
            if ($sessionType === 'quran') {
                $query->whereNotIn('meeting_attendances.session_type', ['academic', 'interactive']);
            } else {
                $query->where('meeting_attendances.session_type', $sessionType);
            }
        }

        if ($search = $request->input('search')) {
            $query->where('users.name', 'like', '%'.$search.'%');
        }

        if ($status = $request->input('status')) {
            $query->where('meeting_attendances.attendance_status', $status);
        }

        // Teacher filter (from searchable-select)
        if ($teacherId = $request->input('teacher_id')) {
            // Scope to sessions of this specific teacher
            $this->filterByTeacher($query, (int) $teacherId);
        }

        // Counted filter
        if ($request->filled('counted')) {
            $countedValue = $request->input('counted') === 'yes';

            if ($activeTab === 'teachers') {
                // Filter by counts_for_teacher from session tables (use subquery EXISTS)
                $query->where(function ($q) use ($countedValue) {
                    $q->whereExists(function ($sub) use ($countedValue) {
                        $sub->select(DB::raw(1))->from('quran_sessions')
                            ->whereColumn('quran_sessions.id', 'meeting_attendances.session_id')
                            ->where('quran_sessions.counts_for_teacher', $countedValue);
                    })->orWhereExists(function ($sub) use ($countedValue) {
                        $sub->select(DB::raw(1))->from('academic_sessions')
                            ->whereColumn('academic_sessions.id', 'meeting_attendances.session_id')
                            ->where('academic_sessions.counts_for_teacher', $countedValue);
                    })->orWhereExists(function ($sub) use ($countedValue) {
                        $sub->select(DB::raw(1))->from('interactive_course_sessions')
                            ->whereColumn('interactive_course_sessions.id', 'meeting_attendances.session_id')
                            ->where('interactive_course_sessions.counts_for_teacher', $countedValue);
                    });
                });
            } else {
                if ($countedValue) {
                    $query->where('meeting_attendances.counts_for_subscription', true);
                } else {
                    $query->where(function ($q) {
                        $q->where('meeting_attendances.counts_for_subscription', false)
                            ->orWhereNull('meeting_attendances.counts_for_subscription');
                    });
                }
            }
        }
    }

    private function filterByTeacher($query, int $teacherId): void
    {
        $academicProfileIds = \App\Models\AcademicTeacherProfile::where('user_id', $teacherId)->pluck('id')->toArray();

        $query->where(function ($q) use ($teacherId, $academicProfileIds) {
            $q->whereExists(function ($sub) use ($teacherId) {
                $sub->select(DB::raw(1))->from('quran_sessions')
                    ->whereColumn('quran_sessions.id', 'meeting_attendances.session_id')
                    ->where('quran_sessions.quran_teacher_id', $teacherId);
            });

            if (! empty($academicProfileIds)) {
                $q->orWhereExists(function ($sub) use ($academicProfileIds) {
                    $sub->select(DB::raw(1))->from('academic_sessions')
                        ->whereColumn('academic_sessions.id', 'meeting_attendances.session_id')
                        ->whereIn('academic_sessions.academic_teacher_id', $academicProfileIds);
                })->orWhereExists(function ($sub) use ($academicProfileIds) {
                    $sub->select(DB::raw(1))->from('interactive_course_sessions')
                        ->join('interactive_courses', 'interactive_courses.id', '=', 'interactive_course_sessions.course_id')
                        ->whereColumn('interactive_course_sessions.id', 'meeting_attendances.session_id')
                        ->whereIn('interactive_courses.assigned_teacher_id', $academicProfileIds);
                });
            }
        });
    }

    private function calculateStats($query, string $activeTab): array
    {
        $attended = AttendanceStatus::ATTENDED->value;
        $absent = AttendanceStatus::ABSENT->value;
        $late = AttendanceStatus::LATE->value;

        if ($activeTab === 'teachers') {
            $row = (clone $query)->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN meeting_attendances.attendance_status = ? THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN meeting_attendances.attendance_status = ? THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN meeting_attendances.attendance_status = ? THEN 1 ELSE 0 END) as late
            ', [$attended, $absent, $late])->first();

            // Counted stats via subquery (counts_for_teacher from session tables)
            $countedQuery = clone $query;
            $counted = (clone $countedQuery)->where(function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))->from('quran_sessions')
                        ->whereColumn('quran_sessions.id', 'meeting_attendances.session_id')
                        ->where('quran_sessions.counts_for_teacher', true);
                })->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))->from('academic_sessions')
                        ->whereColumn('academic_sessions.id', 'meeting_attendances.session_id')
                        ->where('academic_sessions.counts_for_teacher', true);
                })->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))->from('interactive_course_sessions')
                        ->whereColumn('interactive_course_sessions.id', 'meeting_attendances.session_id')
                        ->where('interactive_course_sessions.counts_for_teacher', true);
                });
            })->count();
        } else {
            $row = (clone $query)->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN meeting_attendances.attendance_status = ? THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN meeting_attendances.attendance_status = ? THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN meeting_attendances.attendance_status = ? THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN meeting_attendances.counts_for_subscription = 1 THEN 1 ELSE 0 END) as counted
            ', [$attended, $absent, $late])->first();

            $counted = (int) ($row->counted ?? 0);
        }

        $total = (int) ($row->total ?? 0);

        return [
            'total' => $total,
            'attended' => (int) ($row->attended ?? 0),
            'absent' => (int) ($row->absent ?? 0),
            'late' => (int) ($row->late ?? 0),
            'attendance_rate' => $total > 0 ? round(((int) $row->attended / $total) * 100) : 0,
            'counted' => $counted,
            'not_counted' => $total - $counted,
        ];
    }

    private function buildTeacherOptions(array $quranTeacherIds, array $academicTeacherIds): array
    {
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
                    'type_label' => __('supervisor.attendance.quran'),
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
                    'type_label' => __('supervisor.attendance.academic'),
                ]);
            $teachers = $teachers->merge($academicTeachers);
        }

        return $teachers->values()->toArray();
    }

    private function emptyStats(): array
    {
        return [
            'total' => 0,
            'attended' => 0,
            'absent' => 0,
            'late' => 0,
            'attendance_rate' => 0,
            'counted' => 0,
            'not_counted' => 0,
        ];
    }
}
