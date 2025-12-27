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
use App\Enums\SessionStatus;

class QuizController extends Controller
{
    use ApiResponses;

    /**
     * Get all quizzes assigned to the student.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->get('status'); // pending, in_progress, completed

        $query = QuizAssignment::where('user_id', $user->id)
            ->whereHas('quiz', function ($q) {
                $q->where('is_published', true);
            })
            ->with([
                'quiz' => function ($q) {
                    $q->select('id', 'title', 'description', 'time_limit_minutes', 'passing_score', 'total_questions', 'created_at');
                },
                'attempts' => function ($q) use ($user) {
                    $q->where('user_id', $user->id)->latest();
                },
            ]);

        if ($status) {
            $query->where('status', $status);
        }

        $assignments = $query->orderBy('created_at', 'desc')->get();

        $quizzes = $assignments->map(function ($assignment) {
            $quiz = $assignment->quiz;
            $latestAttempt = $assignment->attempts->first();

            return [
                'id' => $quiz->id,
                'assignment_id' => $assignment->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'passing_score' => $quiz->passing_score,
                'total_questions' => $quiz->total_questions,
                'status' => $assignment->status,
                'due_date' => $assignment->due_date?->toISOString(),
                'is_overdue' => $assignment->due_date && $assignment->due_date->isPast() && $assignment->status === 'pending',
                'attempts_allowed' => $assignment->attempts_allowed ?? 1,
                'attempts_used' => $assignment->attempts->count(),
                'latest_attempt' => $latestAttempt ? [
                    'id' => $latestAttempt->id,
                    'score' => $latestAttempt->score,
                    'passed' => $latestAttempt->passed,
                    'completed_at' => $latestAttempt->completed_at?->toISOString(),
                ] : null,
                'assigned_at' => $assignment->created_at->toISOString(),
            ];
        })->toArray();

        return $this->success([
            'quizzes' => $quizzes,
            'total' => count($quizzes),
            'stats' => [
                'pending' => collect($quizzes)->where('status', 'pending')->count(),
                'in_progress' => collect($quizzes)->where('status', 'in_progress')->count(),
                'completed' => collect($quizzes)->where('status', SessionStatus::COMPLETED->value)->count(),
            ],
        ], __('Quizzes retrieved successfully'));
    }

    /**
     * Get a specific quiz.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $assignment = QuizAssignment::where('user_id', $user->id)
            ->where('quiz_id', $id)
            ->whereHas('quiz', function ($q) {
                $q->where('is_published', true);
            })
            ->with([
                'quiz',
                'attempts' => function ($q) use ($user) {
                    $q->where('user_id', $user->id)->orderBy('created_at', 'desc');
                },
            ])
            ->first();

        if (!$assignment) {
            return $this->notFound(__('Quiz not found or not assigned to you.'));
        }

        $quiz = $assignment->quiz;
        $canStart = $assignment->status === 'pending'
            && ($assignment->attempts_allowed === null || $assignment->attempts->count() < $assignment->attempts_allowed)
            && (!$assignment->due_date || !$assignment->due_date->isPast());

        return $this->success([
            'quiz' => [
                'id' => $quiz->id,
                'assignment_id' => $assignment->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'instructions' => $quiz->instructions,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'passing_score' => $quiz->passing_score,
                'total_questions' => $quiz->total_questions,
                'status' => $assignment->status,
                'due_date' => $assignment->due_date?->toISOString(),
                'is_overdue' => $assignment->due_date && $assignment->due_date->isPast(),
                'attempts_allowed' => $assignment->attempts_allowed ?? 1,
                'attempts_used' => $assignment->attempts->count(),
                'can_start' => $canStart,
                'attempts' => $assignment->attempts->map(fn($a) => [
                    'id' => $a->id,
                    'score' => $a->score,
                    'passed' => $a->passed,
                    'started_at' => $a->started_at?->toISOString(),
                    'completed_at' => $a->completed_at?->toISOString(),
                    'time_taken_minutes' => $a->time_taken_minutes,
                ])->toArray(),
            ],
        ], __('Quiz retrieved successfully'));
    }

    /**
     * Start a quiz attempt.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $assignment = QuizAssignment::where('user_id', $user->id)
            ->where('quiz_id', $id)
            ->whereHas('quiz', function ($q) {
                $q->where('is_published', true);
            })
            ->with(['quiz.questions.options', 'attempts'])
            ->first();

        if (!$assignment) {
            return $this->notFound(__('Quiz not found or not assigned to you.'));
        }

        // Check if can start
        if ($assignment->due_date && $assignment->due_date->isPast()) {
            return $this->error(
                __('Quiz due date has passed.'),
                400,
                'QUIZ_OVERDUE'
            );
        }

        $attemptsUsed = $assignment->attempts->count();
        if ($assignment->attempts_allowed && $attemptsUsed >= $assignment->attempts_allowed) {
            return $this->error(
                __('Maximum attempts reached.'),
                400,
                'MAX_ATTEMPTS_REACHED'
            );
        }

        // Check for in-progress attempt
        $inProgressAttempt = QuizAttempt::where('quiz_id', $id)
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->first();

        if ($inProgressAttempt) {
            // Return existing attempt with questions
            return $this->success([
                'attempt' => $this->formatAttemptWithQuestions($inProgressAttempt, $assignment->quiz),
            ], __('Continuing existing attempt'));
        }

        // Create new attempt
        $attempt = DB::transaction(function () use ($user, $id, $assignment) {
            $attempt = QuizAttempt::create([
                'quiz_id' => $id,
                'user_id' => $user->id,
                'started_at' => now(),
                'answers' => [],
            ]);

            // Update assignment status
            $assignment->update(['status' => 'in_progress']);

            return $attempt;
        });

        return $this->success([
            'attempt' => $this->formatAttemptWithQuestions($attempt, $assignment->quiz),
        ], __('Quiz started'));
    }

    /**
     * Submit quiz answers.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
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

        // Find in-progress attempt
        $attempt = QuizAttempt::where('quiz_id', $id)
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->with(['quiz.questions.options'])
            ->first();

        if (!$attempt) {
            return $this->error(
                __('No active quiz attempt found.'),
                400,
                'NO_ACTIVE_ATTEMPT'
            );
        }

        $quiz = $attempt->quiz;

        // Check time limit
        if ($quiz->time_limit_minutes) {
            $elapsed = now()->diffInMinutes($attempt->started_at);
            if ($elapsed > $quiz->time_limit_minutes + 1) { // 1 minute grace
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
        $attempt = DB::transaction(function () use ($attempt, $processedAnswers, $score, $passed, $user, $id) {
            $attempt->update([
                'answers' => $processedAnswers,
                'score' => $score,
                'passed' => $passed,
                'completed_at' => now(),
                'time_taken_minutes' => now()->diffInMinutes($attempt->started_at),
            ]);

            // Update assignment status
            QuizAssignment::where('quiz_id', $id)
                ->where('user_id', $user->id)
                ->update(['status' => SessionStatus::COMPLETED]);

            return $attempt->fresh();
        });

        return $this->success([
            'result' => [
                'attempt_id' => $attempt->id,
                'score' => $attempt->score,
                'passed' => $attempt->passed,
                'correct_answers' => $correctCount,
                'total_questions' => $totalQuestions,
                'time_taken_minutes' => $attempt->time_taken_minutes,
                'passing_score' => $quiz->passing_score,
            ],
        ], $passed
            ? __('Congratulations! You passed the quiz.')
            : __('Quiz completed. Unfortunately, you did not pass.'));
    }

    /**
     * Get quiz result.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function result(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $attemptId = $request->get('attempt_id');

        $query = QuizAttempt::where('quiz_id', $id)
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->with(['quiz.questions.options']);

        if ($attemptId) {
            $query->where('id', $attemptId);
        } else {
            $query->latest('completed_at');
        }

        $attempt = $query->first();

        if (!$attempt) {
            return $this->notFound(__('Quiz result not found.'));
        }

        $quiz = $attempt->quiz;
        $answers = collect($attempt->answers ?? []);

        $questions = $quiz->questions->map(function ($question) use ($answers) {
            $answer = $answers->firstWhere('question_id', $question->id);

            return [
                'id' => $question->id,
                'question' => $question->question,
                'type' => $question->type,
                'options' => $question->options->map(fn($opt) => [
                    'id' => $opt->id,
                    'text' => $opt->text,
                    'is_correct' => $opt->is_correct,
                    'was_selected' => in_array($opt->id, $answer['selected_option_ids'] ?? []),
                ])->toArray(),
                'was_correct' => $answer['is_correct'] ?? false,
                'explanation' => $question->explanation,
            ];
        })->toArray();

        return $this->success([
            'result' => [
                'attempt_id' => $attempt->id,
                'quiz_title' => $quiz->title,
                'score' => $attempt->score,
                'passed' => $attempt->passed,
                'passing_score' => $quiz->passing_score,
                'correct_answers' => collect($attempt->answers)->where('is_correct', true)->count(),
                'total_questions' => count($attempt->answers ?? []),
                'time_taken_minutes' => $attempt->time_taken_minutes,
                'started_at' => $attempt->started_at?->toISOString(),
                'completed_at' => $attempt->completed_at?->toISOString(),
                'questions' => $questions,
            ],
        ], __('Quiz result retrieved successfully'));
    }

    /**
     * Format attempt with questions for quiz taking.
     */
    protected function formatAttemptWithQuestions(QuizAttempt $attempt, Quiz $quiz): array
    {
        $startedAt = $attempt->started_at;
        $timeLimit = $quiz->time_limit_minutes;
        $timeRemaining = null;

        if ($timeLimit && $startedAt) {
            $elapsed = now()->diffInMinutes($startedAt);
            $timeRemaining = max(0, $timeLimit - $elapsed);
        }

        return [
            'id' => $attempt->id,
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'started_at' => $startedAt?->toISOString(),
            'time_limit_minutes' => $timeLimit,
            'time_remaining_minutes' => $timeRemaining,
            'total_questions' => $quiz->questions->count(),
            'questions' => $quiz->questions->map(fn($q) => [
                'id' => $q->id,
                'question' => $q->question,
                'type' => $q->type,
                'options' => $q->options->map(fn($opt) => [
                    'id' => $opt->id,
                    'text' => $opt->text,
                ])->toArray(),
            ])->toArray(),
            'saved_answers' => $attempt->answers ?? [],
        ];
    }
}
