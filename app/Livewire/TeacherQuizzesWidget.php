<?php

namespace App\Livewire;

use App\Models\QuizAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TeacherQuizzesWidget extends Component
{
    #[Locked]
    public Model $assignable;

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
                $totalAttempts = $assignment->attempts->count();
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
                    'total_attempts' => $totalAttempts,
                    'completed_attempts' => $completedAttempts,
                    'passed_attempts' => $passedAttempts,
                    'avg_score' => $avgScore,
                    'is_available' => $assignment->isAvailable(),
                ];
            });
    }

    public function render()
    {
        return view('livewire.teacher-quizzes-widget', [
            'assignments' => $this->assignments,
        ]);
    }
}
