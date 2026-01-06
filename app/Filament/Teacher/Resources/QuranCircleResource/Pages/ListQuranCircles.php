<?php

namespace App\Filament\Teacher\Resources\QuranCircleResource\Pages;

use App\Enums\SessionSubscriptionStatus;
use App\Filament\Teacher\Resources\QuranCircleResource;
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
                ->label('إنشاء حلقة جديدة'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'حلقاتي الجماعية',
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('جميع الحلقات'),
            SessionSubscriptionStatus::ACTIVE->value => Tab::make('النشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', true)),
            'inactive' => Tab::make('غير نشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', false)),
            'open_registration' => Tab::make('تقبل تسجيلات')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('enrollment_status', 'open')
                    ->where('status', true)),
            'full_capacity' => Tab::make('مكتملة العدد')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('enrollment_status', 'full')),
        ];
    }
}
