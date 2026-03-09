<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Services\AcademyContextService;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuizManagementController extends Controller
{
    /**
     * Map short assignable type names to fully qualified class names.
     */
    private const TYPE_MAP = [
        'quran_circle' => QuranCircle::class,
        'quran_individual_circle' => QuranIndividualCircle::class,
        'academic_lesson' => AcademicIndividualLesson::class,
        'interactive_course' => InteractiveCourse::class,
    ];

    public function __construct(
        private QuizService $quizService,
    ) {
        $this->middleware('auth');
    }

    /**
     * Display list of quizzes created by the authenticated teacher.
     */
    public function index(Request $request, $subdomain = null): View
    {
        $user = Auth::user();

        $query = Quiz::where('created_by', $user->id)
            ->withCount(['questions', 'assignments']);

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->input('search') . '%');
        }

        $quizzes = $query->latest()->paginate(12)->withQueryString();

        // Aggregate stats
        $totalQuizzes = Quiz::where('created_by', $user->id)->count();
        $activeQuizzes = Quiz::where('created_by', $user->id)->where('is_active', true)->count();
        $totalAssignments = QuizAssignment::whereHas('quiz', fn ($q) => $q->where('created_by', $user->id))->count();
        $totalAttempts = QuizAttempt::whereHas('assignment.quiz', fn ($q) => $q->where('created_by', $user->id))->count();

        return view('teacher.quizzes.index', compact(
            'quizzes',
            'totalQuizzes',
            'activeQuizzes',
            'totalAssignments',
            'totalAttempts',
        ));
    }

    /**
     * Show the quiz creation form.
     */
    public function create($subdomain = null): View
    {
        return view('teacher.quizzes.create');
    }

    /**
     * Store a newly created quiz with its questions.
     */
    public function store(Request $request, $subdomain = null)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'passing_score' => ['required', 'integer', 'min:10', 'max:90'],
            'is_active' => ['boolean'],
            'randomize_questions' => ['boolean'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.options.*' => ['required', 'string', 'max:500'],
            'questions.*.correct_option' => ['required', 'integer', 'min:0'],
        ]);

        // Validate that correct_option index is within options bounds for each question
        foreach ($validated['questions'] as $index => $question) {
            if ($question['correct_option'] >= count($question['options'])) {
                return back()->withErrors([
                    "questions.{$index}.correct_option" => __('quiz.correct_option_out_of_bounds'),
                ])->withInput();
            }
        }

        $quizData = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'passing_score' => $validated['passing_score'],
            'is_active' => $validated['is_active'] ?? true,
            'randomize_questions' => $validated['randomize_questions'] ?? false,
            'created_by' => Auth::id(),
            'academy_id' => AcademyContextService::getCurrentAcademyId(),
        ];

        $quiz = $this->quizService->createQuiz($quizData, $validated['questions']);

        return redirect()
            ->route('teacher.quizzes.show', [
                'subdomain' => $subdomain ?? request()->route('subdomain'),
                'quiz' => $quiz->id,
            ])
            ->with('success', __('quiz.created_successfully'));
    }

    /**
     * Display a single quiz with its questions, assignments, and statistics.
     */
    public function show($subdomain, $quiz): View
    {
        $quiz = Quiz::where('id', $quiz)
            ->where('created_by', Auth::id())
            ->with([
                'questions',
                'assignments.assignable',
                'assignments.attempts',
            ])
            ->firstOrFail();

        // Compute per-assignment statistics
        $assignmentStats = $quiz->assignments->map(function (QuizAssignment $assignment) {
            return [
                'assignment' => $assignment,
                'stats' => $this->quizService->getAssignmentStatistics($assignment),
            ];
        });

        // Assignable options for the assign form
        $assignableOptions = $this->getTeacherAssignableOptions();

        return view('teacher.quizzes.show', compact(
            'quiz',
            'assignmentStats',
            'assignableOptions',
        ));
    }

    /**
     * Show the quiz edit form.
     */
    public function edit($subdomain, $quiz): View
    {
        $quiz = Quiz::where('id', $quiz)
            ->where('created_by', Auth::id())
            ->with('questions')
            ->firstOrFail();

        return view('teacher.quizzes.edit', compact('quiz'));
    }

    /**
     * Update an existing quiz and sync its questions.
     */
    public function update(Request $request, $subdomain, $quiz)
    {
        $quiz = Quiz::where('id', $quiz)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'passing_score' => ['required', 'integer', 'min:10', 'max:90'],
            'is_active' => ['boolean'],
            'randomize_questions' => ['boolean'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['nullable', 'string'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.options.*' => ['required', 'string', 'max:500'],
            'questions.*.correct_option' => ['required', 'integer', 'min:0'],
        ]);

        // Validate correct_option bounds
        foreach ($validated['questions'] as $index => $question) {
            if ($question['correct_option'] >= count($question['options'])) {
                return back()->withErrors([
                    "questions.{$index}.correct_option" => __('quiz.correct_option_out_of_bounds'),
                ])->withInput();
            }
        }

        DB::transaction(function () use ($quiz, $validated) {
            // Update quiz fields
            $quiz->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'passing_score' => $validated['passing_score'],
                'is_active' => $validated['is_active'] ?? true,
                'randomize_questions' => $validated['randomize_questions'] ?? false,
            ]);

            // Collect submitted question IDs (existing ones being updated)
            $submittedIds = collect($validated['questions'])
                ->pluck('id')
                ->filter()
                ->all();

            // Delete questions that were removed from the form
            $quiz->questions()
                ->whereNotIn('id', $submittedIds)
                ->delete();

            // Update existing and create new questions
            foreach ($validated['questions'] as $order => $questionData) {
                if (! empty($questionData['id'])) {
                    // Update existing question (ensure it belongs to this quiz)
                    $quiz->questions()
                        ->where('id', $questionData['id'])
                        ->update([
                            'question_text' => $questionData['question_text'],
                            'options' => $questionData['options'],
                            'correct_option' => $questionData['correct_option'],
                            'order' => $order,
                        ]);
                } else {
                    // Create new question
                    $quiz->questions()->create([
                        'question_text' => $questionData['question_text'],
                        'options' => $questionData['options'],
                        'correct_option' => $questionData['correct_option'],
                        'order' => $order,
                    ]);
                }
            }
        });

        return redirect()
            ->route('teacher.quizzes.show', [
                'subdomain' => $subdomain ?? request()->route('subdomain'),
                'quiz' => $quiz->id,
            ])
            ->with('success', __('quiz.updated_successfully'));
    }

    /**
     * Delete a quiz if no attempts have been made.
     */
    public function destroy($subdomain, $quiz)
    {
        $quiz = Quiz::where('id', $quiz)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        // Prevent deletion if any attempts exist (through assignments)
        $attemptCount = QuizAttempt::whereHas('assignment', fn ($q) => $q->where('quiz_id', $quiz->id))->count();

        if ($attemptCount > 0) {
            return back()->with('error', __('quiz.cannot_delete_has_attempts'));
        }

        // Cascading deletes handle questions and assignments (via DB foreign keys)
        $quiz->delete();

        return redirect()
            ->route('teacher.quizzes.index', [
                'subdomain' => $subdomain ?? request()->route('subdomain'),
            ])
            ->with('success', __('quiz.deleted_successfully'));
    }

    /**
     * Assign a quiz to an assignable entity (circle, lesson, course).
     */
    public function assign(Request $request, $subdomain, $quiz): JsonResponse
    {
        $quiz = Quiz::where('id', $quiz)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        $validated = $request->validate([
            'assignable_type' => ['required', 'string', 'in:quran_circle,quran_individual_circle,academic_lesson,interactive_course'],
            'assignable_id' => ['required', 'string'],
            'is_visible' => ['boolean'],
            'max_attempts' => ['integer', 'min:1', 'max:10'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after:available_from'],
        ]);

        $fullClassName = self::TYPE_MAP[$validated['assignable_type']] ?? null;

        if (! $fullClassName) {
            return response()->json([
                'success' => false,
                'message' => __('quiz.invalid_assignable_type'),
            ], 422);
        }

        // Verify teacher owns the assignable entity
        $teacherAssignableIds = $this->getTeacherAssignableIds();
        $ownedIds = $teacherAssignableIds[$fullClassName] ?? [];

        if (! in_array($validated['assignable_id'], $ownedIds)) {
            return response()->json([
                'success' => false,
                'message' => __('quiz.not_authorized_for_entity'),
            ], 403);
        }

        $assignable = $fullClassName::findOrFail($validated['assignable_id']);

        $assignment = $this->quizService->assignQuiz($quiz, $assignable, [
            'is_visible' => $validated['is_visible'] ?? true,
            'max_attempts' => $validated['max_attempts'] ?? 1,
            'available_from' => $validated['available_from'] ?? null,
            'available_until' => $validated['available_until'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('quiz.assigned_successfully'),
            'assignment' => $assignment,
        ]);
    }

    /**
     * Revoke (delete) a quiz assignment if no completed attempts exist.
     */
    public function revokeAssignment(Request $request, $subdomain, $assignmentId): JsonResponse
    {
        $assignment = QuizAssignment::with('quiz')
            ->where('id', $assignmentId)
            ->whereHas('quiz', fn ($q) => $q->where('created_by', Auth::id()))
            ->firstOrFail();

        // Prevent revocation if completed attempts exist
        $completedCount = $assignment->attempts()
            ->whereNotNull('submitted_at')
            ->count();

        if ($completedCount > 0) {
            return response()->json([
                'success' => false,
                'message' => __('quiz.cannot_revoke_has_completed_attempts'),
            ], 422);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => __('quiz.assignment_revoked_successfully'),
        ]);
    }

    /**
     * AJAX endpoint: return assignable options the teacher owns, filtered by type.
     */
    public function getAssignableOptions(Request $request, $subdomain = null): JsonResponse
    {
        $type = $request->input('type');

        // Resolve full class name from short name or accept full class name
        $fullClassName = self::TYPE_MAP[$type] ?? null;

        if (! $fullClassName) {
            return response()->json([
                'success' => false,
                'message' => __('quiz.invalid_assignable_type'),
            ], 422);
        }

        $user = Auth::user();
        $options = [];

        if ($fullClassName === QuranCircle::class && $user->isQuranTeacher()) {
            $options = QuranCircle::where('quran_teacher_id', $user->id)
                ->select('id', 'name')
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
                ->all();

        } elseif ($fullClassName === QuranIndividualCircle::class && $user->isQuranTeacher()) {
            $options = QuranIndividualCircle::where('quran_teacher_id', $user->id)
                ->with('student:id,first_name,last_name')
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name ?: __('quiz.individual_circle_for', ['student' => $c->student?->name ?? '-']),
                ])
                ->all();

        } elseif ($fullClassName === AcademicIndividualLesson::class && $user->isAcademicTeacher()) {
            $profileId = $user->academicTeacherProfile?->id;
            if ($profileId) {
                $options = AcademicIndividualLesson::where('academic_teacher_id', $profileId)
                    ->with('student:id,first_name,last_name')
                    ->get()
                    ->map(fn ($l) => [
                        'id' => $l->id,
                        'name' => $l->name ?: ($l->lesson_code . ' - ' . ($l->student?->name ?? '-')),
                    ])
                    ->all();
            }

        } elseif ($fullClassName === InteractiveCourse::class && $user->isAcademicTeacher()) {
            $profileId = $user->academicTeacherProfile?->id;
            if ($profileId) {
                $options = InteractiveCourse::where('assigned_teacher_id', $profileId)
                    ->select('id', 'title')
                    ->get()
                    ->map(fn ($c) => ['id' => $c->id, 'name' => $c->title])
                    ->all();
            }
        }

        return response()->json([
            'success' => true,
            'options' => $options,
        ]);
    }

    // ========================================
    // Private helpers
    // ========================================

    /**
     * Collect all assignable entity IDs the current teacher owns, grouped by class.
     *
     * @return array<class-string, array<int|string>>
     */
    private function getTeacherAssignableIds(): array
    {
        $user = Auth::user();
        $ids = [];

        if ($user->isQuranTeacher()) {
            $ids[QuranCircle::class] = QuranCircle::where('quran_teacher_id', $user->id)
                ->pluck('id')
                ->all();
            $ids[QuranIndividualCircle::class] = QuranIndividualCircle::where('quran_teacher_id', $user->id)
                ->pluck('id')
                ->all();
        }

        if ($user->isAcademicTeacher()) {
            $profileId = $user->academicTeacherProfile?->id;
            if ($profileId) {
                $ids[AcademicIndividualLesson::class] = AcademicIndividualLesson::where('academic_teacher_id', $profileId)
                    ->pluck('id')
                    ->all();
                $ids[InteractiveCourse::class] = InteractiveCourse::where('assigned_teacher_id', $profileId)
                    ->pluck('id')
                    ->all();
            }
        }

        return $ids;
    }

    /**
     * Build a structured array of assignable options for the teacher, suitable for form selects.
     *
     * @return array<string, array{label: string, options: array}>
     */
    private function getTeacherAssignableOptions(): array
    {
        $user = Auth::user();
        $groups = [];

        if ($user->isQuranTeacher()) {
            $circles = QuranCircle::where('quran_teacher_id', $user->id)
                ->select('id', 'name')
                ->get();

            if ($circles->isNotEmpty()) {
                $groups['quran_circle'] = [
                    'label' => __('quiz.assignable.quran_circle'),
                    'options' => $circles->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all(),
                ];
            }

            $individualCircles = QuranIndividualCircle::where('quran_teacher_id', $user->id)
                ->with('student:id,first_name,last_name')
                ->get();

            if ($individualCircles->isNotEmpty()) {
                $groups['quran_individual_circle'] = [
                    'label' => __('quiz.assignable.individual_circle'),
                    'options' => $individualCircles->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name ?: __('quiz.individual_circle_for', ['student' => $c->student?->name ?? '-']),
                    ])->all(),
                ];
            }
        }

        if ($user->isAcademicTeacher()) {
            $profileId = $user->academicTeacherProfile?->id;

            if ($profileId) {
                $lessons = AcademicIndividualLesson::where('academic_teacher_id', $profileId)
                    ->with('student:id,first_name,last_name')
                    ->get();

                if ($lessons->isNotEmpty()) {
                    $groups['academic_lesson'] = [
                        'label' => __('quiz.assignable.academic_lesson'),
                        'options' => $lessons->map(fn ($l) => [
                            'id' => $l->id,
                            'name' => $l->name ?: ($l->lesson_code . ' - ' . ($l->student?->name ?? '-')),
                        ])->all(),
                    ];
                }

                $courses = InteractiveCourse::where('assigned_teacher_id', $profileId)
                    ->select('id', 'title')
                    ->get();

                if ($courses->isNotEmpty()) {
                    $groups['interactive_course'] = [
                        'label' => __('quiz.assignable.interactive_course'),
                        'options' => $courses->map(fn ($c) => ['id' => $c->id, 'name' => $c->title])->all(),
                    ];
                }
            }
        }

        return $groups;
    }
}
