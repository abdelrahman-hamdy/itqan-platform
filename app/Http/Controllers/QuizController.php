<?php

namespace App\Http\Controllers;

use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function index()
    {
        $student = Auth::user()->studentProfile;

        if (!$student) {
            abort(403, 'يجب أن تكون طالباً لعرض الاختبارات');
        }

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
    public function start(string $quiz_id)
    {
        \Log::info('QuizController::start() called', [
            'quiz_id' => $quiz_id,
            'user_id' => Auth::id(),
            'route_params' => request()->route()->parameters(),
        ]);

        // Check if user is a student
        if (!Auth::user()->isStudent()) {
            abort(403, 'يجب أن تكون طالباً لأداء الاختبار');
        }

        $assignment = QuizAssignment::findOrFail($quiz_id);
        $student = Auth::user()->studentProfile;

        if (!$student) {
            abort(403, 'يجب أن تكون طالباً لأداء الاختبار');
        }

        if (!$assignment->isAvailable()) {
            return back()->with('error', 'هذا الاختبار غير متاح حالياً');
        }

        if (!$assignment->canStudentAttempt($student->id)) {
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
    public function take(string $attempt_id)
    {
        $attempt = QuizAttempt::findOrFail($attempt_id);
        $student = Auth::user()->studentProfile;

        if (!$student || $attempt->student_id !== $student->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا الاختبار');
        }

        if ($attempt->isCompleted()) {
            return redirect()->route('student.quiz.result', [
                'subdomain' => $this->getSubdomain(),
                'quiz_id' => $attempt->quiz_assignment_id,
            ]);
        }

        $quiz = $attempt->assignment->quiz->load('questions');
        $questions = $quiz->questions->shuffle(); // Randomize questions

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
    public function submit(Request $request, string $attempt_id)
    {
        $attempt = QuizAttempt::findOrFail($attempt_id);
        $student = Auth::user()->studentProfile;

        if (!$student || $attempt->student_id !== $student->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا الاختبار');
        }

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
    public function result(string $quiz_id)
    {
        $assignment = QuizAssignment::findOrFail($quiz_id);
        $student = Auth::user()->studentProfile;

        if (!$student) {
            abort(403, 'يجب أن تكون طالباً لعرض النتائج');
        }

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
