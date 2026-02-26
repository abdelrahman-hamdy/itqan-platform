<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\CourseReviewResource\Pages;
use App\Filament\Resources\CourseReviewResource as SuperAdminCourseReviewResource;
use App\Models\InteractiveCourse;
use App\Models\RecordedCourse;
use Illuminate\Database\Eloquent\Builder;

class CourseReviewResource extends SuperAdminCourseReviewResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'التقييمات والمراجعات';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $academyId = auth()->user()?->academy_id;
        if (!$academyId) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where(function ($q) use ($academyId) {
                $q->whereHasMorph('reviewable', InteractiveCourse::class, fn ($r) => $r->where('academy_id', $academyId))
                  ->orWhereHasMorph('reviewable', RecordedCourse::class, fn ($r) => $r->where('academy_id', $academyId));
            });
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
