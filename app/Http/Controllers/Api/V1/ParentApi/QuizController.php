<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\ParentStudentRelationship;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\SessionStatus;

class QuizController extends Controller
{
    use ApiResponses;

    /**
     * Get all quizzes and results for linked children.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Get all linked children
        $children = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user')
            ->get();

        $quizResults = [];

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $student->user?->id ?? $student->id;

            // Filter by specific child if requested
            if ($request->filled('child_id') && $student->id != $request->child_id) {
                continue;
            }

            // Get quiz attempts - use student_id (StudentProfile.id)
            $attempts = QuizAttempt::where('student_id', $student->id)
                ->with(['quiz.course', 'quiz.session'])
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($attempts as $attempt) {
                $quizResults[] = [
                    'id' => $attempt->id,
                    'child_id' => $student->id,
                    'child_name' => $student->full_name,
                    'quiz' => [
                        'id' => $attempt->quiz?->id,
                        'title' => $attempt->quiz?->title,
                        'type' => $attempt->quiz?->type ?? 'general',
                        'total_questions' => $attempt->quiz?->questions_count ?? $attempt->total_questions ?? 0,
                        'passing_score' => $attempt->quiz?->passing_score ?? 60,
                    ],
                    'score' => $attempt->score,
                    'total_questions' => $attempt->total_questions,
                    'correct_answers' => $attempt->correct_answers,
                    'percentage' => $attempt->total_questions > 0
                        ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1)
                        : 0,
                    'passed' => $attempt->passed ?? ($attempt->score >= ($attempt->quiz?->passing_score ?? 60)),
                    'time_taken_minutes' => $attempt->time_taken_minutes,
                    'started_at' => $attempt->started_at?->toISOString(),
                    'completed_at' => $attempt->completed_at?->toISOString(),
                    'created_at' => $attempt->created_at->toISOString(),
                ];
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'passed') {
                $quizResults = array_filter($quizResults, fn($r) => $r['passed']);
            } elseif ($request->status === 'failed') {
                $quizResults = array_filter($quizResults, fn($r) => !$r['passed']);
            }
        }

        // Sort by date
        usort($quizResults, fn($a, $b) =>
            strtotime($b['created_at']) <=> strtotime($a['created_at'])
        );

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $total = count($quizResults);
        $quizResults = array_slice($quizResults, ($page - 1) * $perPage, $perPage);

        return $this->success([
            'quiz_results' => array_values($quizResults),
            'pagination' => PaginationHelper::fromArray($total, $page, $perPage),
        ], __('Quiz results retrieved successfully'));
    }

    /**
     * Get quizzes for a specific child.
     *
     * @param Request $request
     * @param int $childId
     * @return JsonResponse
     */
    public function childQuizzes(Request $request, int $childId): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Verify child is linked
        $relationship = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->where('student_id', $childId)
            ->with('student.user')
            ->first();

        if (!$relationship) {
            return $this->notFound(__('Child not found.'));
        }

        $student = $relationship->student;

        // Get quiz attempts with details - use student_id (StudentProfile.id)
        $attempts = QuizAttempt::where('student_id', $student->id)
            ->with(['quiz.course', 'quiz.session'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'child' => [
                'id' => $student->id,
                'name' => $student->full_name,
            ],
            'quiz_results' => collect($attempts->items())->map(fn($attempt) => [
                'id' => $attempt->id,
                'quiz' => [
                    'id' => $attempt->quiz?->id,
                    'title' => $attempt->quiz?->title,
                    'type' => $attempt->quiz?->type ?? 'general',
                    'course' => $attempt->quiz?->course?->title,
                    'total_questions' => $attempt->quiz?->questions_count ?? $attempt->total_questions ?? 0,
                    'passing_score' => $attempt->quiz?->passing_score ?? 60,
                ],
                'score' => $attempt->score,
                'total_questions' => $attempt->total_questions,
                'correct_answers' => $attempt->correct_answers,
                'percentage' => $attempt->total_questions > 0
                    ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1)
                    : 0,
                'passed' => $attempt->passed ?? ($attempt->score >= ($attempt->quiz?->passing_score ?? 60)),
                'time_taken_minutes' => $attempt->time_taken_minutes,
                'started_at' => $attempt->started_at?->toISOString(),
                'completed_at' => $attempt->completed_at?->toISOString(),
                'created_at' => $attempt->created_at->toISOString(),
            ])->toArray(),
            'stats' => $this->getChildQuizStats($student->id),
            'pagination' => PaginationHelper::fromPaginator($attempts),
        ], __('Child quiz results retrieved successfully'));
    }

    /**
     * Get a specific quiz result.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Get all linked children's student profile IDs
        $childStudentIds = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->pluck('student_id')
            ->toArray();

        $attempt = QuizAttempt::where('id', $id)
            ->whereIn('student_id', $childStudentIds)
            ->with(['quiz.questions'])
            ->first();

        if (!$attempt) {
            return $this->notFound(__('Quiz result not found.'));
        }

        // Get child info
        $childRelation = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->where('student_id', $attempt->student_id)
            ->with('student')
            ->first();

        return $this->success([
            'quiz_result' => [
                'id' => $attempt->id,
                'child' => $childRelation?->student ? [
                    'id' => $childRelation->student->id,
                    'name' => $childRelation->student->full_name,
                ] : null,
                'quiz' => [
                    'id' => $attempt->quiz?->id,
                    'title' => $attempt->quiz?->title,
                    'description' => $attempt->quiz?->description,
                    'type' => $attempt->quiz?->type ?? 'general',
                    'total_questions' => $attempt->quiz?->questions_count ?? count($attempt->quiz?->questions ?? []),
                    'passing_score' => $attempt->quiz?->passing_score ?? 60,
                    'time_limit_minutes' => $attempt->quiz?->time_limit_minutes,
                ],
                'score' => $attempt->score,
                'total_questions' => $attempt->total_questions,
                'correct_answers' => $attempt->correct_answers,
                'wrong_answers' => $attempt->total_questions - $attempt->correct_answers,
                'percentage' => $attempt->total_questions > 0
                    ? round(($attempt->correct_answers / $attempt->total_questions) * 100, 1)
                    : 0,
                'passed' => $attempt->passed ?? ($attempt->score >= ($attempt->quiz?->passing_score ?? 60)),
                'time_taken_minutes' => $attempt->time_taken_minutes,
                'answers' => $attempt->answers ?? [],
                'started_at' => $attempt->started_at?->toISOString(),
                'completed_at' => $attempt->completed_at?->toISOString(),
            ],
        ], __('Quiz result retrieved successfully'));
    }

    /**
     * Get quiz stats for a child.
     */
    protected function getChildQuizStats(int $studentId): array
    {
        $attempts = QuizAttempt::where('student_id', $studentId)->get();

        $totalAttempts = $attempts->count();
        $passedAttempts = $attempts->filter(function ($a) {
            return $a->passed ?? ($a->score >= 60);
        })->count();

        return [
            'total_quizzes_taken' => $totalAttempts,
            'quizzes_passed' => $passedAttempts,
            'quizzes_failed' => $totalAttempts - $passedAttempts,
            'pass_rate' => $totalAttempts > 0
                ? round(($passedAttempts / $totalAttempts) * 100, 1)
                : 0,
            'average_score' => $attempts->count() > 0
                ? round($attempts->avg('score') ?? 0, 1)
                : 0,
        ];
    }
}
