<?php

namespace App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewMonitoredAcademicLesson extends ViewRecord
{
    protected static string $resource = MonitoredAcademicLessonsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Supervisors can view but not edit
        ];
    }
}
