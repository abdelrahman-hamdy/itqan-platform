<?php

namespace App\Filament\Teacher\Resources\StudentSessionReportResource\Pages;

use App\Filament\Teacher\Resources\StudentSessionReportResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentSessionReport extends ViewRecord
{
    protected static string $resource = StudentSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'تقرير '.($this->getRecord()->student->name ?? 'الطالب');
    }

    public function getBreadcrumbs(): array
    {
        $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';

        $breadcrumbs = [
            route('teacher.profile', ['subdomain' => $subdomain]) => 'ملفي الشخصي',
        ];

        // Add parent breadcrumbs
        $parentBreadcrumbs = parent::getBreadcrumbs();

        // Skip the first item (dashboard) and use our custom profile link instead
        $filteredBreadcrumbs = array_slice($parentBreadcrumbs, 1, null, true);

        return array_merge($breadcrumbs, $filteredBreadcrumbs);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الطالب والجلسة')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('اسم الطالب'),
                                Infolists\Components\TextEntry::make('session.scheduled_at')
                                    ->label('تاريخ الجلسة')
                                    ->dateTime('Y-m-d H:i'),
                                Infolists\Components\TextEntry::make('session.session_type')
                                    ->label('نوع الجلسة')
                                    ->formatStateUsing(function (string $state): string {
                                        return match ($state) {
                                            'individual' => 'فردية',
                                            'group' => 'جماعية',
                                            default => $state,
                                        };
                                    })
                                    ->badge(),
                                Infolists\Components\TextEntry::make('attendance_status')
                                    ->label('حالة الحضور')
                                    ->formatStateUsing(function (string $state): string {
                                        return match ($state) {
                                            'present' => 'حاضر',
                                            'late' => 'متأخر',
                                            'partial' => 'جزئي',
                                            'absent' => 'غائب',
                                            default => $state,
                                        };
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'present' => 'success',
                                        'late' => 'warning',
                                        'partial' => 'info',
                                        'absent' => 'danger',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('تفاصيل الحضور')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('meeting_enter_time')
                                    ->label('وقت الدخول')
                                    ->dateTime('H:i')
                                    ->placeholder('لم يدخل'),
                                Infolists\Components\TextEntry::make('meeting_leave_time')
                                    ->label('وقت الخروج')
                                    ->dateTime('H:i')
                                    ->placeholder('لم يخرج'),
                                Infolists\Components\TextEntry::make('actual_attendance_minutes')
                                    ->label('مدة الحضور')
                                    ->suffix(' دقيقة'),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('late_minutes')
                                    ->label('دقائق التأخير')
                                    ->suffix(' دقيقة'),
                                Infolists\Components\TextEntry::make('attendance_percentage')
                                    ->label('نسبة الحضور')
                                    ->suffix('%')
                                    ->numeric(2),
                                Infolists\Components\TextEntry::make('connection_quality_score')
                                    ->label('جودة الاتصال')
                                    ->suffix('/100'),
                            ]),
                    ]),

                Infolists\Components\Section::make('التقييم الأكاديمي')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('new_memorization_degree')
                                    ->label('درجة الحفظ الجديد')
                                    ->suffix('/10'),
                                Infolists\Components\TextEntry::make('reservation_degree')
                                    ->label('درجة المراجعة')
                                    ->suffix('/10'),
                            ]),

                        Infolists\Components\TextEntry::make('notes')
                            ->label('ملاحظات التقييم')
                            ->placeholder('لا توجد ملاحظات'),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('is_auto_calculated')
                                    ->label('نوع التقييم')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'محسوب تلقائياً' : 'مقيم يدوياً')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'info' : 'warning'),
                                Infolists\Components\TextEntry::make('evaluated_at')
                                    ->label('تاريخ التقييم')
                                    ->dateTime('Y-m-d H:i')
                                    ->placeholder('لم يقيم بعد'),
                            ]),
                    ]),
            ]);
    }
}
