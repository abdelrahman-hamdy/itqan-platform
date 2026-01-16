<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuizController extends Controller
{
    use ApiResponses;

    /**
     * Get all quizzes assigned to the student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return $this->success([
                'quizzes' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                    'has_more' => false,
                ],
                'stats' => [
                    'pending' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                ],
            ], __('Quizzes retrieved successfully'));
        }

        $studentId = $studentProfile->id;
        $status = $request->get('status'); // pending, in_progress, completed
        $perPage = min(
            (int) $request->get('per_page', config('api.pagination.default_per_page', 15)),
            config('api.pagination.max_per_page', 50)
        );

        // Get quiz assignments for entities the student belongs to
        $assignableIds = $this->getStudentAssignableIds($user, $studentProfile);

        if (empty($assignableIds)) {
            return $this->success([
                'quizzes' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'has_more' => false,
                ],
                'stats' => [
                    'pending' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                ],
            ], __('Quizzes retrieved successfully'));
        }

        $query = QuizAssignment::where(function ($q) use ($assignableIds) {
            foreach ($assignableIds as $type => $ids) {
                $q->orWhere(function ($subQ) use ($type, $ids) {
                    $subQ->where('assignable_type', $type)
                        ->whereIn('assignable_id', $ids);
                });
            }
        })
            ->whereHas('quiz', function ($q) {
                $q->where('is_published', true);
            })
            ->with([
                'quiz' => function ($q) {
                    $q->select('id', 'title', 'description', 'duration_minutes', 'passing_score', 'questions_count', 'created_at');
                },
                'attempts' => function ($q) use ($studentId) {
                    $q->where('student_id', $studentId)->latest();
                },
            ]);

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $quizzes = collect($paginator->items())->map(function ($assignment) use ($studentId) {
            $quiz = $assignment->quiz;
            if (! $quiz) {
                return null;
            }

            $latestAttempt = $assignment->attempts->first();
            $completedAttempts = $assignment->attempts->filter(fn ($a) => $a->submitted_at !== null);
            $attemptsCount = $assignment->attempts->count();
            $completedCount = $completedAttempts->count();

            // Calculate best score from completed attempts
            $bestScore = $completedAttempts->max('score');
            $hasPassed = $completedAttempts->where('passed', true)->isNotEmpty();

            // Check for in-progress attempt
            $inProgressAttempt = $assignment->attempts->filter(fn ($a) => $a->submitted_at === null)->first();

            // Determine status
            $quizStatus = 'pending';
            if ($inProgressAttempt) {
                $quizStatus = 'in_progress';
            } elseif ($completedCount > 0) {
                $quizStatus = 'completed';
            }

            // Get assignable name (circle, course, etc.)
            $assignableName = $this->getAssignableName($assignment->assignable);

            return [
                'id' => $quiz->id,
                'assignment_id' => $assignment->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'time_limit_minutes' => $quiz->duration_minutes,
                'passing_score' => $quiz->passing_score,
                'total_questions' => $quiz->questions_count ?? 0,
                'status' => $quizStatus,
                'due_date' => $assignment->available_until?->toISOString(),
                'is_overdue' => $assignment->available_until && $assignment->available_until->isPast() && $quizStatus === 'pending',
                'attempts_allowed' => $assignment->max_attempts ?? 1,
                'attempts_used' => $completedCount,
                'attempts_remaining' => max(0, ($assignment->max_attempts ?? 1) - $completedCount),
                'best_score' => $bestScore,
                'has_passed' => $hasPassed,
                'can_attempt' => $assignment->isAvailable() && $completedCount < ($assignment->max_attempts ?? 1),
                'assignable_name' => $assignableName,
                'assignable_type' => class_basename($assignment->assignable_type ?? ''),
                'in_progress_attempt' => $inProgressAttempt ? [
                    'id' => $inProgressAttempt->id,
                    'started_at' => $inProgressAttempt->started_at?->toISOString(),
                ] : null,
                'latest_attempt' => $latestAttempt ? [
                    'id' => $latestAttempt->id,
                    'score' => $latestAttempt->score,
                    'passed' => $latestAttempt->passed,
                    'completed_at' => $latestAttempt->submitted_at?->toISOString(),
                ] : null,
                'assigned_at' => $assignment->created_at->toISOString(),
            ];
        })->filter()->values();

        // Apply status filter after mapping
        if ($status) {
            $quizzes = $quizzes->where('status', $status)->values();
        }

        return $this->success([
            'quizzes' => $quizzes->toArray(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'stats' => [
                'pending' => $quizzes->where('status', 'pending')->count(),
                'in_progress' => $quizzes->where('status', 'in_progress')->count(),
                'completed' => $quizzes->where('status', 'completed')->count(),
            ],
        ], __('Quizzes retrieved successfully'));
    }

    /**
     * Get a specific quiz.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return $this->notFound(__('Quiz not found or not assigned to you.'));
        }

        $studentId = $studentProfile->id;
        $assignableIds = $this->getStudentAssignableIds($user, $studentProfile);

        $assignment = QuizAssignment::where('quiz_id', $id)
            ->where(function ($q) use ($assignableIds) {
                foreach ($assignableIds as $type => $ids) {
                    $q->orWhere(function ($subQ) use ($type, $ids) {
                        $subQ->where('assignable_type', $type)
                            ->whereIn('assignable_id', $ids);
                    });
                }
            })
            ->whereHas('quiz', function ($q) {
                $q->where('is_published', true);
            })
            ->with([
                'quiz',
                'attempts' => function ($q) use ($studentId) {
                    $q->where('student_id', $studentId)->orderBy('created_at', 'desc');
                },
            ])
            ->first();

        if (! $assignment) {
            return $this->notFound(__('Quiz not found or not assigned to you.'));
        }

        $quiz = $assignment->quiz;
        $canStart = $assignment->isAvailable() && $assignment->canStudentAttempt($studentId);

        return $this->success([
            'quiz' => [
                'id' => $quiz->id,
                'assignment_id' => $assignment->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'instructions' => $quiz->instructions,
                'time_limit_minutes' => $quiz->duration_minutes,
                'passing_score' => $quiz->passing_score,
                'total_questions' => $quiz->questions_count ?? $quiz->questions()->count(),
                'due_date' => $assignment->available_until?->toISOString(),
                'is_overdue' => $assignment->available_until && $assignment->available_until->isPast(),
                'attempts_allowed' => $assignment->max_attempts ?? 1,
                'attempts_used' => $assignment->attempts->count(),
                'can_start' => $canStart,
                'attempts' => $assignment->attempts->map(fn ($a) => [
                    'id' => $a->id,
                    'score' => $a->score,
                    'passed' => $a->passed,
                    'started_at' => $a->started_at?->toISOString(),
                    'completed_at' => $a->submitted_at?->toISOString(),
                ])->toArray(),
            ],
        ], __('Quiz retrieved successfully'));
    }

    /**
     * Start a quiz attempt.
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return $this->notFound(__('Quiz not found or not assigned to you.'));
        }

        $studentId = $studentProfile->id;
        $assignableIds = $this->getStudentAssignableIds($user, $studentProfile);

        $assignment = QuizAssignment::where('quiz_id', $id)
            ->where(function ($q) use ($assignableIds) {
                foreach ($assignableIds as $type => $ids) {
                    $q->orWhere(function ($subQ) use ($type, $ids) {
                        $subQ->where('assignable_type', $type)
                            ->whereIn('assignable_id', $ids);
                    });
                }
            })
            ->whereHas('quiz', function ($q) {
                $q->where('is_published', true);
            })
            ->with(['quiz.questions.options', 'attempts' => function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            }])
            ->first();

        if (! $assignment) {
            return $this->notFound(__('Quiz not found or not assigned to you.'));
        }

        // Check if can start
        if ($assignment->available_until && $assignment->available_until->isPast()) {
            return $this->error(
                __('Quiz due date has passed.'),
                400,
                'QUIZ_OVERDUE'
            );
        }

        if (! $assignment->canStudentAttempt($studentId)) {
            return $this->error(
                __('Maximum attempts reached.'),
                400,
                'MAX_ATTEMPTS_REACHED'
            );
        }

        // Check for in-progress attempt
        $inProgressAttempt = QuizAttempt::where('quiz_assignment_id', $assignment->id)
            ->where('student_id', $studentId)
            ->whereNull('submitted_at')
            ->first();

        if ($inProgressAttempt) {
            // Return existing attempt with questions
            return $this->success([
                'attempt' => $this->formatAttemptWithQuestions($inProgressAttempt, $assignment->quiz),
            ], __('Continuing existing attempt'));
        }

        // Create new attempt
        $attempt = DB::transaction(function () use ($studentId, $assignment) {
            return QuizAttempt::create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentId,
                'started_at' => now(),
                'answers' => [],
            ]);
        });

        return $this->success([
            'attempt' => $this->formatAttemptWithQuestions($attempt, $assignment->quiz),
        ], __('Quiz started'));
    }

    /**
     * Submit quiz answers.
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.selected_option_ids' => ['required', 'array'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return $this->error(__('No active quiz attempt found.'), 400, 'NO_ACTIVE_ATTEMPT');
        }

        $studentId = $studentProfile->id;

        // Find in-progress attempt
        $attempt = QuizAttempt::whereHas('assignment', function ($q) use ($id) {
            $q->where('quiz_id', $id);
        })
            ->where('student_id', $studentId)
            ->whereNull('submitted_at')
            ->with(['assignment.quiz.questions.options'])
            ->first();

        if (! $attempt) {
            return $this->error(
                __('No active quiz attempt found.'),
                400,
                'NO_ACTIVE_ATTEMPT'
            );
        }

        $quiz = $attempt->assignment->quiz;

        // Check time limit
        if ($quiz->duration_minutes) {
            $elapsed = now()->diffInMinutes($attempt->started_at);
            if ($elapsed > $quiz->duration_minutes + 1) { // 1 minute grace
                return $this->error(
                    __('Time limit exceeded.'),
                    400,
                    'TIME_LIMIT_EXCEEDED'
                );
            }
        }

        // Calculate score
        $answers = collect($request->answers);
        $correctCount = 0;
        $totalQuestions = $quiz->questions->count();

        $processedAnswers = [];
        foreach ($quiz->questions as $question) {
            $answer = $answers->firstWhere('question_id', $question->id);
            $selectedOptionIds = $answer['selected_option_ids'] ?? [];

            $correctOptionIds = $question->options
                ->where('is_correct', true)
                ->pluck('id')
                ->toArray();

            $isCorrect = empty(array_diff($selectedOptionIds, $correctOptionIds))
                && empty(array_diff($correctOptionIds, $selectedOptionIds));

            if ($isCorrect) {
                $correctCount++;
            }

            $processedAnswers[] = [
                'question_id' => $question->id,
                'selected_option_ids' => $selectedOptionIds,
                'is_correct' => $isCorrect,
            ];
        }

        $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0;
        $passed = $score >= ($quiz->passing_score ?? 60);

        // Update attempt
        $attempt = DB::transaction(function () use ($attempt, $processedAnswers, $score, $passed) {
            $attempt->update([
                'answers' => $processedAnswers,
                'score' => $score,
                'passed' => $passed,
                'submitted_at' => now(),
            ]);

            return $attempt->fresh();
        });

        return $this->success([
            'result' => [
                'attempt_id' => $attempt->id,
                'score' => $attempt->score,
                'passed' => $attempt->passed,
                'correct_answers' => $correctCount,
                'total_questions' => $totalQuestions,
                'passing_score' => $quiz->passing_score,
            ],
        ], $passed
            ? __('Congratulations! You passed the quiz.')
            : __('Quiz completed. Unfortunately, you did not pass.'));
    }

    /**
     * Get quiz result.
     */
    public function result(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return $this->notFound(__('Quiz result not found.'));
        }

        $studentId = $studentProfile->id;
        $attemptId = $request->get('attempt_id');

        // Get all completed attempts for this quiz
        $allAttempts = QuizAttempt::whereHas('assignment', function ($q) use ($id) {
            $q->where('quiz_id', $id);
        })
            ->where('student_id', $studentId)
            ->whereNotNull('submitted_at')
            ->with(['assignment.quiz.questions.options'])
            ->orderByDesc('submitted_at')
            ->get();

        if ($allAttempts->isEmpty()) {
            return $this->notFound(__('Quiz result not found.'));
        }

        // Get specific attempt or latest
        $attempt = $attemptId
            ? $allAttempts->firstWhere('id', $attemptId)
            : $allAttempts->first();

        if (! $attempt) {
            return $this->notFound(__('Quiz result not found.'));
        }

        // Find best attempt by score
        $bestAttempt = $allAttempts->sortByDesc('score')->first();

        $quiz = $attempt->assignment->quiz;
        $assignment = $attempt->assignment;
        $answers = collect($attempt->answers ?? []);

        $questions = $quiz->questions->map(function ($question) use ($answers) {
            $answer = $answers->firstWhere('question_id', $question->id);

            return [
                'id' => $question->id,
                'question' => $question->question,
                'type' => $question->type,
                'options' => $question->options->map(fn ($opt) => [
                    'id' => $opt->id,
                    'text' => $opt->text,
                    'is_correct' => $opt->is_correct,
                    'was_selected' => in_array($opt->id, $answer['selected_option_ids'] ?? []),
                ])->toArray(),
                'was_correct' => $answer['is_correct'] ?? false,
                'explanation' => $question->explanation,
            ];
        })->toArray();

        // Format all attempts summary
        $attemptsSummary = $allAttempts->map(fn ($a) => [
            'id' => $a->id,
            'score' => $a->score,
            'passed' => $a->passed,
            'is_best' => $a->id === $bestAttempt->id,
            'started_at' => $a->started_at?->toISOString(),
            'completed_at' => $a->submitted_at?->toISOString(),
        ])->values()->toArray();

        // Calculate remaining attempts
        $completedCount = $allAttempts->count();
        $maxAttempts = $assignment->max_attempts ?? 1;
        $canRetry = $assignment->isAvailable() && $completedCount < $maxAttempts;

        return $this->success([
            'result' => [
                'attempt_id' => $attempt->id,
                'quiz_id' => $quiz->id,
                'quiz_title' => $quiz->title,
                'score' => $attempt->score,
                'passed' => $attempt->passed,
                'passing_score' => $quiz->passing_score,
                'correct_answers' => collect($attempt->answers)->where('is_correct', true)->count(),
                'total_questions' => count($attempt->answers ?? []),
                'started_at' => $attempt->started_at?->toISOString(),
                'completed_at' => $attempt->submitted_at?->toISOString(),
                'questions' => $questions,
            ],
            'best_attempt' => [
                'id' => $bestAttempt->id,
                'score' => $bestAttempt->score,
                'passed' => $bestAttempt->passed,
                'completed_at' => $bestAttempt->submitted_at?->toISOString(),
            ],
            'all_attempts' => $attemptsSummary,
            'attempts_info' => [
                'total_attempts' => $completedCount,
                'max_attempts' => $maxAttempts,
                'remaining_attempts' => max(0, $maxAttempts - $completedCount),
                'can_retry' => $canRetry,
            ],
        ], __('Quiz result retrieved successfully'));
    }

    /**
     * Get quiz attempt history for the student.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return $this->success([
                'history' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                    'has_more' => false,
                ],
            ], __('Quiz history retrieved successfully'));
        }

        $studentId = $studentProfile->id;
        $perPage = min(
            (int) $request->get('per_page', config('api.pagination.default_per_page', 15)),
            config('api.pagination.max_per_page', 50)
        );

        // Get all completed attempts
        $paginator = QuizAttempt::with(['assignment.quiz', 'assignment.assignable'])
            ->where('student_id', $studentId)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->paginate($perPage);

        $history = collect($paginator->items())->map(function ($attempt) {
            $quiz = $attempt->assignment?->quiz;
            if (! $quiz) {
                return null;
            }

            return [
                'attempt_id' => $attempt->id,
                'quiz_id' => $quiz->id,
                'quiz_title' => $quiz->title,
                'assignable_name' => $this->getAssignableName($attempt->assignment?->assignable),
                'assignable_type' => class_basename($attempt->assignment?->assignable_type ?? ''),
                'score' => $attempt->score,
                'passed' => $attempt->passed,
                'passing_score' => $quiz->passing_score,
                'started_at' => $attempt->started_at?->toISOString(),
                'completed_at' => $attempt->submitted_at?->toISOString(),
            ];
        })->filter()->values()->toArray();

        return $this->success([
            'history' => $history,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ], __('Quiz history retrieved successfully'));
    }

    /**
     * Get assignable IDs for the student (circles, courses, lessons they belong to).
     *
     * @param  \App\Models\User  $user  The authenticated user (for subscriptions)
     * @param  \App\Models\StudentProfile  $studentProfile  The student profile (for enrollments)
     */
    protected function getStudentAssignableIds($user, $studentProfile): array
    {
        $assignableIds = [];

        // Get education units from Quran subscriptions (circles, individual circles)
        // quran_subscriptions.student_id references User.id
        $quranSubs = $user->quranSubscriptions()
            ->whereNotNull('education_unit_type')
            ->whereNotNull('education_unit_id')
            ->select('education_unit_type', 'education_unit_id')
            ->get();

        foreach ($quranSubs as $sub) {
            $type = $sub->education_unit_type;
            $id = $sub->education_unit_id;
            if (! isset($assignableIds[$type])) {
                $assignableIds[$type] = [];
            }
            if (! in_array($id, $assignableIds[$type])) {
                $assignableIds[$type][] = $id;
            }
        }

        // Get Quran individual circles for the student
        // quran_individual_circles.student_id references User.id
        $quranIndividualCircleIds = \App\Models\QuranIndividualCircle::where('student_id', $user->id)
            ->pluck('id')
            ->toArray();
        if (! empty($quranIndividualCircleIds)) {
            $assignableIds['App\\Models\\QuranIndividualCircle'] = $quranIndividualCircleIds;
        }

        // Get academic individual lessons for the student
        // academic_individual_lessons.student_id references User.id
        $academicLessonIds = \App\Models\AcademicIndividualLesson::where('student_id', $user->id)
            ->pluck('id')
            ->toArray();
        if (! empty($academicLessonIds)) {
            $assignableIds['App\\Models\\AcademicIndividualLesson'] = $academicLessonIds;
        }

        // Get interactive courses the student is enrolled in
        // interactive_course_enrollments.student_id references StudentProfile.id
        $interactiveCourseIds = \App\Models\InteractiveCourseEnrollment::where('student_id', $studentProfile->id)
            ->pluck('course_id')
            ->toArray();
        if (! empty($interactiveCourseIds)) {
            $assignableIds['App\\Models\\InteractiveCourse'] = $interactiveCourseIds;
        }

        // Get recorded courses from course subscriptions
        // course_subscriptions.student_id references User.id
        $courseSubs = $user->courseSubscriptions()
            ->whereNotNull('recorded_course_id')
            ->pluck('recorded_course_id')
            ->toArray();
        if (! empty($courseSubs)) {
            $assignableIds['App\\Models\\RecordedCourse'] = $courseSubs;
        }

        return $assignableIds;
    }

    /**
     * Format attempt with questions for quiz taking.
     */
    protected function formatAttemptWithQuestions(QuizAttempt $attempt, Quiz $quiz): array
    {
        $startedAt = $attempt->started_at;
        $timeLimit = $quiz->duration_minutes;
        $timeRemainingMinutes = null;
        $timeRemainingSeconds = null;

        if ($timeLimit && $startedAt) {
            $elapsed = now()->diffInMinutes($startedAt);
            $timeRemainingMinutes = max(0, $timeLimit - $elapsed);
            // Also provide remaining time in seconds for more precise timing
            $elapsedSeconds = now()->diffInSeconds($startedAt);
            $timeRemainingSeconds = max(0, ($timeLimit * 60) - $elapsedSeconds);
        }

        // Get questions - randomize if enabled on the quiz
        $questions = $quiz->randomize_questions
            ? $quiz->questions->shuffle()
            : $quiz->questions;

        return [
            'id' => $attempt->id,
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'started_at' => $startedAt?->toISOString(),
            'time_limit_minutes' => $timeLimit,
            'time_remaining_minutes' => $timeRemainingMinutes,
            'time_remaining_seconds' => $timeRemainingSeconds,
            'total_questions' => $questions->count(),
            'randomize_questions' => $quiz->randomize_questions ?? false,
            'questions' => $questions->map(fn ($q) => [
                'id' => $q->id,
                'question' => $q->question,
                'type' => $q->type,
                'options' => $q->options->map(fn ($opt) => [
                    'id' => $opt->id,
                    'text' => $opt->text,
                ])->toArray(),
            ])->toArray(),
            'saved_answers' => $attempt->answers ?? [],
        ];
    }

    /**
     * Get a human-readable name for the assignable entity.
     */
    protected function getAssignableName($assignable): string
    {
        if (! $assignable) {
            return 'غير محدد';
        }

        $type = get_class($assignable);

        return match ($type) {
            \App\Models\QuranCircle::class => $assignable->name ?? 'حلقة قرآن',
            \App\Models\QuranIndividualCircle::class => $assignable->name ?? 'حلقة فردية',
            \App\Models\AcademicIndividualLesson::class => $assignable->subscription?->teacher?->user?->name ?? 'درس أكاديمي',
            \App\Models\AcademicSubscription::class => ($assignable->subject_name ?? 'درس خاص').' - '.($assignable->teacher?->user?->name ?? ''),
            \App\Models\InteractiveCourse::class => $assignable->name ?? 'دورة تفاعلية',
            \App\Models\RecordedCourse::class => $assignable->name ?? 'دورة مسجلة',
            default => 'غير محدد',
        };
    }
}
