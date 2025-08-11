<?php

namespace App\Filament\Teacher\Resources\QuranCircleResource\Pages;

use App\Filament\Teacher\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListQuranCircles extends ListRecords
{
    protected static string $resource = QuranCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إنشاء حلقة جديدة'),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('جميع الحلقات'),
            'planning' => Tab::make('قيد التخطيط')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'planning')),
            'active' => Tab::make('النشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),
            'accepting_registrations' => Tab::make('تقبل تسجيلات')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('accepting_registrations', true)
                    ->where('status', 'active')),
            'full_capacity' => Tab::make('مكتملة العدد')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereColumn('current_students', '>=', 'max_students')),
            'paused' => Tab::make('متوقفة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paused')),
        ];
    }
}