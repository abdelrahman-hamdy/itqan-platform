<?php

namespace App\Filament\Resources\TeacherReviewResource\Pages;

use App\Filament\Resources\TeacherReviewResource;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTeacherReviews extends ListRecords
{
    protected static string $resource = TeacherReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->badge(fn () => static::getResource()::getModel()::count())
                ->icon('heroicon-o-star'),

            'quran' => Tab::make('معلمي القرآن')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('reviewable_type', QuranTeacherProfile::class))
                ->badge(fn () => static::getResource()::getModel()::where('reviewable_type', QuranTeacherProfile::class)->count())
                ->icon('heroicon-o-book-open'),

            'academic' => Tab::make('المعلمين الأكاديميين')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('reviewable_type', AcademicTeacherProfile::class))
                ->badge(fn () => static::getResource()::getModel()::where('reviewable_type', AcademicTeacherProfile::class)->count())
                ->icon('heroicon-o-academic-cap'),

            'pending' => Tab::make('بانتظار الموافقة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_approved', false))
                ->badge(fn () => static::getResource()::getModel()::where('is_approved', false)->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock'),
        ];
    }
}
