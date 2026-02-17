<?php

namespace App\Filament\Resources\AcademicGradeLevelResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use App\Models\AcademicGradeLevel;
use App\Filament\Resources\AcademicGradeLevelResource;
use App\Services\AcademyContextService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAcademicGradeLevels extends ListRecords
{
    protected static string $resource = AcademicGradeLevelResource::class;

    public function getTitle(): string
    {
        return 'الصفوف الدراسية';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();

                    return $academyId ? AcademicGradeLevel::where('academy_id', $academyId)->count() : 0;
                }),

            'active' => Tab::make('نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();

                    return $academyId ? AcademicGradeLevel::where('academy_id', $academyId)->where('is_active', true)->count() : 0;
                })
                ->badgeColor('success'),

            'inactive' => Tab::make('غير نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();

                    return $academyId ? AcademicGradeLevel::where('academy_id', $academyId)->where('is_active', false)->count() : 0;
                })
                ->badgeColor('danger'),
        ];
    }
}
