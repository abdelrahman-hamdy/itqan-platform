<?php

namespace App\Filament\Resources\InteractiveCourseSessionResource\Pages;

use App\Enums\SessionStatus;
use App\Filament\Resources\InteractiveCourseSessionResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewInteractiveCourseSession extends ViewRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title ?? 'تفاصيل جلسة الدورة التفاعلية';
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
                                Infolists\Components\TextEntry::make('session_number')
                                    ->label('رقم الجلسة'),
                                Infolists\Components\TextEntry::make('title')
                                    ->label('العنوان'),
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

                Infolists\Components\Section::make('الدورة والمعلم')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('course.title')
                                    ->label('الدورة'),
                                Infolists\Components\TextEntry::make('course.assignedTeacher.user.name')
                                    ->label('المعلم'),
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
                                Infolists\Components\TextEntry::make('attendance_count')
                                    ->label('عدد الحضور'),
                            ]),
                    ]),

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
                    ->collapsible(),

                Infolists\Components\Section::make('الواجبات')
                    ->schema([
                        Infolists\Components\IconEntry::make('homework_assigned')
                            ->label('يوجد واجب')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('homework_description')
                            ->label('وصف الواجب')
                            ->columnSpanFull()
                            ->placeholder('لا يوجد وصف')
                            ->visible(fn ($record) => $record->homework_assigned),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->homework_assigned),

                Infolists\Components\Section::make('معلومات النظام')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
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
