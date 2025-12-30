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

            'pending_approval' => Tab::make(__('filament.tabs.pending_approval'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approval_status', 'pending'))
                ->badge(fn () => static::getResource()::getModel()::where('approval_status', 'pending')->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock'),

            'approved' => Tab::make(__('filament.tabs.approved'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approval_status', 'approved'))
                ->badge(fn () => static::getResource()::getModel()::where('approval_status', 'approved')->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle'),

            'active' => Tab::make(__('filament.tabs.active'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(fn () => static::getResource()::getModel()::where('is_active', true)->count())
                ->badgeColor('success')
                ->icon('heroicon-o-user-circle'),

            'inactive' => Tab::make(__('filament.tabs.inactive'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(fn () => static::getResource()::getModel()::where('is_active', false)->count())
                ->badgeColor('gray')
                ->icon('heroicon-o-user-minus'),

            'rejected' => Tab::make(__('filament.tabs.rejected'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approval_status', 'rejected'))
                ->badge(fn () => static::getResource()::getModel()::where('approval_status', 'rejected')->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-x-circle'),
        ];
    }
}
