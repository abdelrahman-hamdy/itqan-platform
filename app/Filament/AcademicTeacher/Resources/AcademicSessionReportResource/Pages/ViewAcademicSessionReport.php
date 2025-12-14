<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages;

use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource;
use Filament\Actions;
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
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }
}
