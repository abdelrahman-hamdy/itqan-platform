<?php

namespace App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMonitoredInteractiveCourse extends ViewRecord
{
    protected static string $resource = MonitoredInteractiveCoursesResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
