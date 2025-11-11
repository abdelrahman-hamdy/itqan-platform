<?php

namespace App\Filament\Teacher\Resources\QuranSessionResource\Pages;

use App\Filament\Teacher\Resources\QuranSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranSession extends ViewRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load homework data if exists for view mode
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
}
