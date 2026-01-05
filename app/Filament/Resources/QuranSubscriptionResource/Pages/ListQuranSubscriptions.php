<?php

namespace App\Filament\Resources\QuranSubscriptionResource\Pages;

use App\Enums\SessionSubscriptionStatus;
use App\Filament\Resources\QuranSubscriptionResource;
use App\Models\QuranSubscription;
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
            'all' => Tab::make('الكل')
                ->badge(fn () => static::getResource()::getModel()::count())
                ->icon('heroicon-o-queue-list'),

            'individual' => Tab::make('الاشتراكات الفردية')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('subscription_type', QuranSubscription::SUBSCRIPTION_TYPE_INDIVIDUAL))
                ->badge(fn () => static::getResource()::getModel()::where('subscription_type', QuranSubscription::SUBSCRIPTION_TYPE_INDIVIDUAL)->count())
                ->badgeColor('primary')
                ->icon('heroicon-o-user'),

            'group' => Tab::make('اشتراكات الحلقات')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('subscription_type', QuranSubscription::SUBSCRIPTION_TYPE_CIRCLE))
                ->badge(fn () => static::getResource()::getModel()::where('subscription_type', QuranSubscription::SUBSCRIPTION_TYPE_CIRCLE)->count())
                ->badgeColor('success')
                ->icon('heroicon-o-user-group'),

            'active' => Tab::make('النشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SessionSubscriptionStatus::ACTIVE->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SessionSubscriptionStatus::ACTIVE->value)->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle'),

            'pending' => Tab::make('قيد الانتظار')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SessionSubscriptionStatus::PENDING->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SessionSubscriptionStatus::PENDING->value)->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock'),

            'paused' => Tab::make('متوقفة مؤقتاً')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SessionSubscriptionStatus::PAUSED->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SessionSubscriptionStatus::PAUSED->value)->count())
                ->badgeColor('info')
                ->icon('heroicon-o-pause-circle'),

            'cancelled' => Tab::make('ملغاة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SessionSubscriptionStatus::CANCELLED->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', SessionSubscriptionStatus::CANCELLED->value)->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-no-symbol'),
        ];
    }
}
