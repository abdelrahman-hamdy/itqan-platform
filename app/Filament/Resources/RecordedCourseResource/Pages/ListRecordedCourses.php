<?php

namespace App\Filament\Resources\RecordedCourseResource\Pages;

use App\Filament\Resources\RecordedCourseResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRecordedCourses extends ListRecords
{
    protected static string $resource = RecordedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة دورة جديدة'),
        ];
    }

    public function getTitle(): string
    {
        return 'الدورات المسجلة';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('filament.tabs.all'))
                ->badge(fn () => static::getResource()::getModel()::count())
                ->icon('heroicon-o-queue-list'),

            'published' => Tab::make(__('filament.tabs.published'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', true))
                ->badge(fn () => static::getResource()::getModel()::where('is_published', true)->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle'),

            'draft' => Tab::make(__('filament.tabs.draft'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', false))
                ->badge(fn () => static::getResource()::getModel()::where('is_published', false)->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-pencil-square'),

            'free' => Tab::make(__('filament.tabs.free'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('price', 0))
                ->badge(fn () => static::getResource()::getModel()::where('price', 0)->count())
                ->badgeColor('info')
                ->icon('heroicon-o-gift'),

            'paid' => Tab::make(__('filament.tabs.paid'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('price', '>', 0))
                ->badge(fn () => static::getResource()::getModel()::where('price', '>', 0)->count())
                ->badgeColor('primary')
                ->icon('heroicon-o-banknotes'),
        ];
    }
} 