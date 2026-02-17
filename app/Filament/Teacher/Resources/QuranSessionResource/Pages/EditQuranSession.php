<?php

namespace App\Filament\Teacher\Resources\QuranSessionResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Models\QuranSession;
use App\Filament\Teacher\Resources\QuranSessionResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use Illuminate\Support\Facades\Auth;

/**
 * @property QuranSession $record
 */
class EditQuranSession extends EditRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
                ->label('حذف')
                ->after(function () {
                    // Update session counts for individual circles if needed
                    $record = $this->getRecord();
                    if ($record->individualCircle) {
                        $record->individualCircle->updateSessionCounts();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load homework data if exists
        if ($this->record->sessionHomework) {
            $homework = $this->record->sessionHomework;

            // Ensure comprehensive_review_surahs is properly formatted as array with enum keys
            $comprehensiveReviewSurahs = $homework->comprehensive_review_surahs;
            if (is_string($comprehensiveReviewSurahs)) {
                $comprehensiveReviewSurahs = json_decode($comprehensiveReviewSurahs, true) ?: [];
            } elseif (! is_array($comprehensiveReviewSurahs)) {
                $comprehensiveReviewSurahs = [];
            }

            $data['sessionHomework'] = [
                'has_new_memorization' => $homework->has_new_memorization,
                'has_review' => $homework->has_review,
                'has_comprehensive_review' => $homework->has_comprehensive_review,
                'new_memorization_pages' => $homework->new_memorization_pages,
                'new_memorization_surah' => $homework->new_memorization_surah,
                'review_pages' => $homework->review_pages,
                'review_surah' => $homework->review_surah,
                'comprehensive_review_surahs' => $comprehensiveReviewSurahs,
                'additional_instructions' => $homework->additional_instructions,
            ];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $sessionData = $this->form->getState();

        // Handle homework update if homework data exists
        if (isset($sessionData['sessionHomework'])) {
            $homeworkData = $sessionData['sessionHomework'];

            // Check if at least one homework type is selected
            $hasAnyHomework = ($homeworkData['has_new_memorization'] ?? false) ||
                             ($homeworkData['has_review'] ?? false) ||
                             ($homeworkData['has_comprehensive_review'] ?? false);

            if ($hasAnyHomework) {
                $this->record->sessionHomework()->updateOrCreate(
                    ['session_id' => $this->record->id],
                    [
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
                    ]
                );
            } else {
                // Delete homework if no types are selected
                $this->record->sessionHomework()->delete();
            }
        }
    }
}
