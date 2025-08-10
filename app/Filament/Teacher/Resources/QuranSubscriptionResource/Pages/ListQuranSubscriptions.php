<?php

namespace App\Filament\Teacher\Resources\QuranSubscriptionResource\Pages;

use App\Filament\Teacher\Resources\QuranSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListQuranSubscriptions extends ListRecords
{
    protected static string $resource = QuranSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('جميع الاشتراكات'),
            'active' => Tab::make('النشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('subscription_status', 'active')),
            'expiring_soon' => Tab::make('تنتهي قريباً')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('expires_at', '<=', now()->addDays(30))
                    ->where('subscription_status', 'active')),
            'low_sessions' => Tab::make('جلسات قليلة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('sessions_remaining', '<', 5)
                    ->where('subscription_status', 'active')),
            'paused' => Tab::make('متوقفة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('subscription_status', 'paused')),
        ];
    }
}