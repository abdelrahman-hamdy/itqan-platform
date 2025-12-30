<?php

namespace App\Filament\Resources\QuranCircleResource\Pages;

use App\Filament\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListQuranCircles extends ListRecords
{
    protected static string $resource = QuranCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة دائرة قرآن جديدة'),
        ];
    }

    public function getTitle(): string
    {
        return 'حلقات القرآن الكريم';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('filament.tabs.all'))
                ->badge(fn () => static::getResource()::getModel()::count())
                ->icon('heroicon-o-queue-list'),

            'individual' => Tab::make(__('filament.tabs.individual'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('circle_type', 'individual'))
                ->badge(fn () => static::getResource()::getModel()::where('circle_type', 'individual')->count())
                ->badgeColor('info')
                ->icon('heroicon-o-user'),

            'group' => Tab::make(__('filament.tabs.group'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('circle_type', 'group'))
                ->badge(fn () => static::getResource()::getModel()::where('circle_type', 'group')->count())
                ->badgeColor('primary')
                ->icon('heroicon-o-user-group'),

            'active' => Tab::make(__('filament.tabs.active'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', true))
                ->badge(fn () => static::getResource()::getModel()::where('status', true)->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle'),

            'inactive' => Tab::make(__('filament.tabs.inactive'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', false))
                ->badge(fn () => static::getResource()::getModel()::where('status', false)->count())
                ->badgeColor('gray')
                ->icon('heroicon-o-x-circle'),
        ];
    }
} 