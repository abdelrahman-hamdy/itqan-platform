<?php

namespace App\Filament\Teacher\Resources\StudentSessionReportResource\Pages;

use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use App\Enums\AttendanceStatus;
use App\Filament\Teacher\Resources\StudentSessionReportResource;
use Filament\Actions;
use Filament\Infolists;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewStudentSessionReport extends ViewRecord
{
    protected static string $resource = StudentSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'تقرير '.($this->getRecord()->student->name ?? 'الطالب');
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'تقارير الطلاب',
            '' => $this->getBreadcrumb(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('معلومات الطالب والجلسة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('student.name')
                                    ->label('اسم الطالب'),
                                TextEntry::make('session.scheduled_at')
                                    ->label('تاريخ الجلسة')
                                    ->dateTime('Y-m-d H:i'),
                                TextEntry::make('session.session_type')
                                    ->label('نوع الجلسة')
                                    ->formatStateUsing(function (string $state): string {
                                        return match ($state) {
                                            'individual' => 'فردية',
                                            'group' => 'جماعية',
                                            default => $state,
                                        };
                                    })
                                    ->badge(),
                                TextEntry::make('attendance_status')
                                    ->label('حالة الحضور')
                                    ->formatStateUsing(function (string $state): string {
                                        return match ($state) {
                                            AttendanceStatus::ATTENDED->value => 'حاضر',
                                            AttendanceStatus::LATE->value => 'متأخر',
                                            AttendanceStatus::LEFT->value => 'غادر مبكراً',
                                            AttendanceStatus::ABSENT->value => 'غائب',
                                            default => $state,
                                        };
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        AttendanceStatus::ATTENDED->value => 'success',
                                        AttendanceStatus::LATE->value => 'warning',
                                        AttendanceStatus::LEFT->value => 'info',
                                        AttendanceStatus::ABSENT->value => 'danger',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),

                Section::make('تفاصيل الحضور')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('meeting_enter_time')
                                    ->label('وقت الدخول')
                                    ->dateTime('H:i')
                                    ->placeholder('لم يدخل'),
                                TextEntry::make('meeting_leave_time')
                                    ->label('وقت الخروج')
                                    ->dateTime('H:i')
                                    ->placeholder('لم يخرج'),
                                TextEntry::make('actual_attendance_minutes')
                                    ->label('مدة الحضور')
                                    ->suffix(' دقيقة'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('late_minutes')
                                    ->label('دقائق التأخير')
                                    ->suffix(' دقيقة'),
                                TextEntry::make('attendance_percentage')
                                    ->label('نسبة الحضور')
                                    ->suffix('%')
                                    ->numeric(2),
                            ]),
                    ]),

                Section::make('التقييم الأكاديمي')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('new_memorization_degree')
                                    ->label('درجة الحفظ الجديد')
                                    ->suffix('/10'),
                                TextEntry::make('reservation_degree')
                                    ->label('درجة المراجعة')
                                    ->suffix('/10'),
                            ]),

                        TextEntry::make('notes')
                            ->label('ملاحظات التقييم')
                            ->placeholder('لا توجد ملاحظات'),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('is_calculated')
                                    ->label('نوع التقييم')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'محسوب تلقائياً' : 'مقيم يدوياً')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'info' : 'warning'),
                                TextEntry::make('evaluated_at')
                                    ->label('تاريخ التقييم')
                                    ->dateTime('Y-m-d H:i')
                                    ->placeholder('لم يقيم بعد'),
                            ]),
                    ]),
            ]);
    }
}
