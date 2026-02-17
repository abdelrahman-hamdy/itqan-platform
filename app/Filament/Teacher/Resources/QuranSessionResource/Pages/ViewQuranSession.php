<?php

namespace App\Filament\Teacher\Resources\QuranSessionResource\Pages;

use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use App\Services\AcademyContextService;
use Filament\Infolists\Components\IconEntry;
use App\Enums\AttendanceStatus;
use App\Enums\QuranSurah;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Filament\Teacher\Resources\QuranSessionResource;
use Filament\Actions;
use Filament\Infolists;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewQuranSession extends ViewRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title ?? 'تفاصيل جلسة القرآن';
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('معلومات الجلسة')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('session_code')
                                    ->label('رمز الجلسة')
                                    ->copyable(),
                                TextEntry::make('title')
                                    ->label('العنوان'),
                                TextEntry::make('session_type')
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
                        TextEntry::make('student.name')
                            ->label('الطالب'),
                    ]),

                Section::make('التوقيت والحالة')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->dateTime('Y-m-d H:i')
                                    ->timezone(AcademyContextService::getTimezone()),
                                TextEntry::make('duration_minutes')
                                    ->label('المدة')
                                    ->suffix(' دقيقة'),
                                TextEntry::make('status')
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
                        TextEntry::make('attendance_status')
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

                Section::make('الواجب المنزلي')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                IconEntry::make('sessionHomework.has_new_memorization')
                                    ->label('حفظ جديد')
                                    ->boolean(),
                                TextEntry::make('sessionHomework.new_memorization_surah')
                                    ->label('سورة الحفظ')
                                    ->formatStateUsing(fn ($state) => $state ? QuranSurah::from($state)->value : '-')
                                    ->visible(fn ($record) => $record->sessionHomework?->has_new_memorization),
                            ]),
                        Grid::make(2)
                            ->schema([
                                IconEntry::make('sessionHomework.has_review')
                                    ->label('مراجعة')
                                    ->boolean(),
                                TextEntry::make('sessionHomework.review_surah')
                                    ->label('سورة المراجعة')
                                    ->formatStateUsing(fn ($state) => $state ? QuranSurah::from($state)->value : '-')
                                    ->visible(fn ($record) => $record->sessionHomework?->has_review),
                            ]),
                        TextEntry::make('sessionHomework.additional_instructions')
                            ->label('تعليمات إضافية')
                            ->placeholder('لا توجد')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => $record->sessionHomework !== null),

                Section::make('المحتوى')
                    ->schema([
                        TextEntry::make('description')
                            ->label('وصف الجلسة')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد وصف'),
                        TextEntry::make('lesson_content')
                            ->label('محتوى الدرس')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد محتوى'),
                    ])
                    ->collapsed(),
            ]);
    }
}
