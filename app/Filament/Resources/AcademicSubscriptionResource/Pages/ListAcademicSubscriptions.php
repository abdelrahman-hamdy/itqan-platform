<?php

namespace App\Filament\Resources\AcademicSubscriptionResource\Pages;

use App\Enums\SessionSubscriptionStatus;
use App\Filament\Resources\AcademicSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAcademicSubscriptions extends ListRecords
{
    protected static string $resource = AcademicSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة اشتراك جديد'),
        ];
    }

    public function getTitle(): string
    {
        return 'الاشتراكات الأكاديمية';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('filament.tabs.all'))
                ->badge(fn () => static::getResource()::getModel()::count())
                ->icon('heroicon-o-queue-list'),

            'active' => Tab::make(__('filament.tabs.active'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SessionSubscriptionStatus::ACTIVE->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SessionSubscriptionStatus::ACTIVE->value)->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle'),

            'pending' => Tab::make(__('filament.tabs.pending'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SessionSubscriptionStatus::PENDING->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SessionSubscriptionStatus::PENDING->value)->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock'),

            'paused' => Tab::make(__('filament.tabs.paused'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SessionSubscriptionStatus::PAUSED->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SessionSubscriptionStatus::PAUSED->value)->count())
                ->badgeColor('info')
                ->icon('heroicon-o-pause-circle'),

            'cancelled' => Tab::make(__('filament.tabs.cancelled'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SessionSubscriptionStatus::CANCELLED->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SessionSubscriptionStatus::CANCELLED->value)->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-no-symbol'),
        ];
    }
}
