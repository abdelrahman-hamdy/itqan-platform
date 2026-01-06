<?php

namespace App\Http\Controllers;

use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function __construct(
        protected QuizService $quizService
    ) {}

    /**
     * Get the current subdomain from request
     */
    private function getSubdomain(): string
    {
        return request()->route('subdomain') ?? Auth::user()->academy->subdomain ?? 'itqan-academy';
    }

    /**
     * List all quizzes for the student
     */
    public function index(): View
    {
        $this->authorize('viewAny', QuizAssignment::class);

        $student = Auth::user()->studentProfile;

        // Get all quiz assignments for the student
        $quizzes = $this->quizService->getStudentQuizzes($student->id);

        // Get quiz attempt history
        $history = $this->quizService->getStudentQuizHistory($student->id);

        return view('student.quiz.index', [
            'quizzes' => $quizzes,
            'history' => $history,
        ]);
    }

    /**
     * Start a quiz attempt
     */
    public function start(string $quiz_id): RedirectResponse
    {
        \Log::info('QuizController::start() called', [
            'quiz_id' => $quiz_id,
            'user_id' => Auth::id(),
            'route_params' => request()->route()->parameters(),
        ]);

        $assignment = QuizAssignment::findOrFail($quiz_id);

        // Authorize start access
        $this->authorize('start', $assignment);

        $student = Auth::user()->studentProfile;

        if (! $assignment->isAvailable()) {
            return back()->with('error', 'هذا الاختبار غير متاح حالياً');
        }

        if (! $assignment->canStudentAttempt($student->id)) {
            return back()->with('error', 'لقد استنفدت جميع محاولاتك المتاحة');
        }

        $attempt = $this->quizService->startAttempt($assignment, $student->id);

        return redirect()->route('student.quiz.take', [
            'subdomain' => $this->getSubdomain(),
            'attempt_id' => $attempt->id,
        ]);
    }

    /**
     * Take/continue a quiz
     */
    public function take(string $attempt_id): View|RedirectResponse
    {
        $attempt = QuizAttempt::findOrFail($attempt_id);

        // Authorize take access
        $this->authorize('take', $attempt);

        $student = Auth::user()->studentProfile;

        if ($attempt->isCompleted()) {
            return redirect()->route('student.quiz.result', [
                'subdomain' => $this->getSubdomain(),
                'quiz_id' => $attempt->quiz_assignment_id,
            ]);
        }

        $quiz = $attempt->assignment->quiz->load('questions');

        // Only randomize if the quiz has randomization enabled
        $questions = $quiz->randomize_questions
            ? $quiz->questions->shuffle()
            : $quiz->questions;

        return view('student.quiz.take', [
            'attempt' => $attempt,
            'quiz' => $quiz,
            'questions' => $questions,
            'remainingTime' => $attempt->getRemainingTimeInSeconds(),
        ]);
    }

    /**
     * Submit quiz answers
     */
    public function submit(Request $request, string $attempt_id): RedirectResponse
    {
        $attempt = QuizAttempt::findOrFail($attempt_id);

        // Authorize submit access
        $this->authorize('submit', $attempt);

        $student = Auth::user()->studentProfile;

        if ($attempt->isCompleted()) {
            return redirect()->route('student.quiz.result', [
                'subdomain' => $this->getSubdomain(),
                'quiz_id' => $attempt->quiz_assignment_id,
            ])->with('info', 'تم تقديم هذا الاختبار بالفعل');
        }

        $answers = $request->input('answers', []);
        $this->quizService->submitAttempt($attempt, $answers);

        return redirect()->route('student.quiz.result', [
            'subdomain' => $this->getSubdomain(),
            'quiz_id' => $attempt->quiz_assignment_id,
        ])->with('success', 'تم تقديم الاختبار بنجاح');
    }

    /**
     * Show quiz results
     */
    public function result(string $quiz_id): View|RedirectResponse
    {
        $assignment = QuizAssignment::findOrFail($quiz_id);

        // Authorize result access
        $this->authorize('viewResult', $assignment);

        $student = Auth::user()->studentProfile;

        $attempts = QuizAttempt::where('quiz_assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->completed()
            ->orderByDesc('submitted_at')
            ->get();

        if ($attempts->isEmpty()) {
            return redirect()->back()->with('error', 'لم تقم بأداء هذا الاختبار بعد');
        }

        $bestAttempt = $attempts->sortByDesc('score')->first();
        $quiz = $assignment->quiz->load('questions');

        return view('student.quiz.result', [
            'assignment' => $assignment,
            'quiz' => $quiz,
            'attempts' => $attempts,
            'bestAttempt' => $bestAttempt,
        ]);
    }
}
