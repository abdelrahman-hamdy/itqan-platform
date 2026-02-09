<?php

namespace App\Filament\AcademicTeacher\Resources\InteractiveCourseResource\Pages;

use App\Enums\InteractiveCourseStatus;
use App\Filament\AcademicTeacher\Resources\InteractiveCourseResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewInteractiveCourse extends ViewRecord
{
    protected static string $resource = InteractiveCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title ?? 'تفاصيل الدورة التفاعلية';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الدورة')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('course_code')
                                    ->label('رمز الدورة')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('title')
                                    ->label('عنوان الدورة'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('الحالة')
                                    ->badge()
                                    ->formatStateUsing(function ($state): string {
                                        if ($state instanceof InteractiveCourseStatus) {
                                            return $state->label();
                                        }
                                        $status = InteractiveCourseStatus::tryFrom($state);

                                        return $status?->label() ?? (string) $state;
                                    })
                                    ->color(fn ($state): string => InteractiveCourseStatus::tryFrom($state)?->color() ?? 'gray'),
                            ]),
                    ]),

                Infolists\Components\Section::make('التفاصيل')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('subject.name')
                                    ->label('المادة')
                                    ->badge()
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('gradeLevel.name')
                                    ->label('المستوى')
                                    ->badge()
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('total_sessions')
                                    ->label('عدد الجلسات'),
                            ]),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('duration_per_session')
                                    ->label('مدة الجلسة')
                                    ->suffix(' دقيقة'),
                                Infolists\Components\TextEntry::make('price_per_student')
                                    ->label('السعر')
                                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR')),
                                Infolists\Components\TextEntry::make('enrolled_students_count')
                                    ->label('الطلاب المسجلين')
                                    ->getStateUsing(fn ($record) => $record->enrolledStudents()->count().' / '.$record->max_students),
                            ]),
                    ]),

                Infolists\Components\Section::make('التواريخ')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('start_date')
                                    ->label('تاريخ البداية')
                                    ->date('Y-m-d'),
                                Infolists\Components\TextEntry::make('end_date')
                                    ->label('تاريخ النهاية')
                                    ->date('Y-m-d'),
                                Infolists\Components\TextEntry::make('enrollment_deadline')
                                    ->label('آخر موعد للتسجيل')
                                    ->date('Y-m-d'),
                            ]),
                    ]),

                Infolists\Components\Section::make('الوصف والمحتوى')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('وصف الدورة')
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('لا يوجد وصف'),
                    ])
                    ->collapsed(),
            ]);
    }
}
