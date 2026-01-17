<?php

namespace App\Filament\Resources\QuranTeacherProfileResource\Pages;

use App\Filament\Resources\QuranTeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListQuranTeacherProfiles extends ListRecords
{
    protected static string $resource = QuranTeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة معلم جديد'),
        ];
    }

    public function getTitle(): string
    {
        return 'معلمي القرآن الكريم';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('filament.tabs.all'))
                ->badge(fn () => static::getResource()::getModel()::count())
                ->icon('heroicon-o-queue-list'),

            'active' => Tab::make(__('filament.tabs.active'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('active_status', true)))
                ->badge(fn () => static::getResource()::getModel()::whereHas('user', fn ($q) => $q->where('active_status', true))->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle'),

            'inactive' => Tab::make(__('filament.tabs.inactive'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('active_status', false)))
                ->badge(fn () => static::getResource()::getModel()::whereHas('user', fn ($q) => $q->where('active_status', false))->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock'),
        ];
    }
}
