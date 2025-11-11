<?php

namespace App\Filament\Teacher\Resources\StudentProgressResource\Pages;

use App\Filament\Teacher\Resources\StudentProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStudentProgress extends ListRecords
{
    protected static string $resource = StudentProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('جميع الطلاب'),
            'excellent' => Tab::make('ممتاز')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', 5)),
            'high_progress' => Tab::make('تقدم عالي')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('progress_percentage', '>=', 75)),
            'needs_attention' => Tab::make('يحتاج متابعة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('progress_percentage', '<', 50)
                    ->orWhere('sessions_remaining', '<', 5)
                    ->orWhere('rating', '<=', 2)),
            'hafez' => Tab::make('حفاظ')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('memorization_level', 'hafez')),
        ];
    }
}