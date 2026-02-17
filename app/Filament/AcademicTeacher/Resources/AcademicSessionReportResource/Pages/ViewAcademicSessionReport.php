<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages;

use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use App\Enums\AttendanceStatus;
use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicSessionReport extends ViewRecord
{
    protected static string $resource = AcademicSessionReportResource::class;

    public function getTitle(): string
    {
        $studentName = $this->record->student?->name ?? 'طالب';
        $sessionTitle = $this->record->session?->title ?? 'جلسة';

        return "تقرير: {$studentName} - {$sessionTitle}";
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $infolist
            ->schema([
                Section::make('معلومات الجلسة والطالب')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('student.name')
                                    ->label('الطالب'),
                                TextEntry::make('session.title')
                                    ->label('الجلسة'),
                            ]),
                    ]),

                Section::make('الحضور')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('attendance_status')
                                    ->label('حالة الحضور')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        AttendanceStatus::ATTENDED->value => 'حاضر',
                                        AttendanceStatus::LATE->value => 'متأخر',
                                        AttendanceStatus::LEFT->value => 'غادر مبكراً',
                                        AttendanceStatus::ABSENT->value => 'غائب',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        AttendanceStatus::ATTENDED->value => 'success',
                                        AttendanceStatus::LATE->value => 'warning',
                                        AttendanceStatus::LEFT->value => 'info',
                                        AttendanceStatus::ABSENT->value => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('actual_attendance_minutes')
                                    ->label('مدة الحضور')
                                    ->suffix(' دقيقة'),
                                IconEntry::make('manually_evaluated')
                                    ->label('تقييم يدوي')
                                    ->boolean(),
                            ]),
                    ]),

                Section::make('تقييم الواجب')
                    ->schema([
                        TextEntry::make('homework_degree')
                            ->label('درجة الواجب')
                            ->suffix('/10')
                            ->badge()
                            ->color(fn (?string $state): string => match (true) {
                                $state === null => 'gray',
                                (float) $state >= 8 => 'success',
                                (float) $state >= 6 => 'warning',
                                default => 'danger',
                            }),
                    ]),

                Section::make('الملاحظات')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('لا توجد ملاحظات')
                            ->columnSpanFull(),
                        TextEntry::make('override_reason')
                            ->label('سبب التعديل')
                            ->placeholder('لا يوجد')
                            ->visible(fn ($record) => $record->manually_evaluated)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Section::make('تواريخ')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('evaluated_at')
                                    ->label('تاريخ التقييم')
                                    ->dateTime('Y-m-d H:i')
                                    ->placeholder('لم يقيم بعد'),
                                TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime('Y-m-d H:i'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
