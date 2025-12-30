<?php

namespace App\Filament\Resources\TeacherPayoutResource\Pages;

use App\Enums\PayoutStatus;
use App\Filament\Resources\TeacherPayoutResource;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTeacherPayouts extends ListRecords
{
    protected static string $resource = TeacherPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->badge(fn () => static::getResource()::getModel()::count())
                ->icon('heroicon-o-banknotes'),

            'pending' => Tab::make('بانتظار الموافقة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PayoutStatus::PENDING->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', PayoutStatus::PENDING->value)->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock'),

            'approved' => Tab::make('تمت الموافقة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PayoutStatus::APPROVED->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', PayoutStatus::APPROVED->value)->count())
                ->badgeColor('info')
                ->icon('heroicon-o-check'),

            'paid' => Tab::make('تم الدفع')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PayoutStatus::PAID->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', PayoutStatus::PAID->value)->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle'),

            'rejected' => Tab::make('مرفوض')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PayoutStatus::REJECTED->value))
                ->badge(fn () => static::getResource()::getModel()::where('status', PayoutStatus::REJECTED->value)->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-x-circle'),
        ];
    }
}
