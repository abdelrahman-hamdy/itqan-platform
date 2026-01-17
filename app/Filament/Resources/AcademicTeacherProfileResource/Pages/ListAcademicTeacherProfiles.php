<?php

namespace App\Filament\Resources\AcademicTeacherProfileResource\Pages;

use App\Filament\Resources\AcademicTeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAcademicTeacherProfiles extends ListRecords
{
    protected static string $resource = AcademicTeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة مدرس جديد'),
        ];
    }

    public function getTitle(): string
    {
        return 'المدرسين الأكاديميين';
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
