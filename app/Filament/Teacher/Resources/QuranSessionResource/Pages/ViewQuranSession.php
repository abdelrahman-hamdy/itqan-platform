<?php

namespace App\Filament\Teacher\Resources\QuranSessionResource\Pages;

use App\Enums\QuranSurah;
use App\Enums\SessionStatus;
use App\Filament\Teacher\Resources\QuranSessionResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use App\Enums\AttendanceStatus;
use App\Enums\SessionSubscriptionStatus;

class ViewQuranSession extends ViewRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title ?? 'تفاصيل جلسة القرآن';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('session_code')
                                    ->label('رمز الجلسة')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('title')
                                    ->label('العنوان'),
                                Infolists\Components\TextEntry::make('session_type')
                                    ->label('نوع الجلسة')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'individual' => 'فردية',
                                        'group' => 'جماعية',
                                        'trial' => 'تجريبية',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'individual' => 'primary',
                                        'group' => 'success',
                                        'trial' => 'warning',
                                        default => 'gray',
                                    }),
                            ]),
                        Infolists\Components\TextEntry::make('student.name')
                            ->label('الطالب'),
                    ]),

                Infolists\Components\Section::make('التوقيت والحالة')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->dateTime('Y-m-d H:i'),
                                Infolists\Components\TextEntry::make('duration_minutes')
                                    ->label('المدة')
                                    ->suffix(' دقيقة'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('الحالة')
                                    ->badge()
                                    ->formatStateUsing(function ($state): string {
                                        if ($state instanceof SessionStatus) {
                                            return $state->label();
                                        }
                                        $status = SessionStatus::tryFrom($state);
                                        return $status?->label() ?? (string) $state;
                                    })
                                    ->color(function ($state): string {
                                        if ($state instanceof SessionStatus) {
                                            return $state->color();
                                        }
                                        return SessionStatus::tryFrom($state)?->color() ?? 'gray';
                                    }),
                            ]),
                        Infolists\Components\TextEntry::make('attendance_status')
                            ->label('حالة الحضور')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                AttendanceStatus::ATTENDED->value => 'حاضر',
                                AttendanceStatus::ABSENT->value => 'غائب',
                                AttendanceStatus::LATE->value => 'متأخر',
                                AttendanceStatus::LEFT->value => 'غادر مبكراً',
                                SessionSubscriptionStatus::PENDING->value => 'في الانتظار',
                                default => 'غير محدد',
                            })
                            ->color(fn (?string $state): string => match ($state) {
                                AttendanceStatus::ATTENDED->value => 'success',
                                AttendanceStatus::ABSENT->value => 'danger',
                                AttendanceStatus::LATE->value => 'warning',
                                AttendanceStatus::LEFT->value => 'info',
                                default => 'gray',
                            }),
                    ]),

                Infolists\Components\Section::make('الواجب المنزلي')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\IconEntry::make('sessionHomework.has_new_memorization')
                                    ->label('حفظ جديد')
                                    ->boolean(),
                                Infolists\Components\TextEntry::make('sessionHomework.new_memorization_surah')
                                    ->label('سورة الحفظ')
                                    ->formatStateUsing(fn ($state) => $state ? QuranSurah::from($state)->value : '-')
                                    ->visible(fn ($record) => $record->sessionHomework?->has_new_memorization),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\IconEntry::make('sessionHomework.has_review')
                                    ->label('مراجعة')
                                    ->boolean(),
                                Infolists\Components\TextEntry::make('sessionHomework.review_surah')
                                    ->label('سورة المراجعة')
                                    ->formatStateUsing(fn ($state) => $state ? QuranSurah::from($state)->value : '-')
                                    ->visible(fn ($record) => $record->sessionHomework?->has_review),
                            ]),
                        Infolists\Components\TextEntry::make('sessionHomework.additional_instructions')
                            ->label('تعليمات إضافية')
                            ->placeholder('لا توجد')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => $record->sessionHomework !== null),

                Infolists\Components\Section::make('المحتوى')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('وصف الجلسة')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد وصف'),
                        Infolists\Components\TextEntry::make('lesson_content')
                            ->label('محتوى الدرس')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد محتوى'),
                    ])
                    ->collapsed(),
            ]);
    }
}
