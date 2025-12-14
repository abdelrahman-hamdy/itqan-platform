<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Shared\Resources\BaseHomeworkSubmissionResource;
use App\Filament\Teacher\Resources\HomeworkSubmissionResource\Pages;

/**
 * Homework Submission Resource for Teacher (Quran) Panel
 *
 * Extends BaseHomeworkSubmissionResource for shared functionality.
 * Filters by QuranSession submissions only.
 */
class HomeworkSubmissionResource extends BaseHomeworkSubmissionResource
{
    protected static ?string $navigationGroup = 'التقارير والتقييمات';

    protected static ?int $navigationSort = 2;
    /**
     * Get the submitable types for Quran teacher.
     */
    protected static function getSubmitableTypes(): array
    {
        return ['App\\Models\\QuranSession'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeworkSubmissions::route('/'),
            'view' => Pages\ViewHomeworkSubmission::route('/{record}'),
            'edit' => Pages\EditHomeworkSubmission::route('/{record}/edit'),
        ];
    }
}
