<?php

namespace App\Filament\Teacher\Resources\QuranIndividualCircleResource\Pages;

use App\Filament\Teacher\Resources\QuranIndividualCircleResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;

class ViewQuranIndividualCircle extends ViewRecord
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getBreadcrumb(): string
    {
        return $this->getRecord()->student->name ?? 'حلقة فردية';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'حلقاتي الفردية',
            '' => $this->getBreadcrumb(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات أساسية')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('اسم الحلقة'),
                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('اسم الطالب'),
                                Infolists\Components\TextEntry::make('total_sessions')
                                    ->label('إجمالي الجلسات'),
                                Infolists\Components\TextEntry::make('sessions_completed')
                                    ->label('الجلسات المكتملة'),
                                Infolists\Components\TextEntry::make('sessions_remaining')
                                    ->label('الجلسات المتبقية'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('الحالة')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        SubscriptionStatus::ACTIVE->value => 'success',
                                        SubscriptionStatus::PENDING->value => 'warning',
                                        SubscriptionStatus::PAUSED->value => 'danger',
                                        SessionStatus::COMPLETED->value => 'info',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        SubscriptionStatus::ACTIVE->value => 'نشطة',
                                        SubscriptionStatus::PENDING->value => 'في الانتظار',
                                        SubscriptionStatus::PAUSED->value => 'متوقفة',
                                        SessionStatus::COMPLETED->value => 'مكتملة',
                                        default => $state,
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('معلومات الاشتراك')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('subscription.starts_at')
                                    ->label('تاريخ البداية')
                                    ->date(),
                                Infolists\Components\TextEntry::make('subscription.expires_at')
                                    ->label('تاريخ الانتهاء')
                                    ->date(),
                                Infolists\Components\TextEntry::make('subscription.package.name_ar')
                                    ->label('اسم الباقة'),
                            ]),
                    ]),
            ]);
    }
}
