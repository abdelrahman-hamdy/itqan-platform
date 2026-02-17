<?php

namespace App\Filament\AcademicTeacher\Resources\TeacherEarningsResource\Pages;

use App\Models\TeacherEarning;
use App\Filament\AcademicTeacher\Resources\TeacherEarningsResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

/**
 * @property TeacherEarning $record
 */
class ViewTeacherEarning extends ViewRecord
{
    protected static string $resource = TeacherEarningsResource::class;

    public function getTitle(): string
    {
        $month = $this->record->earning_month?->format('Y-m') ?? 'غير محدد';

        return "أرباح شهر {$month}";
    }

    protected function getHeaderActions(): array
    {
        return [
            // No edit action - earnings are read-only for teachers
        ];
    }
}
