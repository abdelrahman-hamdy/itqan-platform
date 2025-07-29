<?php

namespace App\Filament\Resources\SubjectResource\Pages;

use App\Filament\Resources\SubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

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
                ->badge(fn () => \App\Models\Subject::count()),
                
            'academic' => Tab::make('المواد الأكاديمية')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_academic', true))
                ->badge(fn () => \App\Models\Subject::where('is_academic', true)->count()),
                
            'quran' => Tab::make('المواد القرآنية')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_academic', false))
                ->badge(fn () => \App\Models\Subject::where('is_academic', false)->count()),
                
            'active' => Tab::make('نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(fn () => \App\Models\Subject::where('is_active', true)->count())
                ->badgeColor('success'),
                
            'inactive' => Tab::make('غير نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(fn () => \App\Models\Subject::where('is_active', false)->count())
                ->badgeColor('danger'),
        ];
    }
} 