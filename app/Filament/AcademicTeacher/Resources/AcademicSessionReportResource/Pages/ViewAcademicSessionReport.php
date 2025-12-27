<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages;

use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use App\Enums\AttendanceStatus;

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
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الجلسة والطالب')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('الطالب'),
                                Infolists\Components\TextEntry::make('session.title')
                                    ->label('الجلسة'),
                            ]),
                    ]),

                Infolists\Components\Section::make('الحضور')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('attendance_status')
                                    ->label('حالة الحضور')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        AttendanceStatus::ATTENDED->value => 'حاضر',
                                        AttendanceStatus::LATE->value => 'متأخر',
                                        AttendanceStatus::LEAVED->value => 'غادر مبكراً',
                                        AttendanceStatus::ABSENT->value => 'غائب',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        AttendanceStatus::ATTENDED->value => 'success',
                                        AttendanceStatus::LATE->value => 'warning',
                                        AttendanceStatus::LEAVED->value => 'info',
                                        AttendanceStatus::ABSENT->value => 'danger',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('actual_attendance_minutes')
                                    ->label('مدة الحضور')
                                    ->suffix(' دقيقة'),
                                Infolists\Components\IconEntry::make('manually_evaluated')
                                    ->label('تقييم يدوي')
                                    ->boolean(),
                            ]),
                    ]),

                Infolists\Components\Section::make('تقييم الواجب')
                    ->schema([
                        Infolists\Components\TextEntry::make('homework_degree')
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

                Infolists\Components\Section::make('الملاحظات')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('لا توجد ملاحظات')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('override_reason')
                            ->label('سبب التعديل')
                            ->placeholder('لا يوجد')
                            ->visible(fn ($record) => $record->manually_evaluated)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Infolists\Components\Section::make('تواريخ')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('evaluated_at')
                                    ->label('تاريخ التقييم')
                                    ->dateTime('Y-m-d H:i')
                                    ->placeholder('لم يقيم بعد'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime('Y-m-d H:i'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
