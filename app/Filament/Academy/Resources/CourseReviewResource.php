<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\CourseReviewResource\Pages;
use App\Filament\Resources\CourseReviewResource as SuperAdminCourseReviewResource;

class CourseReviewResource extends SuperAdminCourseReviewResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'التقييمات والمراجعات';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseReviews::route('/'),
            'view' => Pages\ViewCourseReview::route('/{record}'),
            'edit' => Pages\EditCourseReview::route('/{record}/edit'),
        ];
    }
}
