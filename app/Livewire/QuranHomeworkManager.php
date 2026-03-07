<?php

namespace App\Livewire;

use App\Enums\QuranSurah;
use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;

class QuranHomeworkManager extends Component
{
    #[Locked]
    public int|string $sessionId;

    public bool $showModal = false;

    // Form fields
    public bool $has_new_memorization = false;
    public bool $has_review = false;
    public bool $has_comprehensive_review = false;
    public ?string $new_memorization_surah = null;
    public ?float $new_memorization_pages = null;
    public ?string $review_surah = null;
    public ?float $review_pages = null;
    public array $comprehensive_review_surahs = [];
    public ?string $additional_instructions = null;

    public function mount(QuranSession $session): void
    {
        $this->sessionId = $session->id;
    }

    public function openAddModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(): void
    {
        $session = $this->getSession();
        if (! $session) {
            return;
        }

        $homework = $session->sessionHomework;
        if (! $homework) {
            $this->dispatch('toast', type: 'error', message: __('components.sessions.homework.loading_error'));

            return;
        }

        $this->has_new_memorization = (bool) $homework->has_new_memorization;
        $this->has_review = (bool) $homework->has_review;
        $this->has_comprehensive_review = (bool) $homework->has_comprehensive_review;
        $this->new_memorization_surah = $homework->new_memorization_surah;
        $this->new_memorization_pages = $homework->new_memorization_pages ? (float) $homework->new_memorization_pages : null;
        $this->review_surah = $homework->review_surah;
        $this->review_pages = $homework->review_pages ? (float) $homework->review_pages : null;
        $this->comprehensive_review_surahs = $homework->comprehensive_review_surahs ?? [];
        $this->additional_instructions = $homework->additional_instructions;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'has_new_memorization' => 'boolean',
            'has_review' => 'boolean',
            'has_comprehensive_review' => 'boolean',
            'new_memorization_surah' => 'nullable|string|max:255',
            'new_memorization_pages' => 'nullable|numeric|min:0.5|max:50',
            'review_surah' => 'nullable|string|max:255',
            'review_pages' => 'nullable|numeric|min:0.5|max:100',
            'comprehensive_review_surahs' => 'nullable|array',
            'comprehensive_review_surahs.*' => 'string|max:255',
            'additional_instructions' => 'nullable|string|max:1000',
        ]);

        if (! $this->has_new_memorization && ! $this->has_review && ! $this->has_comprehensive_review) {
            $this->dispatch('toast', type: 'error', message: __('components.sessions.homework.at_least_one_type'));

            return;
        }

        $session = $this->getSession();
        if (! $session) {
            $this->dispatch('toast', type: 'error', message: __('components.sessions.homework.loading_error'));

            return;
        }

        try {
            $homeworkData = [
                'session_id' => $this->sessionId,
                'created_by' => Auth::id(),
                'has_new_memorization' => $this->has_new_memorization,
                'has_review' => $this->has_review,
                'has_comprehensive_review' => $this->has_comprehensive_review,
                'new_memorization_surah' => $this->has_new_memorization ? $this->new_memorization_surah : null,
                'new_memorization_pages' => $this->has_new_memorization ? $this->new_memorization_pages : null,
                'review_surah' => $this->has_review ? $this->review_surah : null,
                'review_pages' => $this->has_review ? $this->review_pages : null,
                'comprehensive_review_surahs' => $this->has_comprehensive_review ? $this->comprehensive_review_surahs : null,
                'additional_instructions' => $this->additional_instructions,
                'is_active' => true,
            ];

            DB::transaction(function () use ($homeworkData) {
                QuranSessionHomework::updateOrCreate(
                    ['session_id' => $this->sessionId],
                    $homeworkData
                );
            });

            $this->showModal = false;
            $this->dispatch('toast', type: 'success', message: __('components.sessions.homework.saved_successfully'));
        } catch (\Exception $e) {
            Log::error('Error saving homework', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', type: 'error', message: __('components.sessions.homework.connection_error'));
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    private function getSession(): ?QuranSession
    {
        return QuranSession::where('id', $this->sessionId)
            ->where('quran_teacher_id', Auth::id())
            ->first();
    }

    private function resetForm(): void
    {
        $this->has_new_memorization = false;
        $this->has_review = false;
        $this->has_comprehensive_review = false;
        $this->new_memorization_surah = null;
        $this->new_memorization_pages = null;
        $this->review_surah = null;
        $this->review_pages = null;
        $this->comprehensive_review_surahs = [];
        $this->additional_instructions = null;
    }

    public function render()
    {
        $session = QuranSession::with('sessionHomework')->find($this->sessionId);

        return view('livewire.quran-homework-manager', [
            'session' => $session,
            'homework' => $session?->sessionHomework,
            'surahs' => QuranSurah::getAllSurahs(),
        ]);
    }
}
