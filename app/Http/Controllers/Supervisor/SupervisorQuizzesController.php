<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorQuizzesController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $allTeacherIds = $this->getAllAssignedTeacherIds();

        $query = Quiz::whereIn('created_by', $allTeacherIds)
            ->with(['creator'])
            ->withCount(['questions', 'assignments']);

        if ($request->teacher_id) {
            $query->where('created_by', $request->teacher_id);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        if ($request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $quizzes = $query->latest()->paginate(15)->withQueryString();

        // Stats
        $totalQuizzes = Quiz::whereIn('created_by', $allTeacherIds)->count();
        $activeQuizzes = Quiz::whereIn('created_by', $allTeacherIds)->where('is_active', true)->count();
        $totalAssignments = QuizAssignment::whereHas('quiz', fn ($q) => $q->whereIn('created_by', $allTeacherIds))->count();
        $totalAttempts = QuizAttempt::whereHas('assignment.quiz', fn ($q) => $q->whereIn('created_by', $allTeacherIds))->count();

        // Teacher filter dropdown
        $teachers = $this->getTeachersForFilter($allTeacherIds);

        return view('supervisor.quizzes.index', compact('quizzes', 'teachers', 'totalQuizzes', 'activeQuizzes', 'totalAssignments', 'totalAttempts'));
    }

    public function show($subdomain, $quizId): View
    {
        $allTeacherIds = $this->getAllAssignedTeacherIds();

        $quiz = Quiz::whereIn('created_by', $allTeacherIds)
            ->with(['creator', 'questions', 'assignments.assignable', 'assignments.attempts'])
            ->withCount(['questions', 'assignments'])
            ->findOrFail($quizId);

        $teacher = User::find($quiz->created_by);

        return view('supervisor.quizzes.show', compact('quiz', 'teacher'));
    }

    private function getTeachersForFilter(array $teacherIds): array
    {
        $quranIds = $this->getAssignedQuranTeacherIds();
        $academicIds = $this->getAssignedAcademicTeacherIds();

        return User::whereIn('id', $teacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'type_label' => in_array($u->id, $quranIds)
                ? __('supervisor.teachers.teacher_type_quran')
                : __('supervisor.teachers.teacher_type_academic'),
        ])->toArray();
    }
}
