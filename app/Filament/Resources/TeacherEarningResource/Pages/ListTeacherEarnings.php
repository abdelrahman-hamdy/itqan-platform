<?php

namespace App\Filament\Resources\TeacherEarningResource\Pages;

use App\Filament\Resources\TeacherEarningResource;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTeacherEarnings extends ListRecords
{
    protected static string $resource = TeacherEarningResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->badge(fn () => static::getResource()::getModel()::count())
                ->icon('heroicon-o-currency-dollar'),

            'quran' => Tab::make('معلمي القرآن')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('teacher_type', QuranTeacherProfile::class))
                ->badge(fn () => static::getResource()::getModel()::where('teacher_type', QuranTeacherProfile::class)->count())
                ->icon('heroicon-o-book-open'),

            'academic' => Tab::make('المعلمين الأكاديميين')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('teacher_type', AcademicTeacherProfile::class))
                ->badge(fn () => static::getResource()::getModel()::where('teacher_type', AcademicTeacherProfile::class)->count())
                ->icon('heroicon-o-academic-cap'),

            'unpaid' => Tab::make('غير مصروف')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNull('payout_id')
                    ->where('is_finalized', false)
                    ->where('is_disputed', false))
                ->badge(fn () => static::getResource()::getModel()::whereNull('payout_id')
                    ->where('is_finalized', false)
                    ->where('is_disputed', false)
                    ->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock'),

            'disputed' => Tab::make('معترض عليه')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_disputed', true))
                ->badge(fn () => static::getResource()::getModel()::where('is_disputed', true)->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
