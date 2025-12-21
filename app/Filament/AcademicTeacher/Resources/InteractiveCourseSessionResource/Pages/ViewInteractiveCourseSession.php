<?php

namespace App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource\Pages;

use App\Enums\SessionStatus;
use App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource;
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
                Infolists\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('session_code')
                                    ->label('رمز الجلسة')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('title')
                                    ->label('العنوان'),
                                Infolists\Components\TextEntry::make('session_number')
                                    ->label('رقم الجلسة'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('course.title')
                                    ->label('الدورة'),
                                Infolists\Components\TextEntry::make('course.course_code')
                                    ->label('رمز الدورة'),
                            ]),
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
                        Infolists\Components\TextEntry::make('attendance_count')
                            ->label('عدد الحضور'),
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
                    ->collapsed(),

                Infolists\Components\Section::make('الواجب')
                    ->schema([
                        Infolists\Components\IconEntry::make('homework_assigned')
                            ->label('يوجد واجب')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('homework_description')
                            ->label('وصف الواجب')
                            ->placeholder('لا يوجد')
                            ->visible(fn ($record) => $record->homework_assigned),
                    ])
                    ->collapsed(),
            ]);
    }
}
