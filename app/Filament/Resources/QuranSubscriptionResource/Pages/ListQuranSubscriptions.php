<?php

namespace App\Filament\Resources\QuranSubscriptionResource\Pages;

use App\Enums\SubscriptionStatus;
use App\Filament\Resources\QuranSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListQuranSubscriptions extends ListRecords
{
    protected static string $resource = QuranSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة اشتراك جديد'),
        ];
    }

    public function getTitle(): string
    {
        return 'اشتراكات القرآن الكريم';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('filament.tabs.all'))
                ->badge(fn () => static::getResource()::getModel()::count())
                ->icon('heroicon-o-queue-list'),

            'active' => Tab::make(__('filament.tabs.active'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SubscriptionStatus::ACTIVE->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SubscriptionStatus::ACTIVE->value)->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle'),

            'pending' => Tab::make(__('filament.tabs.pending'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SubscriptionStatus::PENDING->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SubscriptionStatus::PENDING->value)->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock'),

            'paused' => Tab::make(__('filament.tabs.paused'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SubscriptionStatus::PAUSED->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SubscriptionStatus::PAUSED->value)->count())
                ->badgeColor('gray')
                ->icon('heroicon-o-pause-circle'),

            'expired' => Tab::make(__('filament.tabs.expired'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SubscriptionStatus::EXPIRED->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SubscriptionStatus::EXPIRED->value)->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-x-circle'),

            'cancelled' => Tab::make(__('filament.tabs.cancelled'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SubscriptionStatus::CANCELLED->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SubscriptionStatus::CANCELLED->value)->count())
                ->badgeColor('gray')
                ->icon('heroicon-o-no-symbol'),
        ];
    }
} 