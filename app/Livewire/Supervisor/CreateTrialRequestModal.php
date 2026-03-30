<?php

namespace App\Livewire\Supervisor;

use App\Enums\LearningGoal;
use App\Enums\QuranLearningLevel;
use App\Enums\TimeSlot;
use App\Enums\UserType;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\User;
use Livewire\Component;

class CreateTrialRequestModal extends Component
{
    public bool $showModal = false;

    // Form fields
    public ?int $student_id = null;

    public ?int $teacher_id = null;

    public string $student_name = '';

    public ?int $student_age = null;

    public string $phone = '';

    public string $email = '';

    public string $current_level = '';

    public string $preferred_time = '';

    public array $learning_goals = [];

    public string $notes = '';

    // Data for dropdowns
    public array $students = [];

    public array $teachers = [];

    protected function rules(): array
    {
        return [
            'student_id' => 'required|exists:users,id',
            'teacher_id' => 'required|exists:quran_teacher_profiles,id',
            'student_name' => 'required|string|max:255',
            'student_age' => 'nullable|integer|min:3|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'current_level' => 'required|string',
            'preferred_time' => 'nullable|string',
            'learning_goals' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function open(): void
    {
        $this->resetForm();
        $this->loadDropdownData();
        $this->showModal = true;
    }

    public function updatedStudentId(?int $value): void
    {
        if (! $value) {
            $this->student_name = '';
            $this->student_age = null;
            $this->phone = '';
            $this->email = '';

            return;
        }

        $user = User::find($value);
        if ($user) {
            $this->student_name = $user->name ?? '';
            $this->phone = $user->phone ?? '';
            $this->email = $user->email ?? '';
        }
    }

    public function create(): void
    {
        $this->validate();

        $academy = current_academy();

        QuranTrialRequest::create([
            'academy_id' => $academy->id,
            'student_id' => $this->student_id,
            'teacher_id' => $this->teacher_id,
            'student_name' => $this->student_name,
            'student_age' => $this->student_age,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'current_level' => $this->current_level,
            'preferred_time' => $this->preferred_time ?: null,
            'learning_goals' => ! empty($this->learning_goals) ? $this->learning_goals : null,
            'notes' => $this->notes ?: null,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        $this->showModal = false;
        $this->dispatch('trial-request-created');
        session()->flash('success', __('supervisor.trial_sessions.request_created_successfully'));
    }

    protected function resetForm(): void
    {
        $this->reset([
            'student_id', 'teacher_id', 'student_name', 'student_age',
            'phone', 'email', 'current_level', 'preferred_time',
            'learning_goals', 'notes',
        ]);
        $this->resetValidation();
    }

    protected function loadDropdownData(): void
    {
        $academy = current_academy();

        $this->students = User::where('user_type', UserType::STUDENT->value)
            ->where('academy_id', $academy->id)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
            ->toArray();

        $this->teachers = QuranTeacherProfile::where('academy_id', $academy->id)
            ->active()
            ->with('user')
            ->get()
            ->map(fn (QuranTeacherProfile $t) => [
                'id' => $t->id,
                'name' => $t->display_name ?? ($t->full_name ?? __('معلم غير محدد')).' ('.($t->teacher_code ?? 'N/A').')',
            ])
            ->toArray();
    }

    public function getLevelOptionsProperty(): array
    {
        return QuranLearningLevel::options();
    }

    public function getTimeOptionsProperty(): array
    {
        return TimeSlot::options();
    }

    public function getGoalOptionsProperty(): array
    {
        return LearningGoal::options();
    }

    public function render()
    {
        return view('livewire.supervisor.create-trial-request-modal');
    }
}
