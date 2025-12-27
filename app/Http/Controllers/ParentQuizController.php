<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ChildSelectionMiddleware;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\SessionStatus;

/**
 * Parent Quiz Controller
 *
 * Handles viewing children's quiz assignments and results.
 * Supports filtering by child using session-based selection.
 */
class ParentQuizController extends Controller
{
    protected QuizService $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;

        // Enforce read-only access
        $this->middleware(function ($request, $next) {
            if (!in_array($request->method(), ['GET', 'HEAD'])) {
                abort(403, 'أولياء الأمور لديهم صلاحيات مشاهدة فقط');
            }
            return $next($request);
        });
    }

    /**
     * List quizzes for all children
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        // Get child student profile IDs from middleware (session-based selection)
        $childUserIds = ChildSelectionMiddleware::getChildIds();

        // Get children student profiles
        $children = $parent->students()->with('user')->get();

        // Aggregate quizzes and history for all children
        $allQuizzes = collect();
        $allHistory = collect();

        foreach ($childUserIds as $childUserId) {
            // Get child's student profile
            $childUser = \App\Models\User::find($childUserId);
            $studentProfile = $childUser?->studentProfile;

            if ($studentProfile) {
                // Get quizzes for this child
                $quizzes = $this->quizService->getStudentQuizzes($studentProfile->id);

                // Add child info to each quiz
                $quizzes = $quizzes->map(function ($quizData) use ($childUser) {
                    $quizData['child_name'] = $childUser->name ?? 'غير معروف';
                    $quizData['child_id'] = $childUser->id;
                    return $quizData;
                });

                $allQuizzes = $allQuizzes->merge($quizzes);

                // Get quiz history for this child
                $history = $this->quizService->getStudentQuizHistory($studentProfile->id);

                // Add child info to each history item
                $history = $history->map(function ($historyItem) use ($childUser) {
                    $historyItem->child_name = $childUser->name ?? 'غير معروف';
                    $historyItem->child_id = $childUser->id;
                    return $historyItem;
                });

                $allHistory = $allHistory->merge($history);
            }
        }

        // Sort quizzes by due date
        $allQuizzes = $allQuizzes->sortBy('due_date')->values();

        // Sort history by completion date (most recent first)
        $allHistory = $allHistory->sortByDesc('completed_at')->values();

        return view('parent.quizzes.index', [
            'parent' => $parent,
            'user' => $user,
            'children' => $children,
            'quizzes' => $allQuizzes,
            'history' => $allHistory,
        ]);
    }

    /**
     * View quiz result for a specific child
     *
     * @param Request $request
     * @param int $quizId
     * @return \Illuminate\View\View
     */
    public function result(Request $request, int $quizId)
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        // Get all children user IDs
        $children = $parent->students()->with('user')->get();
        $childUserIds = $children->pluck('user_id')->toArray();

        // Get the quiz assignment
        $assignment = \App\Models\QuizAssignment::with(['quiz', 'attempts'])->findOrFail($quizId);

        // Get attempts by parent's children only
        $childAttempts = $assignment->attempts->filter(function ($attempt) use ($childUserIds) {
            $studentProfile = \App\Models\StudentProfile::find($attempt->student_id);
            return $studentProfile && in_array($studentProfile->user_id, $childUserIds);
        });

        if ($childAttempts->isEmpty()) {
            abort(403, 'لا يمكنك الوصول إلى نتائج هذا الاختبار');
        }

        return view('parent.quizzes.result', [
            'parent' => $parent,
            'user' => $user,
            'assignment' => $assignment,
            'attempts' => $childAttempts,
        ]);
    }
}
