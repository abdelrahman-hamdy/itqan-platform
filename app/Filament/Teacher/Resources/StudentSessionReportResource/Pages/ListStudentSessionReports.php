<?php

namespace App\Filament\Teacher\Resources\StudentSessionReportResource\Pages;

use App\Enums\AttendanceStatus;
use App\Filament\Teacher\Resources\StudentSessionReportResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListStudentSessionReports extends ListRecords
{
    protected static string $resource = StudentSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Reports are automatically generated, no manual creation needed
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'تقارير الطلاب',
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('جميع التقارير'),
            'individual' => Tab::make('الجلسات الفردية')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('session', function (Builder $subQuery) {
                    $subQuery->where('session_type', 'individual');
                })),
            'group' => Tab::make('الجلسات الجماعية')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('session', function (Builder $subQuery) {
                    $subQuery->where('session_type', 'group');
                })),
            'present' => Tab::make('الحاضرون')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('attendance_status', AttendanceStatus::ATTENDED->value)),
            AttendanceStatus::ABSENT->value => Tab::make('الغائبون')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('attendance_status', AttendanceStatus::ABSENT->value)),
            'manual' => Tab::make('مقيم يدوياً')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_calculated', false)),
        ];
    }
}
