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
    public $sessionId;

    public $showModal = false;

    // Form fields
    public $has_new_memorization = false;
    public $has_review = false;
    public $has_comprehensive_review = false;
    public $new_memorization_surah = null;
    public $new_memorization_pages = null;
    public $review_surah = null;
    public $review_pages = null;
    public $comprehensive_review_surahs = [];
    public $additional_instructions = null;

    public function mount($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function openAddModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(): void
    {
        $homework = QuranSessionHomework::where('session_id', $this->sessionId)->first();
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

    public function saveFormData(array $formData): void
    {
        $this->has_new_memorization = (bool) ($formData['has_new_memorization'] ?? false);
        $this->has_review = (bool) ($formData['has_review'] ?? false);
        $this->has_comprehensive_review = (bool) ($formData['has_comprehensive_review'] ?? false);
        $this->new_memorization_surah = $formData['new_memorization_surah'] ?? null;
        $this->new_memorization_pages = $formData['new_memorization_pages'] ?? null;
        $this->review_surah = $formData['review_surah'] ?? null;
        $this->review_pages = $formData['review_pages'] ?? null;
        $this->comprehensive_review_surahs = $formData['comprehensive_review_surahs'] ?? [];
        $this->additional_instructions = $formData['additional_instructions'] ?? null;
        $this->save();

        if (! $this->showModal) {
            $this->dispatch('toast', type: 'success', message: __('components.sessions.homework.saved_successfully'));
        }
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
