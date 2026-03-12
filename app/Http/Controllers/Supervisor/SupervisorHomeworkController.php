<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\HomeworkSubmissionStatus;
use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicSession;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseHomework;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\QuranSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SupervisorHomeworkController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        $items = collect();

        // 1. Quran homework: sessions with homework assigned (via relationship or legacy flag)
        $quranQuery = QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
            ->where(function ($q) {
                $q->where('homework_assigned', true)
                  ->orWhereHas('sessionHomework');
            })
            ->with(['quranTeacher', 'student', 'sessionHomework']);

        if ($request->filled('teacher_id')) {
            $quranQuery->where('quran_teacher_id', $request->teacher_id);
        }
        if ($request->filled('date_from')) {
            $quranQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $quranQuery->whereDate('created_at', '<=', $request->date_to);
        }

        if (! $request->filled('type') || $request->type === 'quran') {
            foreach ($quranQuery->get() as $session) {
                $items->push([
                    'id' => $session->id,
                    'type' => 'quran',
                    'type_label' => __('supervisor.homework.type_quran'),
                    'session_info' => $session->title ?? __('supervisor.homework.quran_session') . ' #' . $session->id,
                    'teacher_name' => $session->quranTeacher?->name ?? '-',
                    'teacher_id' => $session->quran_teacher_id,
                    'student_names' => $session->student?->name ?? '-',
                    'assigned_date' => $session->updated_at,
                    'due_date' => null,
                    'status' => 'assigned',
                    'status_label' => __('supervisor.homework.status_assigned'),
                    'submissions_count' => 0,
                    'has_submissions' => false,
                ]);
            }
        }

        // 2. Academic homework: formal AcademicHomework records
        if (! $request->filled('type') || $request->type === 'academic') {
            $academicQuery = AcademicHomework::whereIn('teacher_id', $academicTeacherIds)
                ->with(['session.academicTeacher.user', 'teacher', 'submissions']);

            if ($request->filled('teacher_id')) {
                $academicQuery->where('teacher_id', $request->teacher_id);
            }
            if ($request->filled('date_from')) {
                $academicQuery->whereDate('assigned_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $academicQuery->whereDate('assigned_at', '<=', $request->date_to);
            }

            foreach ($academicQuery->get() as $homework) {
                $isOverdue = $homework->due_date && $homework->due_date->isPast() && $homework->graded_count < $homework->submitted_count;
                $status = $homework->graded_count > 0 ? 'graded' : ($isOverdue ? 'overdue' : 'pending');

                $items->push([
                    'id' => $homework->id,
                    'type' => 'academic',
                    'type_label' => __('supervisor.homework.type_academic'),
                    'session_info' => $homework->title ?? __('supervisor.homework.academic_homework') . ' #' . $homework->id,
                    'teacher_name' => $homework->teacher?->name ?? '-',
                    'teacher_id' => $homework->teacher_id,
                    'student_names' => $homework->submissions->pluck('student.name')->filter()->implode(', ') ?: '-',
                    'assigned_date' => $homework->assigned_at ?? $homework->created_at,
                    'due_date' => $homework->due_date,
                    'status' => $status,
                    'status_label' => __('supervisor.homework.status_' . $status),
                    'submissions_count' => $homework->submitted_count ?? $homework->submissions->count(),
                    'has_submissions' => true,
                    'session_id' => $homework->academic_session_id,
                ]);
            }
        }

        // 3. Interactive course homework
        if (! $request->filled('type') || $request->type === 'interactive') {
            $interactiveQuery = InteractiveCourseHomework::whereIn('teacher_id', $academicTeacherIds)
                ->with(['session.course', 'teacher', 'submissions']);

            if ($request->filled('teacher_id')) {
                $interactiveQuery->where('teacher_id', $request->teacher_id);
            }
            if ($request->filled('date_from')) {
                $interactiveQuery->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $interactiveQuery->whereDate('created_at', '<=', $request->date_to);
            }

            foreach ($interactiveQuery->get() as $homework) {
                $isOverdue = $homework->due_date && $homework->due_date->isPast() && $homework->graded_count < $homework->submitted_count;
                $status = $homework->graded_count > 0 ? 'graded' : ($isOverdue ? 'overdue' : 'pending');

                $items->push([
                    'id' => $homework->id,
                    'type' => 'interactive',
                    'type_label' => __('supervisor.homework.type_interactive'),
                    'session_info' => $homework->title ?? ($homework->session?->course?->title ?? __('supervisor.homework.interactive_homework')) . ' #' . $homework->id,
                    'teacher_name' => $homework->teacher?->name ?? '-',
                    'teacher_id' => $homework->teacher_id,
                    'student_names' => '-',
                    'assigned_date' => $homework->created_at,
                    'due_date' => $homework->due_date,
                    'status' => $status,
                    'status_label' => __('supervisor.homework.status_' . $status),
                    'submissions_count' => $homework->submitted_count ?? $homework->submissions->count(),
                    'has_submissions' => true,
                ]);
            }
        }

        // Sort by assigned_date descending
        $items = $items->sortByDesc('assigned_date')->values();

        // Stats
        $totalAssigned = $items->count();
        $pendingCount = $items->where('status', 'pending')->count();
        $gradedCount = $items->where('status', 'graded')->count();
        $overdueCount = $items->where('status', 'overdue')->count();

        // Manual pagination
        $page = $request->get('page', 1);
        $perPage = 15;
        $paginated = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Teachers for filter
        $allTeacherIds = $this->getAllAssignedTeacherIds();
        $teachers = User::whereIn('id', $allTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ])->toArray();

        return view('supervisor.homework.index', [
            'homework' => $paginated,
            'totalAssigned' => $totalAssigned,
            'pendingCount' => $pendingCount,
            'gradedCount' => $gradedCount,
            'overdueCount' => $overdueCount,
            'teachers' => $teachers,
        ]);
    }

    public function submissions(Request $request, $subdomain = null, $type = null, $id = null): View
    {
        if ($type === 'academic') {
            $homework = AcademicHomework::with(['teacher', 'session'])->findOrFail($id);
            $submissions = AcademicHomeworkSubmission::where('academic_homework_id', $id)
                ->with(['student', 'grader'])
                ->orderByDesc('submitted_at')
                ->get();
            $homeworkTitle = $homework->title ?? __('supervisor.homework.academic_homework');
            $sessionInfo = $homework->session?->session_code ?? '';
        } elseif ($type === 'interactive') {
            $homework = InteractiveCourseHomework::with(['teacher', 'session.course'])->findOrFail($id);
            $submissions = InteractiveCourseHomeworkSubmission::where('interactive_course_homework_id', $id)
                ->with(['student', 'grader'])
                ->orderByDesc('submitted_at')
                ->get();
            $homeworkTitle = $homework->title ?? __('supervisor.homework.interactive_homework');
            $sessionInfo = $homework->session?->course?->title ?? '';
        } elseif ($type === 'quran') {
            // Quran sessions don't have formal submissions — show session info
            $session = QuranSession::with(['quranTeacher', 'student'])->findOrFail($id);
            $submissions = collect();
            $homework = null;
            $homeworkTitle = $session->title ?? __('supervisor.homework.quran_session') . ' #' . $session->id;
            $sessionInfo = $session->quranTeacher?->name ?? '';
        } else {
            abort(404);
        }

        return view('supervisor.homework.submissions', [
            'type' => $type,
            'homework' => $homework,
            'submissions' => $submissions,
            'homeworkTitle' => $homeworkTitle,
            'sessionInfo' => $sessionInfo,
            'session' => $type === 'quran' ? ($session ?? null) : null,
        ]);
    }

    public function grade(Request $request, $subdomain = null, $submissionId = null)
    {
        $request->validate([
            'score' => 'required|numeric|min:0|max:10',
            'teacher_feedback' => 'nullable|string|max:2000',
            'type' => 'required|in:academic,interactive',
        ]);

        if ($request->type === 'academic') {
            $submission = AcademicHomeworkSubmission::findOrFail($submissionId);
        } else {
            $submission = InteractiveCourseHomeworkSubmission::findOrFail($submissionId);
        }

        $submission->grade(
            score: (float) $request->score,
            feedback: $request->teacher_feedback,
            gradedBy: auth()->id(),
        );

        return redirect()->back()->with('success', __('supervisor.homework.graded_successfully'));
    }
}
