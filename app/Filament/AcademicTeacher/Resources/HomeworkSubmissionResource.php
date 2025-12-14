<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\Shared\Resources\BaseHomeworkSubmissionResource;
use App\Filament\AcademicTeacher\Resources\HomeworkSubmissionResource\Pages;

/**
 * Homework Submission Resource for AcademicTeacher Panel
 *
 * Extends BaseHomeworkSubmissionResource for shared functionality.
 * Filters by AcademicSession and InteractiveCourseSession submissions.
 * Shows submitable type column/field since it handles multiple types.
 */
class HomeworkSubmissionResource extends BaseHomeworkSubmissionResource
{
    protected static ?string $navigationGroup = 'التقارير والتقييمات';

    protected static ?int $navigationSort = 3;
    /**
     * Get the submitable types for Academic teacher.
     */
    protected static function getSubmitableTypes(): array
    {
        return [
            'App\\Models\\AcademicSession',
            'App\\Models\\InteractiveCourseSession',
        ];
    }

    /**
     * Show submitable type info since we handle multiple types.
     */
    protected static function showSubmitableTypeInfo(): bool
    {
        return true;
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
