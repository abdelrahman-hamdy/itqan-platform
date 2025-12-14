<?php

namespace App\Filament\AcademicTeacher\Resources\InteractiveSessionReportResource\Pages;

use App\Filament\AcademicTeacher\Resources\InteractiveSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInteractiveSessionReport extends ViewRecord
{
    protected static string $resource = InteractiveSessionReportResource::class;

    public function getTitle(): string
    {
        $studentName = $this->record->student?->name ?? 'طالب';
        $courseName = $this->record->session?->course?->name ?? 'دورة';
        return "تقرير: {$studentName} - {$courseName}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }
}
