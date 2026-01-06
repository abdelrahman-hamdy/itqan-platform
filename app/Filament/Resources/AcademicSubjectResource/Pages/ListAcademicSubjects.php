<?php

namespace App\Filament\Resources\AcademicSubjectResource\Pages;

use App\Filament\Resources\AcademicSubjectResource;
use App\Services\AcademyContextService;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAcademicSubjects extends ListRecords
{
    protected static string $resource = AcademicSubjectResource::class;

    protected static ?string $title = 'المواد الأكاديمية';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة مادة جديدة')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();

                    return $academyId ? \App\Models\AcademicSubject::where('academy_id', $academyId)->count() : 0;
                }),

            'active' => Tab::make('نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();

                    return $academyId ? \App\Models\AcademicSubject::where('academy_id', $academyId)->where('is_active', true)->count() : 0;
                })
                ->badgeColor('success'),

            'inactive' => Tab::make('غير نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();

                    return $academyId ? \App\Models\AcademicSubject::where('academy_id', $academyId)->where('is_active', false)->count() : 0;
                })
                ->badgeColor('danger'),
        ];
    }
}
