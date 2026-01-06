<?php

namespace App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource;
use Filament\Resources\Pages\ViewRecord;

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
