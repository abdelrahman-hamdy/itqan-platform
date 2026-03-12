<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Models\InteractiveCourseHomework;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\QuranSessionHomework;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorHomeworkController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        // --- Quran homework ---
        $quranBaseQuery = QuranSessionHomework::whereHas('session', fn ($q) =>
            $q->whereIn('quran_teacher_id', $quranTeacherIds)
        );

        // Stats (before filters)
        $quranAllForStats = (clone $quranBaseQuery)
            ->with(['session.studentReport'])
            ->get();

        $quranStatsTotal = $quranAllForStats->count();
        $quranEvaluatedCount = $quranAllForStats->filter(fn ($hw) =>
            $hw->session?->studentReport &&
            ($hw->session->studentReport->new_memorization_degree !== null || $hw->session->studentReport->reservation_degree !== null)
        )->count();

        $evaluatedReports = $quranAllForStats
            ->map(fn ($hw) => $hw->session?->studentReport)
            ->filter(fn ($r) => $r && ($r->new_memorization_degree !== null || $r->reservation_degree !== null));

        $quranAvgScore = $evaluatedReports->count() > 0
            ? round($evaluatedReports->avg(fn ($r) => $r->overall_performance), 1)
            : null;

        $quranStats = [
            'total' => $quranStatsTotal,
            'evaluated' => $quranEvaluatedCount,
            'notEvaluated' => $quranStatsTotal - $quranEvaluatedCount,
            'avgScore' => $quranAvgScore,
        ];

        // Filtered + paginated query
        $quranQuery = QuranSessionHomework::whereHas('session', function ($q) use ($quranTeacherIds, $request) {
            $q->whereIn('quran_teacher_id', $quranTeacherIds);
            if ($request->filled('teacher_id')) {
                $q->where('quran_teacher_id', $request->teacher_id);
            }
            if ($request->filled('student_id')) {
                $q->where('student_id', $request->student_id);
            }
        })->with(['session.quranTeacher', 'session.student', 'session.studentReport']);

        if ($request->filled('date_from')) {
            $quranQuery->whereHas('session', fn ($q) => $q->whereDate('scheduled_at', '>=', $request->date_from));
        }
        if ($request->filled('date_to')) {
            $quranQuery->whereHas('session', fn ($q) => $q->whereDate('scheduled_at', '<=', $request->date_to));
        }

        $quranHomework = $quranQuery->latest()->paginate(15, ['*'], 'page_quran')->withQueryString();

        // --- Academic homework ---
        $academicBaseQuery = AcademicHomework::whereIn('teacher_id', $academicTeacherIds);

        $academicAllForStats = (clone $academicBaseQuery)->with('submissions')->get();
        $academicStats = [
            'total' => $academicAllForStats->count(),
            'pending' => $academicAllForStats->filter(fn ($hw) =>
                $hw->submissions->isEmpty() || $hw->submissions->every(fn ($s) => $this->submissionIsPending($s))
            )->count(),
            'graded' => $academicAllForStats->filter(fn ($hw) =>
                $hw->submissions->contains(fn ($s) => $s->score !== null)
            )->count(),
            'overdue' => $academicAllForStats->filter(fn ($hw) =>
                $hw->due_date && $hw->due_date->isPast() &&
                $hw->submissions->contains(fn ($s) => $s->score === null)
            )->count(),
        ];

        $academicQuery = AcademicHomework::whereIn('teacher_id', $academicTeacherIds)
            ->with(['session.academicTeacher.user', 'teacher', 'submissions.student']);

        if ($request->filled('teacher_id')) {
            $academicQuery->where('teacher_id', $request->teacher_id);
        }
        if ($request->filled('student_id')) {
            $academicQuery->whereHas('submissions', fn ($q) => $q->where('student_id', $request->student_id));
        }
        if ($request->filled('date_from')) {
            $academicQuery->whereDate('assigned_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $academicQuery->whereDate('assigned_at', '<=', $request->date_to);
        }

        $academicHomework = $academicQuery->latest()->paginate(15, ['*'], 'page_academic')->withQueryString();

        // --- Interactive homework ---
        $interactiveBaseQuery = InteractiveCourseHomework::whereIn('teacher_id', $academicTeacherIds);

        $interactiveAllForStats = (clone $interactiveBaseQuery)->with('submissions')->get();
        $interactiveStats = [
            'total' => $interactiveAllForStats->count(),
            'pending' => $interactiveAllForStats->filter(fn ($hw) =>
                $hw->submissions->isEmpty() || $hw->submissions->every(fn ($s) => $this->submissionIsPending($s))
            )->count(),
            'graded' => $interactiveAllForStats->filter(fn ($hw) =>
                $hw->submissions->contains(fn ($s) => $s->score !== null)
            )->count(),
            'overdue' => $interactiveAllForStats->filter(fn ($hw) =>
                $hw->due_date && $hw->due_date->isPast() &&
                $hw->submissions->contains(fn ($s) => $s->score === null)
            )->count(),
        ];

        $interactiveQuery = InteractiveCourseHomework::whereIn('teacher_id', $academicTeacherIds)
            ->with(['session.course', 'teacher', 'submissions.student']);

        if ($request->filled('teacher_id')) {
            $interactiveQuery->where('teacher_id', $request->teacher_id);
        }
        if ($request->filled('student_id')) {
            $interactiveQuery->whereHas('submissions', fn ($q) => $q->where('student_id', $request->student_id));
        }
        if ($request->filled('date_from')) {
            $interactiveQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $interactiveQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $interactiveHomework = $interactiveQuery->latest()->paginate(15, ['*'], 'page_interactive')->withQueryString();

        // Teachers for filter
        $allTeacherIds = $this->getAllAssignedTeacherIds();
        $teachers = User::whereIn('id', $allTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ])->toArray();

        // Students for filter — collect unique students from all homework types
        $studentIds = collect();
        // Quran students
        $studentIds = $studentIds->merge(
            $quranAllForStats->map(fn ($hw) => $hw->session?->student_id)->filter()
        );
        // Academic students
        $studentIds = $studentIds->merge(
            $academicAllForStats->flatMap(fn ($hw) => $hw->submissions->pluck('student_id'))->filter()
        );
        // Interactive students
        $studentIds = $studentIds->merge(
            $interactiveAllForStats->flatMap(fn ($hw) => $hw->submissions->pluck('student_id'))->filter()
        );

        $students = User::whereIn('id', $studentIds->unique())->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ])->toArray();

        return view('supervisor.homework.index', [
            'quranHomework' => $quranHomework,
            'academicHomework' => $academicHomework,
            'interactiveHomework' => $interactiveHomework,
            'quranStats' => $quranStats,
            'academicStats' => $academicStats,
            'interactiveStats' => $interactiveStats,
            'teachers' => $teachers,
            'students' => $students,
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

            return view('supervisor.homework.submissions', [
                'type' => $type,
                'homework' => $homework,
                'submissions' => $submissions,
                'homeworkTitle' => $homeworkTitle,
                'sessionInfo' => $sessionInfo,
                'session' => $homework->session,
                'reports' => collect(),
            ]);
        }

        if ($type === 'interactive') {
            $homework = InteractiveCourseHomework::with(['teacher', 'session.course'])->findOrFail($id);
            $submissions = InteractiveCourseHomeworkSubmission::where('interactive_course_homework_id', $id)
                ->with(['student', 'grader'])
                ->orderByDesc('submitted_at')
                ->get();
            $homeworkTitle = $homework->title ?? __('supervisor.homework.interactive_homework');
            $sessionInfo = $homework->session?->course?->title ?? '';

            return view('supervisor.homework.submissions', [
                'type' => $type,
                'homework' => $homework,
                'submissions' => $submissions,
                'homeworkTitle' => $homeworkTitle,
                'sessionInfo' => $sessionInfo,
                'session' => $homework->session,
                'reports' => collect(),
            ]);
        }

        if ($type === 'quran') {
            $homework = QuranSessionHomework::with([
                'session.quranTeacher',
                'session.student',
                'session.studentReports',
            ])->findOrFail($id);

            $session = $homework->session;
            $reports = $session ? $session->studentReports : collect();
            $homeworkTitle = $homework->new_memorization_range
                ?? $homework->review_range
                ?? __('supervisor.homework.quran_homework_details');
            $sessionInfo = $session?->quranTeacher?->name ?? '';

            return view('supervisor.homework.submissions', [
                'type' => $type,
                'homework' => $homework,
                'submissions' => collect(),
                'homeworkTitle' => $homeworkTitle,
                'sessionInfo' => $sessionInfo,
                'session' => $session,
                'reports' => $reports,
            ]);
        }

        abort(404);
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

    private function submissionIsPending($submission): bool
    {
        $status = $submission->submission_status instanceof \App\Enums\HomeworkSubmissionStatus
            ? $submission->submission_status->value
            : $submission->submission_status;

        return in_array($status, ['pending', 'draft']);
    }
}
