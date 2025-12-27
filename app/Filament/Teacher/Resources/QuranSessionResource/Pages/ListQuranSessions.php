<?php

namespace App\Filament\Teacher\Resources\QuranSessionResource\Pages;

use App\Filament\Teacher\Resources\QuranSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\SessionStatus;

class ListQuranSessions extends ListRecords
{
    protected static string $resource = QuranSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إنشاء جلسة جديدة'),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('جميع الجلسات'),
            'today' => Tab::make('جلسات اليوم')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('scheduled_at', today())),
            'this_week' => Tab::make('هذا الأسبوع')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('scheduled_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])),
            SessionStatus::SCHEDULED->value => Tab::make('مجدولة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SessionStatus::SCHEDULED->value)),
            SessionStatus::COMPLETED->value => Tab::make('مكتملة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SessionStatus::COMPLETED->value)),
        ];
    }
}