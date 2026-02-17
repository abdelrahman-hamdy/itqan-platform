<?php

namespace App\Filament\Teacher\Resources\TeacherEarningsResource\Pages;

use App\Models\TeacherEarning;
use App\Filament\Teacher\Resources\TeacherEarningsResource;
use Filament\Resources\Pages\ViewRecord;

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
