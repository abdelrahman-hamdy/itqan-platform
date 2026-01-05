<?php

namespace App\Filament\Resources\QuranSessionResource\Pages;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Filament\Resources\QuranSessionResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

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
                Infolists\Components\Section::make('معلومات الجلسة الأساسية')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('session_code')
                                    ->label('رمز الجلسة')
                                    ->copyable()
                                    ->weight('bold'),
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
                    ]),

                Infolists\Components\Section::make('المعلم والطالب')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('quranTeacher.id')
                                    ->label('المعلم')
                                    ->formatStateUsing(fn ($record) =>
                                        trim(($record->quranTeacher?->first_name ?? '') . ' ' . ($record->quranTeacher?->last_name ?? '')) ?: 'معلم #' . ($record->quranTeacher?->id ?? '-')
                                    ),
                                Infolists\Components\TextEntry::make('student.id')
                                    ->label('الطالب')
                                    ->formatStateUsing(fn ($record) =>
                                        trim(($record->student?->first_name ?? '') . ' ' . ($record->student?->last_name ?? '')) ?: null
                                    )
                                    ->placeholder('جلسة جماعية'),
                                Infolists\Components\TextEntry::make('circle.name')
                                    ->label('الحلقة')
                                    ->placeholder('جلسة فردية'),
                            ]),
                    ]),

                Infolists\Components\Section::make('التوقيت')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->dateTime('Y-m-d H:i'),
                                Infolists\Components\TextEntry::make('duration_minutes')
                                    ->label('المدة المقررة')
                                    ->suffix(' دقيقة'),
                                Infolists\Components\TextEntry::make('started_at')
                                    ->label('وقت البدء')
                                    ->dateTime('H:i')
                                    ->placeholder('لم تبدأ'),
                                Infolists\Components\TextEntry::make('ended_at')
                                    ->label('وقت الانتهاء')
                                    ->dateTime('H:i')
                                    ->placeholder('لم تنته'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('actual_duration_minutes')
                                    ->label('المدة الفعلية')
                                    ->suffix(' دقيقة')
                                    ->placeholder('غير متاح'),
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
                    ]),

                Infolists\Components\Section::make('تفاصيل الجلسة')
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
                    ->collapsible(),

                Infolists\Components\Section::make('الواجب المنزلي')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\IconEntry::make('sessionHomework.has_new_memorization')
                                    ->label('حفظ جديد')
                                    ->boolean(),
                                Infolists\Components\IconEntry::make('sessionHomework.has_review')
                                    ->label('مراجعة')
                                    ->boolean(),
                                Infolists\Components\IconEntry::make('sessionHomework.has_comprehensive_review')
                                    ->label('مراجعة شاملة')
                                    ->boolean(),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('sessionHomework.new_memorization_surah')
                                    ->label('سورة الحفظ الجديد')
                                    ->placeholder('غير محدد')
                                    ->visible(fn ($record) => $record->sessionHomework?->has_new_memorization),
                                Infolists\Components\TextEntry::make('sessionHomework.new_memorization_pages')
                                    ->label('عدد أوجه الحفظ')
                                    ->suffix(' وجه')
                                    ->placeholder('غير محدد')
                                    ->visible(fn ($record) => $record->sessionHomework?->has_new_memorization),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('sessionHomework.review_surah')
                                    ->label('سورة المراجعة')
                                    ->placeholder('غير محدد')
                                    ->visible(fn ($record) => $record->sessionHomework?->has_review),
                                Infolists\Components\TextEntry::make('sessionHomework.review_pages')
                                    ->label('عدد أوجه المراجعة')
                                    ->suffix(' وجه')
                                    ->placeholder('غير محدد')
                                    ->visible(fn ($record) => $record->sessionHomework?->has_review),
                            ]),
                        Infolists\Components\TextEntry::make('sessionHomework.comprehensive_review_surahs')
                            ->label('سور المراجعة الشاملة')
                            ->placeholder('غير محدد')
                            ->visible(fn ($record) => $record->sessionHomework?->has_comprehensive_review),
                        Infolists\Components\TextEntry::make('sessionHomework.additional_instructions')
                            ->label('تعليمات إضافية')
                            ->columnSpanFull()
                            ->placeholder('لا توجد تعليمات'),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('ملاحظات')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('session_notes')
                                    ->label('ملاحظات الجلسة')
                                    ->placeholder('لا توجد ملاحظات'),
                                Infolists\Components\TextEntry::make('supervisor_notes')
                                    ->label('ملاحظات المشرف')
                                    ->placeholder('لا توجد ملاحظات'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make('معلومات النظام')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('academy.name')
                                    ->label('الأكاديمية'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('آخر تحديث')
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
