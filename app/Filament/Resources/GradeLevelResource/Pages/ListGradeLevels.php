<?php

namespace App\Filament\Resources\GradeLevelResource\Pages;

use App\Filament\Resources\GradeLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Services\AcademyContextService;

class ListGradeLevels extends ListRecords
{
    protected static string $resource = GradeLevelResource::class;

    public function getTitle(): string
    {
        return 'المراحل الدراسية';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('active_grades')
                ->label('الصفوف النشطة')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();
                    return $academyId ? \App\Models\GradeLevel::where('academy_id', $academyId)->where('is_active', true)->count() : 0;
                }),
            Actions\Action::make('inactive_grades')
                ->label('الصفوف غير النشطة')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();
                    return $academyId ? \App\Models\GradeLevel::where('academy_id', $academyId)->where('is_active', false)->count() : 0;
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();
                    return $academyId ? \App\Models\GradeLevel::where('academy_id', $academyId)->count() : 0;
                }),
            
            'active' => Tab::make('نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();
                    return $academyId ? \App\Models\GradeLevel::where('academy_id', $academyId)->where('is_active', true)->count() : 0;
                })
                ->badgeColor('success'),
            
            'inactive' => Tab::make('غير نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();
                    return $academyId ? \App\Models\GradeLevel::where('academy_id', $academyId)->where('is_active', false)->count() : 0;
                })
                ->badgeColor('danger'),
        ];
    }
}
