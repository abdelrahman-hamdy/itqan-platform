<?php

namespace App\Livewire;

use App\Services\QuizService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * @property Collection $quizzes
 */
class QuizzesWidget extends Component
{
    public Model $assignable;

    public ?int $studentId = null;

    public function mount(Model $assignable, ?int $studentId = null)
    {
        $this->assignable = $assignable;
        $this->studentId = $studentId ?? auth()->user()?->studentProfile?->id;
    }

    public function getQuizzesProperty()
    {
        if (! $this->studentId) {
            return collect();
        }

        $quizService = app(QuizService::class);

        return $quizService->getAvailableQuizzes($this->assignable, $this->studentId);
    }

    public function render()
    {
        return view('livewire.quizzes-widget', [
            'quizzes' => $this->quizzes,
        ]);
    }
}
