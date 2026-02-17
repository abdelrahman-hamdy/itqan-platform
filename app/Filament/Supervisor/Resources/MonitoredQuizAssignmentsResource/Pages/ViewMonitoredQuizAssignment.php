<?php

namespace App\Filament\Supervisor\Resources\MonitoredQuizAssignmentsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredQuizAssignmentsResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewMonitoredQuizAssignment extends ViewRecord
{
    protected static string $resource = MonitoredQuizAssignmentsResource::class;

    public function getTitle(): string
    {
        $quizTitle = $this->record->quiz?->title ?? 'اختبار';

        return "تعيين: {$quizTitle}";
    }

    protected function getHeaderActions(): array
    {
        return [
            // No edit action - supervisors view only
        ];
    }
}
