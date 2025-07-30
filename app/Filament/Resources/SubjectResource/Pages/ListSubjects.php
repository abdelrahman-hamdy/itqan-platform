<?php

namespace App\Filament\Resources\SubjectResource\Pages;

use App\Filament\Resources\SubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Services\AcademyContextService;

class ListSubjects extends ListRecords
{
    protected static string $resource = SubjectResource::class;
    
    protected static ?string $title = 'المواد الدراسية';

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
                    return $academyId ? \App\Models\Subject::where('academy_id', $academyId)->count() : 0;
                }),
                
            'active' => Tab::make('نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();
                    return $academyId ? \App\Models\Subject::where('academy_id', $academyId)->where('is_active', true)->count() : 0;
                })
                ->badgeColor('success'),
                
            'inactive' => Tab::make('غير نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();
                    return $academyId ? \App\Models\Subject::where('academy_id', $academyId)->where('is_active', false)->count() : 0;
                })
                ->badgeColor('danger'),
        ];
    }
} 