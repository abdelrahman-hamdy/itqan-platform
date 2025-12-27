<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages;

use App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;

class ViewAcademicIndividualLesson extends ViewRecord
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->name ?? 'تفاصيل الدرس الفردي';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الدرس')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('lesson_code')
                                    ->label('رمز الدرس')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('name')
                                    ->label('اسم الدرس'),
                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('الطالب'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('academicSubject.name')
                                    ->label('المادة')
                                    ->badge()
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('academicGradeLevel.name')
                                    ->label('المستوى')
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ]),

                Infolists\Components\Section::make('تقدم الجلسات')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_sessions')
                                    ->label('إجمالي الجلسات'),
                                Infolists\Components\TextEntry::make('sessions_completed')
                                    ->label('الجلسات المكتملة'),
                                Infolists\Components\TextEntry::make('sessions_remaining')
                                    ->label('الجلسات المتبقية')
                                    ->getStateUsing(fn ($record) => $record->total_sessions - $record->sessions_completed),
                                Infolists\Components\TextEntry::make('progress_percentage')
                                    ->label('نسبة الإنجاز')
                                    ->suffix('%')
                                    ->badge()
                                    ->color(fn ($state): string => match (true) {
                                        (float) $state >= 80 => 'success',
                                        (float) $state >= 50 => 'warning',
                                        default => 'danger',
                                    }),
                            ]),
                        Infolists\Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                SubscriptionStatus::PENDING->value => 'قيد الانتظار',
                                SubscriptionStatus::ACTIVE->value => 'نشط',
                                SessionStatus::COMPLETED->value => 'مكتمل',
                                SessionStatus::CANCELLED->value => 'ملغي',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                SubscriptionStatus::PENDING->value => 'warning',
                                SubscriptionStatus::ACTIVE->value => 'success',
                                SessionStatus::COMPLETED->value => 'info',
                                SessionStatus::CANCELLED->value => 'danger',
                                default => 'gray',
                            }),
                    ]),

                Infolists\Components\Section::make('الوصف والملاحظات')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('وصف الدرس')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد وصف'),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull()
                            ->placeholder('لا توجد ملاحظات'),
                    ])
                    ->collapsed(),
            ]);
    }
}
