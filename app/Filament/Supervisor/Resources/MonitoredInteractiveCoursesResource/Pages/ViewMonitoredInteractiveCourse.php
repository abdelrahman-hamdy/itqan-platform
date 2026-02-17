<?php

namespace App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewMonitoredInteractiveCourse extends ViewRecord
{
    protected static string $resource = MonitoredInteractiveCoursesResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
