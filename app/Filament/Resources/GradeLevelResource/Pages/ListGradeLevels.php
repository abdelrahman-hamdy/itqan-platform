<?php

namespace App\Filament\Resources\GradeLevelResource\Pages;

use App\Filament\Resources\GradeLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

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
            Actions\CreateAction::make()
                ->label('إضافة مرحلة جديدة')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->badge(fn () => \App\Models\GradeLevel::where('academy_id', auth()->user()->academy_id ?? 1)->count()),
            
            'active' => Tab::make('نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(fn () => \App\Models\GradeLevel::where('academy_id', auth()->user()->academy_id ?? 1)->where('is_active', true)->count())
                ->badgeColor('success'),
            
            'inactive' => Tab::make('غير نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(fn () => \App\Models\GradeLevel::where('academy_id', auth()->user()->academy_id ?? 1)->where('is_active', false)->count())
                ->badgeColor('danger'),
        ];
    }
}
