<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicSessionReport;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorSessionReportsController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $reports = collect();

        // Quran session reports
        if (!empty($quranTeacherIds)) {
            $quranReports = StudentSessionReport::whereHas('session', function ($q) use ($quranTeacherIds) {
                $q->whereIn('quran_teacher_id', $quranTeacherIds);
            })->with(['session.quranTeacher', 'student'])
              ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
              ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
              ->when($request->attendance_status, fn ($q) => $q->where('attendance_status', $request->attendance_status))
              ->latest()
              ->limit(100)
              ->get()
              ->map(fn ($r) => [
                  'id' => $r->id,
                  'type' => 'quran',
                  'student_name' => $r->student?->name ?? '',
                  'teacher_name' => $r->session?->quranTeacher?->name ?? '',
                  'attendance_status' => $r->attendance_status,
                  'created_at' => $r->created_at,
                  'session_date' => $r->session?->scheduled_at,
              ]);
            $reports = $reports->merge($quranReports);
        }

        // Academic session reports
        if (!empty($academicTeacherProfileIds)) {
            $academicReports = AcademicSessionReport::whereHas('session', function ($q) use ($academicTeacherProfileIds) {
                $q->whereIn('academic_teacher_id', $academicTeacherProfileIds);
            })->with(['session.academicTeacher.user', 'student'])
              ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
              ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
              ->when($request->attendance_status, fn ($q) => $q->where('attendance_status', $request->attendance_status))
              ->latest()
              ->limit(100)
              ->get()
              ->map(fn ($r) => [
                  'id' => $r->id,
                  'type' => 'academic',
                  'student_name' => $r->student?->name ?? '',
                  'teacher_name' => $r->session?->academicTeacher?->user?->name ?? '',
                  'attendance_status' => $r->attendance_status,
                  'created_at' => $r->created_at,
                  'session_date' => $r->session?->scheduled_at,
              ]);
            $reports = $reports->merge($academicReports);
        }

        $reports = $reports->sortByDesc('created_at')->values();

        // Stats
        $totalReports = $reports->count();
        $presentCount = $reports->where('attendance_status', 'present')->count();
        $absentCount = $reports->where('attendance_status', 'absent')->count();
        $lateCount = $reports->where('attendance_status', 'late')->count();

        // Teacher filter
        $allTeacherIds = $this->getAllAssignedTeacherIds();
        $teachers = User::whereIn('id', $allTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ])->toArray();

        return view('supervisor.session-reports.index', compact(
            'reports', 'teachers', 'totalReports', 'presentCount', 'absentCount', 'lateCount'
        ));
    }
}
