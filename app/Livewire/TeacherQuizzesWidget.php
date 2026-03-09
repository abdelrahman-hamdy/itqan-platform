<?php

namespace App\Livewire;

use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Services\QuizService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TeacherQuizzesWidget extends Component
{
    #[Locked]
    public Model $assignable;

    // Assignment form fields
    public bool $showForm = false;
    public string $selectedQuizId = '';
    public int $maxAttempts = 1;
    public bool $isVisible = true;
    public ?string $availableFrom = null;
    public ?string $availableUntil = null;

    public string $successMessage = '';
    public string $errorMessage = '';

    public function mount(Model $assignable): void
    {
        $this->assignable = $assignable;
    }

    public function getAssignmentsProperty()
    {
        $user = Auth::user();

        return QuizAssignment::where('assignable_type', get_class($this->assignable))
            ->where('assignable_id', $this->assignable->id)
            ->whereHas('quiz', fn ($q) => $q->where('created_by', $user->id))
            ->with(['quiz', 'attempts'])
            ->latest()
            ->get()
            ->map(function ($assignment) {
                $completedAttempts = $assignment->attempts->whereNotNull('submitted_at')->count();
                $passedAttempts = $assignment->attempts->where('passed', true)->count();
                $avgScore = $completedAttempts > 0
                    ? round($assignment->attempts->whereNotNull('submitted_at')->avg('score'))
                    : null;

                return (object) [
                    'id' => $assignment->id,
                    'quiz' => $assignment->quiz,
                    'is_visible' => $assignment->is_visible,
                    'available_from' => $assignment->available_from,
                    'available_until' => $assignment->available_until,
                    'max_attempts' => $assignment->max_attempts,
                    'completed_attempts' => $completedAttempts,
                    'passed_attempts' => $passedAttempts,
                    'avg_score' => $avgScore,
                    'is_available' => $assignment->isAvailable(),
                    'can_revoke' => $completedAttempts === 0,
                ];
            });
    }

    /**
     * Get quizzes the teacher owns that are not already assigned to this entity.
     */
    public function getAvailableQuizzesProperty()
    {
        $user = Auth::user();
        $assignedQuizIds = QuizAssignment::where('assignable_type', get_class($this->assignable))
            ->where('assignable_id', $this->assignable->id)
            ->pluck('quiz_id');

        return Quiz::where('created_by', $user->id)
            ->where('is_active', true)
            ->whereNotIn('id', $assignedQuizIds)
            ->orderBy('title')
            ->get(['id', 'title', 'duration_minutes', 'passing_score']);
    }

    public function assignQuiz(): void
    {
        $this->successMessage = '';
        $this->errorMessage = '';

        if (! $this->selectedQuizId) {
            $this->errorMessage = __('teacher.quizzes.error_select_quiz');
            return;
        }

        $user = Auth::user();
        $quiz = Quiz::where('id', $this->selectedQuizId)
            ->where('created_by', $user->id)
            ->first();

        if (! $quiz) {
            $this->errorMessage = __('teacher.quizzes.quiz_not_found');
            return;
        }

        // Check if already assigned
        $exists = QuizAssignment::where('quiz_id', $quiz->id)
            ->where('assignable_type', get_class($this->assignable))
            ->where('assignable_id', $this->assignable->id)
            ->exists();

        if ($exists) {
            $this->errorMessage = __('teacher.quizzes.already_assigned');
            return;
        }

        $quizService = app(QuizService::class);
        $quizService->assignQuiz($quiz, $this->assignable, [
            'is_visible' => $this->isVisible,
            'max_attempts' => $this->maxAttempts,
            'available_from' => $this->availableFrom ?: null,
            'available_until' => $this->availableUntil ?: null,
        ]);

        $this->successMessage = __('teacher.quizzes.assigned_success');
        $this->resetForm();
    }

    public function revokeAssignment(string $assignmentId): void
    {
        $this->successMessage = '';
        $this->errorMessage = '';

        $assignment = QuizAssignment::with('quiz')
            ->where('id', $assignmentId)
            ->whereHas('quiz', fn ($q) => $q->where('created_by', Auth::id()))
            ->first();

        if (! $assignment) {
            $this->errorMessage = __('teacher.quizzes.assignment_not_found');
            return;
        }

        $completedCount = $assignment->attempts()->whereNotNull('submitted_at')->count();
        if ($completedCount > 0) {
            $this->errorMessage = __('teacher.quizzes.cannot_revoke_completed');
            return;
        }

        $assignment->delete();
        $this->successMessage = __('teacher.quizzes.assignment_revoked');
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;
        if (! $this->showForm) {
            $this->resetForm();
        }
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->selectedQuizId = '';
        $this->maxAttempts = 1;
        $this->isVisible = true;
        $this->availableFrom = null;
        $this->availableUntil = null;
        $this->errorMessage = '';
    }

    public function render()
    {
        return view('livewire.teacher-quizzes-widget', [
            'assignments' => $this->assignments,
            'availableQuizzes' => $this->availableQuizzes,
        ]);
    }
}
