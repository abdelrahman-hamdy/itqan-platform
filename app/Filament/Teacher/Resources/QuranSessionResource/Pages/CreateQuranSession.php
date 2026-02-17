<?php

namespace App\Filament\Teacher\Resources\QuranSessionResource\Pages;

use App\Enums\SessionStatus;
use App\Filament\Teacher\Resources\QuranSessionResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateQuranSession extends CreateRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        // Set the teacher and academy IDs
        $data['quran_teacher_id'] = $user->id;
        $data['academy_id'] = $user->academy_id;

        // Generate session code
        $data['session_code'] = 'QS-'.$user->academy_id.'-'.now()->format('Ymd').'-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Set default status
        $data['status'] = $data['status'] ?? SessionStatus::SCHEDULED->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $sessionData = $this->form->getState();

        // Handle homework creation if homework data exists
        if (isset($sessionData['sessionHomework']) && ! empty(array_filter($sessionData['sessionHomework']))) {
            $homeworkData = $sessionData['sessionHomework'];

            // Check if at least one homework type is selected
            $hasAnyHomework = ($homeworkData['has_new_memorization'] ?? false) ||
                             ($homeworkData['has_review'] ?? false) ||
                             ($homeworkData['has_comprehensive_review'] ?? false);

            if ($hasAnyHomework) {
                $this->record->sessionHomework()->create([
                    'created_by' => Auth::id(),
                    'has_new_memorization' => $homeworkData['has_new_memorization'] ?? false,
                    'has_review' => $homeworkData['has_review'] ?? false,
                    'has_comprehensive_review' => $homeworkData['has_comprehensive_review'] ?? false,
                    'new_memorization_pages' => $homeworkData['new_memorization_pages'] ?? null,
                    'new_memorization_surah' => $homeworkData['new_memorization_surah'] ?? null,
                    'review_pages' => $homeworkData['review_pages'] ?? null,
                    'review_surah' => $homeworkData['review_surah'] ?? null,
                    'comprehensive_review_surahs' => $homeworkData['comprehensive_review_surahs'] ?? null,
                    'additional_instructions' => $homeworkData['additional_instructions'] ?? null,
                    'is_active' => true,
                ]);
            }
        }
    }
}
