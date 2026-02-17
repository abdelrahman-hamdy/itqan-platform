<?php

namespace App\Filament\AcademicTeacher\Resources\InteractiveCourseResource\Pages;

use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use App\Enums\InteractiveCourseStatus;
use App\Filament\AcademicTeacher\Resources\InteractiveCourseResource;
use Filament\Actions;
use Filament\Infolists;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewInteractiveCourse extends ViewRecord
{
    protected static string $resource = InteractiveCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title ?? 'تفاصيل الدورة التفاعلية';
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('معلومات الدورة')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('course_code')
                                    ->label('رمز الدورة')
                                    ->copyable(),
                                TextEntry::make('title')
                                    ->label('عنوان الدورة'),
                                TextEntry::make('status')
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

                Section::make('التفاصيل')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('subject.name')
                                    ->label('المادة')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('gradeLevel.name')
                                    ->label('المستوى')
                                    ->badge()
                                    ->color('success'),
                                TextEntry::make('total_sessions')
                                    ->label('عدد الجلسات'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('duration_per_session')
                                    ->label('مدة الجلسة')
                                    ->suffix(' دقيقة'),
                                TextEntry::make('price_per_student')
                                    ->label('السعر')
                                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR')),
                                TextEntry::make('enrolled_students_count')
                                    ->label('الطلاب المسجلين')
                                    ->getStateUsing(fn ($record) => $record->enrolledStudents()->count().' / '.$record->max_students),
                            ]),
                    ]),

                Section::make('التواريخ')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('start_date')
                                    ->label('تاريخ البداية')
                                    ->date('Y-m-d'),
                                TextEntry::make('end_date')
                                    ->label('تاريخ النهاية')
                                    ->date('Y-m-d'),
                                TextEntry::make('enrollment_deadline')
                                    ->label('آخر موعد للتسجيل')
                                    ->date('Y-m-d'),
                            ]),
                    ]),

                Section::make('الوصف والمحتوى')
                    ->schema([
                        TextEntry::make('description')
                            ->label('وصف الدورة')
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('لا يوجد وصف'),
                    ])
                    ->collapsed(),
            ]);
    }
}
