<?php

namespace App\Filament\Academy\Resources\AcademicGradeLevelResource\Pages;

use App\Filament\Academy\Resources\AcademicGradeLevelResource;
use App\Models\AcademicGradeLevel;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAcademicGradeLevels extends ListRecords
{
    protected static string $resource = AcademicGradeLevelResource::class;

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
                ->badge(fn () => AcademicGradeLevel::count()),

            'active' => Tab::make('نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(fn () => AcademicGradeLevel::where('is_active', true)->count())
                ->badgeColor('success'),

            'inactive' => Tab::make('غير نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(fn () => AcademicGradeLevel::where('is_active', false)->count())
                ->badgeColor('danger'),
        ];
    }
}
